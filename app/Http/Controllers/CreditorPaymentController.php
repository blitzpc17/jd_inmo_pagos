<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PdfReceiptService;

class CreditorPaymentController extends Controller
{
    public function index()
    {
        return view('pagos_acreedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('creditor_payments as sp')
            ->join('creditors as c', 'c.id', '=', 'sp.creditor_id')
            ->leftJoin('developments as d', 'd.id', '=', 'sp.development_id')
            ->join('statuses as st', 'st.id', '=', 'sp.status_id')
            ->whereNull('sp.fecha_baja')
            ->select([
                'sp.id',
                'sp.numero_referencia',
                'sp.fecha_inicio',
                'sp.fecha_fin',
                'sp.importe',
                'sp.enganche',
                'sp.plazo',
                'sp.observacion',
                'c.nombre as acreedor',
                'd.nombre as lotificacion',
                'st.nombre as estado',
            ])
            ->orderByDesc('sp.id')
            ->get()
            ->map(function ($r) {
                $abonos = DB::table('creditor_payment_concepts')
                    ->where('creditor_payment_id', $r->id)
                    ->whereNull('fecha_baja')
                    ->sum('importe');

                $r->abonos = $abonos;
                $r->resto = max(0, ($r->importe - $r->enganche) - $abonos);

                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-info btn-view" data-id="'.$r->id.'" title="Ver detalle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                ';
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options()
    {
        $suppliers = DB::table('creditors as c')
            ->join('statuses as st', 'st.id', '=', 'c.status_id')
            ->join('processes as p', 'p.id', '=', 'st.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('st.clave', 'ACTIVE')
            ->whereNull('c.fecha_baja')
            ->orderBy('c.nombre')
            ->get([
                'c.id as value',
                'c.nombre as text'
            ]);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get([
                'id as value',
                'nombre as text',
            ]);

        $developments = DB::table('developments')
            ->whereNull('fecha_baja')
            ->orderBy('nombre')
            ->get([
                'id as value',
                'nombre as text',
            ]);

        return response()->json([
            'creditors' => $suppliers,
            'payment_methods' => $paymentMethods,
            'developments' => $developments,
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'creditor_id' => ['required', 'integer', 'exists:creditors,id'],
            'development_id' => ['required', 'integer', 'exists:developments,id'],
            'plazo' => ['required', 'integer', 'min:1'],
            'fecha_inicio' => ['required', 'date'],
            'enganche' => ['required', 'numeric', 'min:0'],
            'importe' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string'],
        ])->validate();

        $statusId = $this->getActiveStatusId();
        $fechaFin = \Carbon\Carbon::parse($data['fecha_inicio'])->addMonths($data['plazo'])->toDateString();

        DB::beginTransaction();

        try {
            $paymentId = DB::table('creditor_payments')->insertGetId([
                'numero_referencia' => '',
                'creditor_id' => $data['creditor_id'],
                'development_id' => $data['development_id'],
                'plazo' => $data['plazo'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $fechaFin,
                'enganche' => $data['enganche'],
                'importe' => $data['importe'],
                'status_id' => $statusId,
                'observacion' => $data['observacion'] ?? null,
                'usuario_genero_id' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('creditor_payments')
                ->where('id', $paymentId)
                ->update([
                    'numero_referencia' => 'BOL-ACR-' . str_pad((string) $paymentId, 5, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Boleta registrada correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(int $id)
    {
        $row = DB::table('creditor_payments as sp')
            ->join('creditors as c', 'c.id', '=', 'sp.creditor_id')
            ->leftJoin('developments as d', 'd.id', '=', 'sp.development_id')
            ->join('statuses as st', 'st.id', '=', 'sp.status_id')
            ->where('sp.id', $id)
            ->select([
                'sp.*',
                'c.nombre as acreedor',
                'd.nombre as lotificacion',
                'st.nombre as estado',
            ])
            ->first();

        abort_if(!$row, 404, 'Boleta no encontrada');

        $items = DB::table('creditor_payment_concepts')
            ->where('creditor_payment_id', $id)
            ->orderBy('fecha')
            ->get([
                'id',
                'fecha',
                'concepto',
                'importe',
            ]);

        $abonos = collect($items)->sum('importe');
        $resto = max(0, ($row->importe - $row->enganche) - $abonos);

        return response()->json([
            'ok' => true,
            'data' => [
                'numero_referencia' => $row->numero_referencia,
                'fecha_inicio' => $row->fecha_inicio,
                'fecha_fin' => $row->fecha_fin,
                'plazo' => $row->plazo,
                'enganche' => $row->enganche,
                'importe' => $row->importe,
                'abonos' => $abonos,
                'resto' => $resto,
                'observacion' => $row->observacion,
                'acreedor' => $row->acreedor,
                'lotificacion' => $row->lotificacion,
                'estado' => $row->estado,
                'items' => $items,
            ]
        ]);
    }

    public function addAbono(Request $request, int $id)
    {
        $data = Validator::make($request->all(), [
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'concepto' => ['required', 'string'],
        ])->validate();

        $boleta = DB::table('creditor_payments')->where('id', $id)->first();
        abort_if(!$boleta, 404, 'Boleta no encontrada');

        DB::table('creditor_payment_concepts')->insert([
            'creditor_payment_id' => $id,
            'fecha' => $data['fecha'],
            'importe' => $data['monto'],
            'payment_method_id' => $data['payment_method_id'],
            'concepto' => $data['concepto'],
            'status_id' => $this->getActiveStatusId(),
            'usuario_genero_id' => session('auth_user.id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Abono registrado correctamente.',
        ]);
    }

    protected function getActiveStatusId(): int
    {
        $id = DB::table('statuses as s')
            ->join('processes as p', 'p.id', '=', 's.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('s.clave', 'ACTIVE')
            ->value('s.id');

        if (!$id) {
            abort(500, 'No existe estado ACTIVE para GENERAL.');
        }

        return (int) $id;
    }

    public function pdfBoleta(int $id, PdfReceiptService $pdf)
    {
        $voucher = DB::table('creditor_payments as cp')
            ->join('creditors as c', 'c.id', '=', 'cp.creditor_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'cp.payment_method_id')
            ->join('statuses as st', 'st.id', '=', 'cp.status_id')
            ->where('cp.id', $id)
            ->select([
                'cp.*',
                'c.nombre as acreedor',
                'pm.nombre as forma_pago',
                'st.nombre as estado_pago',
            ])
            ->first();

        abort_if(!$voucher, 404, 'Boleta no encontrada');

        $items = DB::table('creditor_payment_concepts')
            ->where('creditor_payment_id', $id)
            ->orderBy('id')
            ->get();

        $totalAbonos = 0;
        foreach ($items as $idx => $row) {
            $totalAbonos += $row->importe;
        }

        $stats = [
            'total_payments' => count($items),
            'paid_payments' => count($items),
            'pending_payments' => 0,
        ];

        return $pdf->stream(
            'pdf.receipts.creditor_boleta',
            [
                'document_type' => 'BOLETA DE PAGO A ACREEDOR',
                'folio' => $voucher->numero_referencia,
                'voucher' => $voucher,
                'items' => $items,
                'totalAbonos' => $totalAbonos,
                'stats' => $stats,
            ],
            'boleta-acreedor-'.$voucher->numero_referencia.'.pdf'
        );
    }

    public function pdfRecibo(int $id, int $abonoId, PdfReceiptService $pdf)
    {
        $voucher = DB::table('creditor_payments as cp')
            ->join('creditors as c', 'c.id', '=', 'cp.creditor_id')
            ->where('cp.id', $id)
            ->select([
                'cp.*',
                'c.nombre as acreedor',
            ])
            ->first();

        abort_if(!$voucher, 404, 'Boleta no encontrada');

        $item = DB::table('creditor_payment_concepts')
            ->where('id', $abonoId)
            ->where('creditor_payment_id', $id)
            ->first();

        abort_if(!$item, 404, 'Abono no encontrado');

        $stats = [
            'total_payments' => DB::table('creditor_payment_concepts')->where('creditor_payment_id', $id)->count(),
            'paid_payments' => DB::table('creditor_payment_concepts')->where('creditor_payment_id', $id)->count(),
            'pending_payments' => 0,
        ];

        return $pdf->stream(
            'pdf.receipts.creditor_recibo',
            [
                'document_type' => 'RECIBO DE ABONO A ACREEDOR',
                'folio' => 'REC-ACR-' . str_pad((string) $item->id, 6, '0', STR_PAD_LEFT),
                'voucher' => $voucher,
                'item' => $item,
                'stats' => $stats,
            ],
            'recibo-acreedor-'.$item->id.'.pdf'
        );
    }
}