<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReservationComplementController extends Controller
{
    public function index()
    {
        return view('apartados_complementos.index');
    }

    public function datatable()
    {
        $rows = DB::table('reservation_complements as rc')
            ->join('reservations as r', 'r.id', '=', 'rc.reservation_id')
            ->join('charges as c', 'c.id', '=', 'rc.charge_id')
            ->join('clients as cl', 'cl.id', '=', 'r.client_id')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->join('charge_types as ct', 'ct.id', '=', 'c.charge_type_id')
            ->join('payment_methods as pm', 'pm.id', '=', 'c.payment_method_id')
            ->select([
                'rc.id',
                'r.numero_referencia as apartado_referencia',
                'c.numero_referencia as cobro_referencia',
                'c.fecha_emision',
                'c.monto',
                'c.observacion',
                'ct.nombre as tipo_cobro',
                'pm.nombre as forma_pago',
                'cl.nombres',
                'cl.apellidos',
                'd.nombre as lotificacion',
            ])
            ->orderByDesc('rc.id')
            ->get()
            ->map(function ($r) {
                $r->cliente = trim(($r->nombres ?? '') . ' ' . ($r->apellidos ?? ''));
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options()
    {
        $reservationStatusId = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'RESERVATION_STATUS')
            ->where('s.clave', 'VIGENTE')
            ->value('s.id');

        $reservations = DB::table('reservations as r')
            ->join('clients as c', 'c.id', '=', 'r.client_id')
            ->join('developments as d', 'd.id', '=', 'r.development_id')
            ->where('r.status_id', $reservationStatusId)
            ->whereNull('r.fecha_baja')
            ->orderByDesc('r.id')
            ->get([
                'r.id as value',
                DB::raw("
                    r.numero_referencia || ' - ' ||
                    c.nombres || ' ' || c.apellidos || ' - ' ||
                    d.nombre as text
                "),
            ]);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        $chargeTypes = DB::table('charge_types')
            ->whereIn('nombre', ['Complemento de apartado', 'Enganche', 'Liquidación contado', 'Otro'])
            ->orderBy('nombre')
            ->get(['id as value', 'nombre as text']);

        return response()->json([
            'reservations' => $reservations,
            'payment_methods' => $paymentMethods,
            'charge_types' => $chargeTypes,
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'reservation_id' => ['required', 'integer', 'exists:reservations,id'],
            'charge_type_id' => ['required', 'integer', 'exists:charge_types,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'observacion' => ['nullable', 'string'],
        ])->validate();

        $reservation = DB::table('reservations')->where('id', $data['reservation_id'])->first();
        abort_if(!$reservation, 404, 'Apartado no encontrado');

        $reservationVigenteId = $this->getReservationStatusId('VIGENTE');
        if ((int) $reservation->status_id !== (int) $reservationVigenteId) {
            return response()->json([
                'message' => 'Solo se pueden registrar complementos a apartados vigentes.'
            ], 422);
        }

        $chargeStatusId = $this->getChargeStatusId('REGISTRADO');
        $clientId = $reservation->client_id;

        DB::beginTransaction();

        try {
            $chargeId = DB::table('charges')->insertGetId([
                'numero_referencia' => '',
                'fecha_emision' => now()->toDateString(),
                'charge_type_id' => $data['charge_type_id'],
                'payment_method_id' => $data['payment_method_id'],
                'client_id' => $clientId,
                'contract_id' => null,
                'reservation_id' => $reservation->id,
                'status_id' => $chargeStatusId,
                'monto' => $data['monto'],
                'aplica_recargo' => false,
                'monto_recargo' => 0,
                'observacion' => $data['observacion'] ?? null,
                'usuario_genero_id' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('charges')
                ->where('id', $chargeId)
                ->update([
                    'numero_referencia' => 'COB-' . str_pad((string) $chargeId, 6, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);

            DB::table('reservation_complements')->insert([
                'reservation_id' => $reservation->id,
                'charge_id' => $chargeId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Complemento registrado correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getReservationStatusId(string $clave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'RESERVATION_STATUS')
            ->where('s.clave', $clave)
            ->value('s.id');

        if (!$id) {
            abort(500, "No existe el estado {$clave} para RESERVATION_STATUS.");
        }

        return (int) $id;
    }

    protected function getChargeStatusId(string $clave): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'CHARGE_STATUS')
            ->where('s.clave', $clave)
            ->value('s.id');

        if (!$id) {
            abort(500, "No existe el estado {$clave} para CHARGE_STATUS.");
        }

        return (int) $id;
    }
}