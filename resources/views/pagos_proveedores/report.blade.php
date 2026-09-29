@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Reporte de Proveedores</h3>
            <div class="text-muted">Totales de capital, enganches y pagos de proveedores</div>
        </div>

        <a href="{{ url('/reporteria/proveedores/export') }}" class="btn btn-success" id="btnExportar">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100 text-nowrap" id="tblReporteProveedores">
            <thead class="table-light">
                <tr>
                    <th>Proveedor</th>
                    <th>Motivo / Descripción</th>
                    <th>Lotificación</th>
                    <th>Capital (Total)</th>
                    <th>Enganche</th>
                    <th>Abono a Capital</th>
                    <th>Resto a Capital</th>
                    <th>Meses</th>
                    <th>Mensualidad</th>
                </tr>
            </thead>
            <tfoot class="table-light fw-bold">
                <tr>
                    <th colspan="3" class="text-end">Totales:</th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const fCurrency = v => '$ ' + parseFloat(v || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    $('#tblReporteProveedores').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/reporteria/proveedores/data',
            type: 'GET'
        },
        columns: [
            { data: 'proveedor' },
            { data: 'descripcion' },
            { data: 'lotificacion' },
            { data: 'capital', render: v => `<span class="fw-bold">${fCurrency(v)}</span>` },
            { data: 'enganche', render: v => fCurrency(v) },
            { data: 'abono_capital', render: v => `<span class="text-success">${fCurrency(v)}</span>` },
            { data: 'resto_capital', render: v => `<span class="text-danger">${fCurrency(v)}</span>` },
            { data: 'meses' },
            { data: 'mensualidad', render: v => fCurrency(v) }
        ],
        order: [[0, 'asc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
        footerCallback: function (row, data, start, end, display) {
            let api = this.api();
            let intVal = function (i) {
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };
            
            [3, 4, 5, 6, 8].forEach(function(colIndex) {
                let total = api.column(colIndex, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                $(api.column(colIndex).footer()).html(fCurrency(total));
            });
        }
    });
});
</script>
@endpush
