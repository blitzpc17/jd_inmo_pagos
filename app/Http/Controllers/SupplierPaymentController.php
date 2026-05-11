<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\PdfReceiptService;

class SupplierPaymentController extends Controller
{
    public function index()
    {
        return view('pagos_proveedores.index');
    }

    public function datatable()
    {
        $rows = DB::table('supplier_payments as sp')
            ->join('suppliers as s', 's.id', '=', 'sp.supplier_id')
            ->join('payment_methods as pm', 'pm.id', '=', 'sp.payment_method_id')
            ->join('statuses as st', 'st.id', '=', 'sp.status_id')
            ->whereNull('sp.fecha_baja')
            ->select([
                'sp.id',
                'sp.numero_referencia',
                'sp.fecha',
                'sp.importe',
                'sp.observacion',
                's.nombre as proveedor',
                'pm.nombre as forma_pago',
                'st.nombre as estado',
            ])
            ->orderByDesc('sp.id')
            ->get()
            ->map(function ($r) {
                $r->acciones = '
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-info btn-view" data-id="'.$r->id.'" title="Ver detalle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <a class="btn btn-sm btn-outline-danger" target="_blank" href="'.route('pagos_proveedores.receipt', $r->id).'" title="Recibo PDF">
                            <i class="fa-solid fa-file-pdf"></i>
                        </a>
                    </div>
                ';
                return $r;
            });

        return response()->json(['data' => $rows]);
    }

    public function options()
    {
        $suppliers = DB::table('suppliers as s')
            ->join('statuses as st', 'st.id', '=', 's.status_id')
            ->join('processes as p', 'p.id', '=', 'st.process_id')
            ->where('p.clave', 'GENERAL')
            ->where('st.clave', 'ACTIVE')
            ->whereNull('s.fecha_baja')
            ->orderBy('s.nombre')
            ->get([
                's.id as value',
                's.nombre as text',
            ]);

        $paymentMethods = DB::table('payment_methods')
            ->orderBy('nombre')
            ->get([
                'id as value',
                'nombre as text',
            ]);

        return response()->json([
            'suppliers' => $suppliers,
            'payment_methods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $data = Validator::make($request->all(), [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'fecha' => ['required', 'date'],
            'observacion' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.concepto' => ['required', 'string'],
            'items.*.importe' => ['required', 'numeric', 'min:0.01'],
        ], [
            'items.required' => 'Debes capturar al menos un concepto.',
        ])->validate();

        $statusId = $this->getActiveStatusId();
        $total = collect($data['items'])->sum(fn ($x) => (float) $x['importe']);

        DB::beginTransaction();

        try {
            $paymentId = DB::table('supplier_payments')->insertGetId([
                'numero_referencia' => '',
                'fecha' => $data['fecha'],
                'importe' => $total,
                'supplier_id' => $data['supplier_id'],
                'payment_method_id' => $data['payment_method_id'],
                'status_id' => $statusId,
                'observacion' => $data['observacion'] ?? null,
                'usuario_genero_id' => session('auth_user.id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('supplier_payments')
                ->where('id', $paymentId)
                ->update([
                    'numero_referencia' => 'PPR-' . str_pad((string) $paymentId, 6, '0', STR_PAD_LEFT),
                    'updated_at' => now(),
                ]);

            $rows = [];
            foreach ($data['items'] as $item) {
                $rows[] = [
                    'supplier_payment_id' => $paymentId,
                    'concepto' => $item['concepto'],
                    'importe' => $item['importe'],
                    'status_id' => $statusId,
                    'usuario_genero_id' => session('auth_user.id'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('supplier_payment_concepts')->insert($rows);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Pago a proveedor registrado correctamente.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(int $id)
    {
        $row = DB::table('supplier_payments as sp')
            ->join('suppliers as s', 's.id', '=', 'sp.supplier_id')
            ->join('payment_methods as pm', 'pm.id', '=', 'sp.payment_method_id')
            ->join('statuses as st', 'st.id', '=', 'sp.status_id')
            ->where('sp.id', $id)
            ->select([
                'sp.*',
                's.nombre as proveedor',
                'pm.nombre as forma_pago',
                'st.nombre as estado',
            ])
            ->first();

        abort_if(!$row, 404, 'Pago a proveedor no encontrado');

        $items = DB::table('supplier_payment_concepts')
            ->where('supplier_payment_id', $id)
            ->orderBy('id')
            ->get([
                'concepto',
                'importe',
            ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'numero_referencia' => $row->numero_referencia,
                'fecha' => $row->fecha,
                'importe' => $row->importe,
                'observacion' => $row->observacion,
                'proveedor' => $row->proveedor,
                'forma_pago' => $row->forma_pago,
                'estado' => $row->estado,
                'items' => $items,
            ]
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

    public function receipt(int $id, PdfReceiptService $pdf)
    {
        $payment = DB::table('supplier_payments as sp')
            ->join('suppliers as s', 's.id', '=', 'sp.supplier_id')
            ->join('payment_methods as pm', 'pm.id', '=', 'sp.payment_method_id')
            ->join('statuses as st', 'st.id', '=', 'sp.status_id')
            ->where('sp.id', $id)
            ->select([
                'sp.*',
                's.nombre as proveedor',
                'pm.nombre as forma_pago',
                'st.nombre as estado',
            ])
            ->first();

        abort_if(!$payment, 404, 'Pago proveedor no encontrado');

        $items = DB::table('supplier_payment_concepts')
            ->where('supplier_payment_id', $id)
            ->orderBy('id')
            ->get([
                'concepto',
                'importe',
            ]);

        return $pdf->stream(
            'pdf.receipts.supplier_payment',
            [
                'title' => 'RECIBO PAGO PROVEEDOR',
                'payment' => $payment,
                'items' => $items,
            ],
            'recibo-pago-proveedor-'.$payment->numero_referencia.'.pdf'
        );
    }
}