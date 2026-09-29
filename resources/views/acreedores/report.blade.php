@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <h3 class="fw-bold mb-1">Reporte de Acreedores</h3>
            <div class="text-muted">
                Resumen general de cuentas por pagar a acreedores, capital e intereses.
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-end gap-2">
            <a href="{{ route('reporteria.acreedores.export') }}" class="btn btn-success" id="btnExport">
                <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
            </a>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100 text-nowrap" id="tblReporteAcreedores">
            <thead>
                <tr>
                    <th class="th-base">ACREEDOR</th>
                    <th class="th-base">DESCRIPCION</th>
                    <th class="th-base text-end">CAPITAL</th>
                    <th class="th-base text-end">ABONO CAPITAL</th>
                    <th class="th-base text-end">RESTO CAPITAL</th>
                    <th class="th-base text-end">MENSUALIDAD CAPITAL</th>
                    <th class="th-base text-end">% INTERES</th>
                    <th class="th-base text-end">INTERES A PAGAR MENSUAL</th>
                    <th class="th-base text-end">INTERES ABONADO</th>
                    <th class="th-base text-end">RESTA INTERES POR ABONAR</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end fw-bold">TOTALES:</th>
                    <th class="text-end fw-bold">0.00</th>
                    <th class="text-end fw-bold">0.00</th>
                    <th class="text-end fw-bold">0.00</th>
                    <th class="text-end fw-bold">0.00</th>
                    <th></th>
                    <th class="text-end fw-bold">0.00</th>
                    <th class="text-end fw-bold">0.00</th>
                    <th class="text-end fw-bold">0.00</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
    #tblReporteAcreedores thead th {
        white-space: nowrap;
        vertical-align: middle;
        background: #a9a9a9 !important;
        color: #fff !important;
    }

    #tblReporteAcreedores tfoot th {
        background: #f1f5f9 !important;
        color: #0f172a !important;
        font-weight: 800;
        border-top: 2px solid #cbd5e1;
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    function moneyRender(data) {
        return Number(data || 0).toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function percentRender(data) {
        return Number(data || 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + '%';
    }

    const table = $('#tblReporteAcreedores').DataTable({
        ajax: {
            url: '{{ route('reporteria.acreedores.data') }}',
            dataSrc: 'data'
        },
        columns: [
            { data: 'acreedor' },
            { data: 'descripcion' },
            { data: 'capital', className: 'text-end', render: moneyRender },
            { data: 'abono_capital', className: 'text-end', render: moneyRender },
            { data: 'resto_capital', className: 'text-end', render: moneyRender },
            { data: 'mensualidad_capital', className: 'text-end', render: moneyRender },
            { data: 'porcentaje_interes', className: 'text-end', render: percentRender },
            { data: 'interes_pagar_mensual', className: 'text-end', render: moneyRender },
            { data: 'interes_abonado', className: 'text-end', render: moneyRender },
            { data: 'resta_interes_por_abonar', className: 'text-end', render: moneyRender },
        ],
        pageLength: 25,
        scrollX: true,
        language: {
            processing: "Procesando...",
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No hay datos disponibles",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            loadingRecords: "Cargando...",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
        },
        footerCallback: function (row, data, start, end, display) {
            const api = this.api();

            const intVal = function (i) {
                if (i === null || i === undefined) return 0;
                return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
            };

            const colsToSum = [2, 3, 4, 5, 7, 8, 9];

            colsToSum.forEach(function(index) {
                const total = api
                    .column(index, { search: 'applied' })
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                $(api.column(index).footer()).html(moneyRender(total));
            });
        }
    });

})();
</script>
@endpush
