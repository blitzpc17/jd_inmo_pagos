<?php

namespace App\Http\Controllers;

use App\Services\ContractCollectionService;
use App\Services\PdfReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChargeController extends Controller
{
    public function index()
    {
        return view('cobros.index');
    }

    public function clients(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $query = DB::table('clients as c')
            ->whereNull('c.fecha_baja')
            ->select([
                'c.id',
                DB::raw("TRIM(COALESCE(c.nombres,'') || ' ' || COALESCE(c.apellidos,'')) as text"),
                'c.telefono',
            ])
            ->orderBy('c.nombres')
            ->limit(30);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->whereRaw(
                    "LOWER(COALESCE(c.nombres,'') || ' ' || COALESCE(c.apellidos,'')) LIKE ?",
                    ['%' . mb_strtolower($term) . '%']
                )->orWhereRaw(
                    "LOWER(COALESCE(c.telefono,'')) LIKE ?",
                    ['%' . mb_strtolower($term) . '%']
                );
            });
        }

        return response()->json([
            'results' => $query->get(),
        ]);
    }

    public function clientContracts(int $clientId, ContractCollectionService $service)
    {
        $rows = DB::table('contracts as c')
            ->join('statuses as s', 's.id', '=', 'c.status_id')
            ->join('developments as d', 'd.id', '=', 'c.development_id')
            ->leftJoin('contract_payment_types as cpt', 'cpt.id', '=', 'c.contract_payment_type_id')
            ->where('c.client_id', $clientId)
            ->whereNull('c.fecha_baja')
            ->orderByDesc('c.id')
            ->get([
                'c.id',
                'c.numero_referencia',
                'c.importe',
                'c.cuota_mensual',
                's.clave as estado_clave',
                's.nombre as estado_nombre',
                'd.nombre as lotificacion',
                'cpt.nombre as tipo_pago',
                'c.is_migration',
            ])
            ->map(function ($row) use ($service) {
                if ($row->estado_clave === 'VIGENTE') {
                    $service->enforceDelinquencyRule((int) $row->id);

                    $fresh = DB::table('contracts as c')
                        ->join('statuses as s', 's.id', '=', 'c.status_id')
                        ->where('c.id', $row->id)
                        ->first([
                            's.clave as estado_clave',
                            's.nombre as estado_nombre',
                        ]);

                    if ($fresh) {
                        $row->estado_clave = $fresh->estado_clave;
                        $row->estado_nombre = $fresh->estado_nombre;
                    }
                }

                $row->text = $row->numero_referencia . ' - ' . $row->lotificacion . ' - ' . $row->estado_nombre;
                $row->can_charge = $row->estado_clave === 'VIGENTE';

                return $row;
            });

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function preview(Request $request, int $contractId, ContractCollectionService $service)
    {
        $fechaCobro = $request->query('fecha_cobro');
        $waiveLateFee = $request->boolean('waive_late_fee', false);

        return response()->json($service->preview($contractId, $fechaCobro, $waiveLateFee));
    }

    public function contractOffices(int $contractId)
    {
        $rows = DB::table('contracts as c')
            ->join('contract_lots as cl', 'cl.contract_id', '=', 'c.id')
            ->join('lots as l', 'l.id', '=', 'cl.lot_id')
            ->join('lot_offices as lo', 'lo.lot_id', '=', 'l.id')
            ->join('offices as o', 'o.id', '=', 'lo.office_id')
            ->join('statuses as s', 's.id', '=', 'o.status_id')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('c.id', $contractId)
            ->whereNull('c.fecha_baja')
            ->whereNull('o.fecha_baja')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->distinct()
            ->orderBy('o.nombre')
            ->get([
                'o.id as value',
                'o.nombre as text',
            ]);

        if ($rows->isEmpty()) {
            $rows = DB::table('contracts as c')
                ->join('offices as o', 'o.id', '=', 'c.office_id')
                ->join('statuses as s', 's.id', '=', 'o.status_id')
                ->join('processes as p', 'p.id', '=', 's.process_id')
                ->where('c.id', $contractId)
                ->whereNull('c.fecha_baja')
                ->whereNull('o.fecha_baja')
                ->where('p.clave', 'GENERAL')
                ->where('s.clave', 'ACTIVE')
                ->orderBy('o.nombre')
                ->get([
                    'o.id as value',
                    'o.nombre as text',
                ]);
        }

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function officePaymentMethods(int $contractId, int $officeId)
    {
        $officeIsAllowed = $this->officeBelongsToContractLots($contractId, $officeId);

        if (!$officeIsAllowed) {
            $officeIsAllowed = DB::table('contracts')
                ->where('id', $contractId)
                ->where('office_id', $officeId)
                ->whereNull('fecha_baja')
                ->exists();
        }

        if (!$officeIsAllowed) {
            return response()->json([
                'message' => 'La oficina seleccionada no está vinculada a los lotes del contrato.',
                'data' => [],
            ], 422);
        }

        $rows = DB::table('payment_methods as pm')
            ->join('statuses as s', 's.id', '=', 'pm.status_id')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('pm.office_id', $officeId)
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->orderBy('pm.nombre')
            ->get([
                'pm.id as value',
                'pm.nombre as text',
            ]);

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function store(Request $request, int $contractId, ContractCollectionService $service)
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'office_receives_charge_id' => ['required', 'integer', 'exists:offices,id'],
            'observacion' => ['nullable', 'string', 'max:1000'],
            'fecha_cobro' => ['required', 'date_format:Y-m-d H:i:s'],
            'waive_late_fee' => ['nullable', 'boolean'],
        ]);

        try {
            $officeId = (int) $data['office_receives_charge_id'];
            $paymentMethodId = (int) $data['payment_method_id'];

            $officeIsAllowed = $this->officeBelongsToContractLots($contractId, $officeId);

            if (!$officeIsAllowed) {
                $officeIsAllowed = DB::table('contracts')
                    ->where('id', $contractId)
                    ->where('office_id', $officeId)
                    ->whereNull('fecha_baja')
                    ->exists();
            }

            if (!$officeIsAllowed) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La oficina seleccionada no está vinculada a los lotes del contrato.',
                ], 422);
            }

            $paymentMethodIsAllowed = DB::table('payment_methods')
                ->where('id', $paymentMethodId)
                ->where('office_id', $officeId)
                ->exists();

            if (!$paymentMethodIsAllowed) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La forma de pago no pertenece a la oficina seleccionada.',
                ], 422);
            }

            return response()->json($service->applyPayment($contractId, $data));
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function options()
    {
        return response()->json([
            'offices' => [],
            'payment_methods' => [],
        ]);
    }

    public function paymentGroup(string $paymentGroupUuid)
    {
        $rows = DB::table('charges as ch')
            ->leftJoin('charge_types as ct', 'ct.id', '=', 'ch.charge_type_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'ch.payment_method_id')
            ->leftJoin('offices as o', 'o.id', '=', 'ch.office_receives_charge_id')
            ->leftJoin('clients as cl', 'cl.id', '=', 'ch.client_id')
            ->where('ch.payment_group_uuid', $paymentGroupUuid)
            ->whereNull('ch.fecha_baja')
            ->orderBy('ch.id')
            ->select([
                'ch.id',
                'ch.numero_referencia',
                'ch.fecha_emision',
                'ch.created_at',
                'ch.monto',
                'ch.monto_recargo',
                'ch.observacion',
                'ch.contract_id',
                'ch.payment_group_uuid',
                'ch.payment_schedule_id',
                'ct.nombre as tipo_cobro',
                'pm.nombre as forma_pago',
                'o.nombre as oficina_recibe',
                DB::raw("TRIM(COALESCE(cl.nombres,'') || ' ' || COALESCE(cl.apellidos,'')) as cliente"),
            ])
            ->get()
            ->map(function ($row) {
                $row->total = round((float) $row->monto + (float) $row->monto_recargo, 2);
                $row->receipt_url = route('cobros.receipt', $row->id);

                return $row;
            });

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }

    public function scheduleCharges(int $scheduleId)
    {
        $schedule = DB::table('payment_schedules')
            ->where('id', $scheduleId)
            ->first();

        if (!$schedule) {
            return response()->json([
                'ok' => false,
                'message' => 'Mensualidad no encontrada.',
                'data' => [],
            ], 404);
        }

        $rows = DB::table('charges as ch')
            ->leftJoin('charge_types as ct', 'ct.id', '=', 'ch.charge_type_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'ch.payment_method_id')
            ->leftJoin('offices as o', 'o.id', '=', 'ch.office_receives_charge_id')
            ->leftJoin('clients as cl', 'cl.id', '=', 'ch.client_id')
            ->where('ch.payment_schedule_id', $scheduleId)
            ->whereNull('ch.fecha_baja')
            ->orderBy('ch.id')
            ->select([
                'ch.id',
                'ch.numero_referencia',
                'ch.fecha_emision',
                'ch.created_at',
                'ch.monto',
                'ch.monto_recargo',
                'ch.observacion',
                'ch.contract_id',
                'ch.payment_group_uuid',
                'ch.payment_schedule_id',
                'ct.nombre as tipo_cobro',
                'pm.nombre as forma_pago',
                'o.nombre as oficina_recibe',
                DB::raw("TRIM(COALESCE(cl.nombres,'') || ' ' || COALESCE(cl.apellidos,'')) as cliente"),
            ])
            ->get()
            ->map(function ($row) {
                $row->total = round((float) $row->monto + (float) $row->monto_recargo, 2);
                $row->receipt_url = route('cobros.receipt', $row->id);

                return $row;
            });

        return response()->json([
            'ok' => true,
            'schedule' => [
                'id' => $schedule->id,
                'installment_number' => $schedule->installment_number,
                'due_date' => $schedule->due_date,
                'amount' => $schedule->amount,
                'amount_paid' => $schedule->amount_paid,
                'late_fee_amount' => $schedule->late_fee_amount,
                'status' => $schedule->status,
            ],
            'data' => $rows,
        ]);
    }

    public function receipt(Request $request, int $id, PdfReceiptService $pdf)
    {
        $includeSchedule = $request->boolean('include_schedule');

        $charge = DB::table('charges as ch')
            ->leftJoin('charge_types as ct', 'ct.id', '=', 'ch.charge_type_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'ch.payment_method_id')
            ->leftJoin('offices as o', 'o.id', '=', 'ch.office_receives_charge_id')
            ->leftJoin('clients as cl', 'cl.id', '=', 'ch.client_id')
            ->leftJoin('users as u', 'u.id', '=', 'ch.usuario_genero_id')
            ->where('ch.id', $id)
            ->select([
                'ch.*',
                'ct.nombre as tipo_cobro',
                'pm.nombre as forma_pago',
                'o.nombre as oficina_recibe',
                DB::raw("TRIM(COALESCE(cl.nombres,'') || ' ' || COALESCE(cl.apellidos,'')) as cliente"),
                'u.alias as usuario',
            ])
            ->first();

        abort_if(!$charge, 404, 'Cobro no encontrado');

        $contract = null;
        $stats = [];
        $scheduleGrid = [];
        $scheduleColumns = [
            'left' => [],
            'right' => [],
        ];

        if (!empty($charge->contract_id)) {
            $contract = DB::table('contracts as c')
                ->leftJoin('statuses as s', 's.id', '=', 'c.status_id')
                ->leftJoin('contract_payment_types as cpt', 'cpt.id', '=', 'c.contract_payment_type_id')
                ->where('c.id', $charge->contract_id)
                ->select([
                    'c.*',
                    's.nombre as estado',
                    'cpt.nombre as tipo_pago',
                ])
                ->first();

            $stats = $pdf->chargePaymentStats((int) $charge->contract_id);

            if ($includeSchedule) {
                $scheduleGrid = $pdf->chargeScheduleGrid((int) $charge->contract_id);
                $scheduleColumns = $pdf->splitGridInTwoColumns($scheduleGrid);
            }
        }

        return $pdf->stream(
            'pdf.receipts.charge',
            [
                'document_type' => 'RECIBO DE COBRO',
                'folio' => $charge->numero_referencia,
                'charge' => $charge,
                'contract' => $contract,
                'stats' => $stats,
                'scheduleGrid' => $scheduleGrid,
                'scheduleColumns' => $scheduleColumns,
                'includeSchedule' => $includeSchedule,
                'emittedAt' => now(),
            ],
            'recibo_' . $charge->numero_referencia . '.pdf'
        );
    }

    protected function officeBelongsToContractLots(int $contractId, int $officeId): bool
    {
        return DB::table('contract_lots as cl')
            ->join('lots as l', 'l.id', '=', 'cl.lot_id')
            ->join('lot_offices as lo', 'lo.lot_id', '=', 'l.id')
            ->where('cl.contract_id', $contractId)
            ->where('lo.office_id', $officeId)
            ->exists();
    }
}