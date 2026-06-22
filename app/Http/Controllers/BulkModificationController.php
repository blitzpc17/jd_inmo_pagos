<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkModificationController extends Controller
{
    public function index()
    {
        $userId = session('auth_user.id');
        $isAuthorizer = DB::table('authorizer_users')->where('user_id', $userId)->exists();

        return view('bulk_modifications.index', [
            'isAuthorizer' => $isAuthorizer
        ]);
    }

    public function datatable()
    {
        $rows = DB::table('modification_requests as mr')
            ->join('users as ur', 'ur.id', '=', 'mr.requested_by')
            ->leftJoin('users as ua', 'ua.id', '=', 'mr.authorized_by')
            ->leftJoin('users as uj', 'uj.id', '=', 'mr.rejected_by')
            ->select([
                'mr.*',
                'ur.alias as requested_by_alias',
                'ua.alias as authorized_by_alias',
                'uj.alias as rejected_by_alias',
            ])
            ->orderByDesc('mr.id')
            ->get()
            ->map(function ($r) {
                // Count items
                $r->items_count = DB::table('modification_request_items')
                    ->where('modification_request_id', $r->id)
                    ->count();
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function show(int $id)
    {
        $request = DB::table('modification_requests as mr')
            ->join('users as ur', 'ur.id', '=', 'mr.requested_by')
            ->select(['mr.*', 'ur.alias as requested_by_alias'])
            ->where('mr.id', $id)
            ->first();

        abort_if(!$request, 404, 'Solicitud no encontrada');

        $items = DB::table('modification_request_items')
            ->where('modification_request_id', $id)
            ->get()
            ->map(function ($item) use ($request) {
                $item->original_data = json_decode($item->original_data, true);
                $item->new_data = json_decode($item->new_data, true);
                
                // Get the reference name of the record
                $ref = '';
                if ($request->type === 'COBRO') {
                    $ref = DB::table('charges')->where('id', $item->record_id)->value('numero_referencia');
                } elseif ($request->type === 'CONTRATO') {
                    $ref = DB::table('contracts')->where('id', $item->record_id)->value('numero_referencia');
                } elseif ($request->type === 'APARTADO') {
                    $ref = DB::table('reservations')->where('id', $item->record_id)->value('numero_referencia');
                }
                $item->reference = $ref ?: ('ID: ' . $item->record_id);

                return $item;
            });

        return response()->json([
            'ok' => true,
            'request' => $request,
            'items' => $items
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:COBRO,CONTRATO,APARTADO'],
            'justification' => ['required', 'string', 'min:5'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.record_id' => ['required', 'integer'],
            'items.*.action' => ['required', 'string', 'in:MODIFICAR,ELIMINAR'],
            'items.*.new_data' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $type = $request->type;
        $justification = $request->justification;
        $items = $request->items;

        $tableName = $this->getTableName($type);

        return DB::transaction(function () use ($type, $justification, $items, $tableName) {
            $requestId = DB::table('modification_requests')->insertGetId([
                'type' => $type,
                'status' => 'PENDIENTE',
                'justification' => $justification,
                'requested_by' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $recordId = (int)$item['record_id'];
                $action = $item['action'] ?? 'MODIFICAR';
                $newData = $item['new_data'] ?? [];

                // Get original record data
                $originalRecord = DB::table($tableName)->where('id', $recordId)->first();
                if (!$originalRecord) {
                    throw new \RuntimeException("El registro con ID {$recordId} en la tabla {$tableName} no existe.");
                }

                // Filter original data to contain only the keys present in new data
                $originalDataFiltered = [];
                if ($action === 'MODIFICAR') {
                    foreach ($newData as $key => $val) {
                        $originalDataFiltered[$key] = $originalRecord->$key ?? null;
                    }
                } else {
                    $originalDataFiltered = ['numero_referencia' => $originalRecord->numero_referencia ?? ''];
                }

                DB::table('modification_request_items')->insert([
                    'modification_request_id' => $requestId,
                    'record_id' => $recordId,
                    'action' => $action,
                    'original_data' => json_encode($originalDataFiltered),
                    'new_data' => json_encode($newData),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'ok' => true,
                'message' => 'Solicitud de modificación creada con éxito y en espera de autorización.'
            ]);
        });
    }

    public function approve(int $id)
    {
        $userId = session('auth_user.id');
        $isAuthorizer = DB::table('authorizer_users')->where('user_id', $userId)->exists();
        
        if (!$isAuthorizer) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos de usuario autorizante para realizar esta acción.'
            ], 403);
        }

        $requestRecord = DB::table('modification_requests')->where('id', $id)->first();
        abort_if(!$requestRecord, 404, 'Solicitud no encontrada');

        if ($requestRecord->status !== 'PENDIENTE') {
            return response()->json([
                'ok' => false,
                'message' => 'Esta solicitud ya ha sido procesada.'
            ], 422);
        }

        $type = $requestRecord->type;
        $tableName = $this->getTableName($type);
        $items = DB::table('modification_request_items')->where('modification_request_id', $id)->get();

        return DB::transaction(function () use ($id, $userId, $tableName, $items, $type) {
            $contractsToRecalculate = [];

            // Get Statuses we might need
            $libreLotStatusId = DB::table('statuses as s')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('p.clave', 'LOT_STATUS')
                ->where('s.clave', 'LIBRE')
                ->value('s.id');

            $canceladoContractStatusId = DB::table('statuses as s')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('p.clave', 'CONTRACT_STATUS')
                ->where('s.clave', 'CANCELADO')
                ->value('s.id');

            $vencidoReservationStatusId = DB::table('statuses as s')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('p.clave', 'RESERVATION_STATUS')
                ->where('s.clave', 'VENCIDO')
                ->value('s.id');

            $canceladoChargeStatusId = DB::table('statuses as s')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('p.clave', 'CHARGE_STATUS')
                ->where('s.clave', 'CANCELADO')
                ->value('s.id');

            foreach ($items as $item) {
                $recordId = (int)$item->record_id;
                $action = $item->action ?? 'MODIFICAR';

                if ($action === 'ELIMINAR') {
                    if ($type === 'CONTRATO') {
                        // 1. Soft delete Contract
                        DB::table('contracts')
                            ->where('id', $recordId)
                            ->update([
                                'fecha_baja' => now(),
                                'usuario_baja_id' => $userId,
                                'status_id' => $canceladoContractStatusId,
                                'updated_at' => now(),
                            ]);

                        // 2. Find associated reservation in contract_reservations
                        $reservationId = DB::table('contract_reservations')
                            ->where('contract_id', $recordId)
                            ->value('reservation_id');

                        if ($reservationId) {
                            // Soft delete Reservation
                            DB::table('reservations')
                                ->where('id', $reservationId)
                                ->update([
                                    'fecha_baja' => now(),
                                    'usuario_baja_id' => $userId,
                                    'status_id' => $vencidoReservationStatusId,
                                    'updated_at' => now(),
                                ]);

                            // Free reservation lots
                            $resLotIds = DB::table('reservation_lots')
                                ->where('reservation_id', $reservationId)
                                ->pluck('lot_id')
                                ->all();

                            if (!empty($resLotIds) && $libreLotStatusId) {
                                DB::table('lots')
                                    ->whereIn('id', $resLotIds)
                                    ->update([
                                        'status_id' => $libreLotStatusId,
                                        'updated_at' => now(),
                                    ]);
                            }
                        }

                        // 3. Free contract lots
                        $lotIds = DB::table('contract_lots')
                            ->where('contract_id', $recordId)
                            ->pluck('lot_id')
                            ->all();

                        if (!empty($lotIds) && $libreLotStatusId) {
                            DB::table('lots')
                                ->whereIn('id', $lotIds)
                                ->update([
                                    'status_id' => $libreLotStatusId,
                                    'updated_at' => now(),
                                ]);
                        }

                        // 4. Soft delete all charges associated with the contract or reservation
                        $chargeQuery = DB::table('charges')
                            ->where(function($q) use ($recordId, $reservationId) {
                                $q->where('contract_id', $recordId);
                                if ($reservationId) {
                                    $q->orWhere('reservation_id', $reservationId);
                                }
                            });

                        $chargeQuery->update([
                            'fecha_baja' => now(),
                            'usuario_baja_id' => $userId,
                            'status_id' => $canceladoChargeStatusId,
                            'updated_at' => now(),
                        ]);

                        // 5. Soft delete payment schedules (update status to CANCELADO)
                        DB::table('payment_schedules')
                            ->where('contract_id', $recordId)
                            ->update([
                                'status' => 'CANCELADO',
                                'updated_at' => now(),
                            ]);

                    } elseif ($type === 'COBRO') {
                        $contractId = DB::table('charges')->where('id', $recordId)->value('contract_id');
                        
                        // Soft delete Charge
                        DB::table('charges')
                            ->where('id', $recordId)
                            ->update([
                                'fecha_baja' => now(),
                                'usuario_baja_id' => $userId,
                                'status_id' => $canceladoChargeStatusId,
                                'updated_at' => now(),
                            ]);

                        if ($contractId) {
                            $contractsToRecalculate[$contractId] = true;
                        }

                    } elseif ($type === 'APARTADO') {
                        // Soft delete Reservation
                        DB::table('reservations')
                            ->where('id', $recordId)
                            ->update([
                                'fecha_baja' => now(),
                                'usuario_baja_id' => $userId,
                                'status_id' => $vencidoReservationStatusId,
                                'updated_at' => now(),
                            ]);

                        // Free lots associated with reservation
                        $resLotIds = DB::table('reservation_lots')
                            ->where('reservation_id', $recordId)
                            ->pluck('lot_id')
                            ->all();

                        if (!empty($resLotIds) && $libreLotStatusId) {
                            DB::table('lots')
                                ->whereIn('id', $resLotIds)
                                ->update([
                                    'status_id' => $libreLotStatusId,
                                    'updated_at' => now(),
                                ]);
                        }
                    }
                } else {
                    $newData = json_decode($item->new_data, true);

                    // If it is a contract and we are modifying its emission date (fecha_emision)
                    if ($type === 'CONTRATO') {
                        $oldContract = DB::table('contracts')->where('id', $recordId)->first();
                        $newFechaEmision = $newData['fecha_emision'] ?? null;
                        if ($oldContract && $newFechaEmision && $oldContract->fecha_emision !== $newFechaEmision) {
                            $this->updatePaymentSchedulesDueDates($recordId, $newFechaEmision);
                            $contractsToRecalculate[$recordId] = true;
                        }
                    }

                    // For charges, track the contract ID before we apply changes
                    if ($type === 'COBRO') {
                        $contractId = DB::table('charges')->where('id', $recordId)->value('contract_id');
                        if ($contractId) {
                            $contractsToRecalculate[$contractId] = true;
                        }
                    }

                    // Apply update
                    DB::table($tableName)
                        ->where('id', $recordId)
                        ->update($newData);

                    // For charges, check the contract ID after update too, just in case
                    if ($type === 'COBRO') {
                        $contractId = DB::table('charges')->where('id', $recordId)->value('contract_id');
                        if ($contractId) {
                            $contractsToRecalculate[$contractId] = true;
                        }
                    }
                }
            }

            // Perform automatic recalculation for affected contracts
            foreach (array_keys($contractsToRecalculate) as $cId) {
                $this->recalculateSchedulesForContract((int)$cId);
            }

            // Update request status
            DB::table('modification_requests')
                ->where('id', $id)
                ->update([
                    'status' => 'APROBADO',
                    'authorized_by' => $userId,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'ok' => true,
                'message' => 'Solicitud aprobada y modificaciones/eliminaciones aplicadas automáticamente.'
            ]);
        });
    }

    public function reject(Request $request, int $id)
    {
        $userId = session('auth_user.id');
        $isAuthorizer = DB::table('authorizer_users')->where('user_id', $userId)->exists();
        
        if (!$isAuthorizer) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permisos de usuario autorizante para realizar esta acción.'
            ], 403);
        }

        $requestRecord = DB::table('modification_requests')->where('id', $id)->first();
        abort_if(!$requestRecord, 404, 'Solicitud no encontrada');

        if ($requestRecord->status !== 'PENDIENTE') {
            return response()->json([
                'ok' => false,
                'message' => 'Esta solicitud ya ha sido procesada.'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => ['required', 'string', 'min:3']
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::table('modification_requests')
            ->where('id', $id)
            ->update([
                'status' => 'RECHAZADO',
                'rejected_by' => $userId,
                'rejection_reason' => $request->rejection_reason,
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Solicitud rechazada correctamente.'
        ]);
    }

    public function options()
    {
        $offices = DB::table('offices')
            ->whereNull('fecha_baja')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $chargeStatuses = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CHARGE_STATUS')
            ->orderBy('s.nombre')
            ->get(['s.id as value', 's.nombre as text']);

        $reservationStatuses = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'RESERVATION_STATUS')
            ->orderBy('s.nombre')
            ->get(['s.id as value', 's.nombre as text']);

        $activeId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        $alreadyAuthorizers = DB::table('authorizer_users')->pluck('user_id')->all();

        $availableUsers = DB::table('users as u')
            ->join('personal as p', 'p.id', '=', 'u.personal_id')
            ->where('u.status_id', $activeId)
            ->whereNotIn('u.id', $alreadyAuthorizers)
            ->select([
                'u.id as value',
                DB::raw("u.alias || ' - ' || TRIM(COALESCE(p.nombres,'') || ' ' || COALESCE(p.apellidos,'')) as text")
            ])
            ->orderBy('u.alias')
            ->get();

        return response()->json([
            'offices' => $offices,
            'payment_methods' => $paymentMethods,
            'charge_statuses' => $chargeStatuses,
            'reservation_statuses' => $reservationStatuses,
            'available_users' => $availableUsers,
        ]);
    }

    public function searchRecords(Request $request)
    {
        $type = $request->input('type');
        $q = trim((string)$request->input('q', ''));

        if (!$type) {
            return response()->json([]);
        }

        $tableName = $this->getTableName($type);
        $query = DB::table($tableName . ' as t')
            ->join('clients as cl', 'cl.id', '=', 't.client_id')
            ->select([
                't.id',
                't.numero_referencia',
                DB::raw("TRIM(COALESCE(cl.nombres,'') || ' ' || COALESCE(cl.apellidos,'')) as cliente")
            ])
            ->limit(30);

        if ($type === 'COBRO') {
            $query->whereNull('t.fecha_baja');
        } elseif ($type === 'CONTRATO') {
            $query->whereNull('t.fecha_baja');
        } elseif ($type === 'APARTADO') {
            $query->whereNull('t.fecha_baja');
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('t.numero_referencia', 'ILIKE', '%' . $q . '%')
                    ->orWhere(DB::raw("TRIM(COALESCE(cl.nombres,'') || ' ' || COALESCE(cl.apellidos,''))"), 'ILIKE', '%' . $q . '%');
            });
        }

        $results = $query->get()->map(function ($row) use ($type, $tableName) {
            $extra = '';
            if ($type === 'COBRO') {
                $monto = DB::table($tableName)->where('id', $row->id)->value('monto');
                $extra = ' | Monto: $' . number_format($monto, 2);
            } elseif ($type === 'CONTRATO') {
                $importe = DB::table($tableName)->where('id', $row->id)->value('importe');
                $extra = ' | Importe: $' . number_format($importe, 2);
            } elseif ($type === 'APARTADO') {
                $importe = DB::table($tableName)->where('id', $row->id)->value('importe_apartado');
                $extra = ' | Apartado: $' . number_format($importe, 2);
            }

            return [
                'id' => $row->id,
                'text' => $row->numero_referencia . ' - ' . $row->cliente . $extra
            ];
        });

        return response()->json($results);
    }

    public function recordDetails(Request $request)
    {
        $type = $request->input('type');
        $id = (int)$request->input('id');

        if (!$type || !$id) {
            return response()->json(['ok' => false, 'message' => 'Parámetros inválidos.'], 400);
        }

        $tableName = $this->getTableName($type);
        $record = DB::table($tableName)->where('id', $id)->first();

        if (!$record) {
            return response()->json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $record
        ]);
    }

    public function getClients(Request $request)
    {
        $q = trim((string)$request->input('q', ''));
        $query = DB::table('clients as c')
            ->whereNull('c.fecha_baja')
            ->select([
                'c.id',
                DB::raw("TRIM(COALESCE(c.nombres,'') || ' ' || COALESCE(c.apellidos,'')) as text")
            ])
            ->orderBy('c.nombres')
            ->limit(30);

        if ($q !== '') {
            $query->where(DB::raw("TRIM(COALESCE(c.nombres,'') || ' ' || COALESCE(c.apellidos,''))"), 'ILIKE', '%' . $q . '%');
        }

        return response()->json($query->get());
    }

    public function getClientContracts(int $clientId)
    {
        $contracts = DB::table('contracts as c')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->join('developments as d', 'd.id', '=', 'c.development_id')
            ->where('c.client_id', $clientId)
            ->whereNull('c.fecha_baja')
            ->select([
                'c.id',
                'c.numero_referencia',
                'c.fecha_emision',
                'd.nombre as lotificacion',
                's.nombre as estado'
            ])
            ->orderByDesc('c.id')
            ->get()
            ->map(function($row) {
                $row->text = $row->numero_referencia . ' - ' . $row->lotificacion . ' (' . $row->estado . ')';
                return $row;
            });

        return response()->json($contracts);
    }

    public function getContractCharges(int $contractId)
    {
        $charges = DB::table('charges as ch')
            ->join('charge_types as ct', 'ct.id', '=', 'ch.charge_type_id')
            ->join('statuses as s', 's.id', '=', 'ch.status_id')
            ->leftJoin('offices as o', 'o.id', '=', 'ch.office_receives_charge_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'ch.payment_method_id')
            ->where('ch.contract_id', $contractId)
            ->whereNull('ch.fecha_baja')
            ->select([
                'ch.*',
                'ct.nombre as tipo_cobro',
                's.nombre as estado',
                'o.nombre as oficina',
                'pm.nombre as forma_pago'
            ])
            ->orderBy('ch.id')
            ->get();

        return response()->json($charges);
    }

    public function getClientReservations(int $clientId)
    {
        $reservations = DB::table('reservations as r')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->join('statuses as s', 's.id', '=', 'r.status_id')
            ->where('r.client_id', $clientId)
            ->whereNull('r.fecha_baja')
            ->select([
                'r.*',
                'd.nombre as lotificacion',
                's.nombre as estado'
            ])
            ->orderByDesc('r.id')
            ->get()
            ->map(function($row) {
                $row->has_contract = DB::table('contract_reservations')
                    ->where('reservation_id', $row->id)
                    ->exists();
                return $row;
            });

        return response()->json($reservations);
    }

    protected function updatePaymentSchedulesDueDates(int $contractId, string $newFechaEmision)
    {
        $contract = DB::table('contracts')->where('id', $contractId)->first();
        if (!$contract) return;

        $baseDate = Carbon::parse($newFechaEmision);
        $diaPago = (int)($contract->dia_pago ?? $baseDate->day);

        $schedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->get();

        foreach ($schedules as $schedule) {
            $i = (int)$schedule->installment_number;
            $due = $baseDate->copy()->addMonthsNoOverflow($i);
            $lastDay = $due->copy()->endOfMonth()->day;
            $safeDay = min($diaPago, $lastDay);
            $due->day($safeDay);

            DB::table('payment_schedules')
                ->where('id', $schedule->id)
                ->update([
                    'due_date' => $due->toDateString(),
                    'updated_at' => now()
                ]);
        }
    }



    // --- Helper Methods ---

    protected function getTableName(string $type): string
    {
        switch ($type) {
            case 'COBRO': return 'charges';
            case 'CONTRATO': return 'contracts';
            case 'APARTADO': return 'reservations';
            default: throw new \InvalidArgumentException("Tipo inválido: {$type}");
        }
    }

    protected function recalculateSchedulesForContract(int $contractId)
    {
        $schedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->orderBy('due_date')
            ->get();

        $currentDate = now()->startOfDay();

        foreach ($schedules as $schedule) {
            $totalPaid = (float)DB::table('charges')
                ->where('payment_schedule_id', $schedule->id)
                ->whereNull('fecha_baja')
                ->sum('monto');

            $totalRecargo = (float)DB::table('charges')
                ->where('payment_schedule_id', $schedule->id)
                ->whereNull('fecha_baja')
                ->sum('monto_recargo');

            $due = Carbon::parse($schedule->due_date)->startOfDay();
            $dueMonth = $due->copy()->startOfMonth();
            $currentMonth = $currentDate->copy()->startOfMonth();

            $amount = (float)$schedule->amount;
            $amountPaid = round($totalPaid, 2);
            $principalRemaining = max(0.0, round($amount - $amountPaid, 2));

            $monthDiff = (int)$dueMonth->diffInMonths($currentMonth, false);
            $isOverdue = $monthDiff > 0 && $principalRemaining > 0.009;

            $isFuture = $dueMonth->greaterThan($currentMonth);
            $isPaid = $principalRemaining <= 0.009;

            if ($isPaid) {
                $status = $isFuture ? 'ADELANTADO' : 'PAGADO';
                $amountPaid = $amount; // Normalise
            } elseif ($isOverdue && $amountPaid > 0) {
                $status = 'ATRASADO_PARCIAL';
            } elseif ($isOverdue) {
                $status = 'ATRASADO';
            } elseif ($amountPaid > 0) {
                $status = 'PARCIAL';
            } else {
                $status = 'PENDIENTE';
            }

            $lastChargeId = DB::table('charges')
                ->where('payment_schedule_id', $schedule->id)
                ->whereNull('fecha_baja')
                ->orderByDesc('id')
                ->value('id');

            DB::table('payment_schedules')
                ->where('id', $schedule->id)
                ->update([
                    'amount_paid' => $amountPaid,
                    'late_fee_amount' => $totalRecargo,
                    'late_fee_applied' => $totalRecargo > 0.009,
                    'status' => $status,
                    'charge_id' => $lastChargeId,
                    'updated_at' => now(),
                ]);
        }

        // Check if contract is fully paid
        $allSchedules = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->get();

        $totalRemaining = $allSchedules->sum(function ($s) {
            return max(0.0, (float)$s->amount - (float)$s->amount_paid);
        });

        if ($totalRemaining <= 0.009) {
            $liquidatedStatusId = DB::table('statuses as s')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('p.clave', 'CONTRACT_STATUS')
                ->where('s.clave', 'LIQUIDADO')
                ->value('s.id');

            if ($liquidatedStatusId) {
                DB::table('contracts')
                    ->where('id', $contractId)
                    ->update([
                        'status_id' => $liquidatedStatusId,
                        'updated_at' => now()
                    ]);
            }
        } else {
            $contractStatus = DB::table('contracts')->where('id', $contractId)->value('status_id');
            $liquidatedStatusId = DB::table('statuses as s')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('p.clave', 'CONTRACT_STATUS')
                ->where('s.clave', 'LIQUIDADO')
                ->value('s.id');

            if ($contractStatus == $liquidatedStatusId) {
                $vigenteStatusId = DB::table('statuses as s')
                    ->join('processes as p', 'p.id', '=', 's.process_id')
                    ->where('p.clave', 'CONTRACT_STATUS')
                    ->where('s.clave', 'VIGENTE')
                    ->value('s.id');

                if ($vigenteStatusId) {
                    DB::table('contracts')
                        ->where('id', $contractId)
                        ->update([
                            'status_id' => $vigenteStatusId,
                            'updated_at' => now()
                        ]);
                }
            }
        }
    }
}
