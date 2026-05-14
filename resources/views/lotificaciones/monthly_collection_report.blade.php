@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <h3 class="fw-bold mb-1">Reporte mensual de cobros</h3>
            <div class="text-muted">
                Contratos con cobros del mes y apartados generados dentro del mes/año seleccionado.
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-end gap-2">
            <div>
                <label class="form-label mb-1">Mes</label>
                <select class="form-select" id="filterMonth" style="min-width:150px">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" @selected($num === $currentMonth)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="form-label mb-1">Año</label>
                <input type="number"
                       class="form-control"
                       id="filterYear"
                       value="{{ $currentYear }}"
                       min="2000"
                       max="2100"
                       style="width:110px">
            </div>

            <button class="btn btn-primary" id="btnSearch">
                <i class="fa-solid fa-magnifying-glass me-1"></i> Consultar
            </button>

            <a href="#" class="btn btn-success" id="btnExport">
                <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
            </a>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblReporteCobrosMensuales">
            <thead>
                <tr>
                    <th class="th-base">OFICINA</th>
                    <th class="th-base">LOTIFICACION</th>
                    <th class="th-base">LOTE</th>
                    <th class="th-base">NOMBRE DEL CLIENTE</th>
                    <th class="th-base">NUM</th>
                    <th class="th-money text-end">MENSUALIDAD</th>
                    <th class="th-paid text-end" id="thRealPagado">REAL PAGADO</th>
                    <th class="th-down text-end">APARTADOS/ENGANCHES</th>
                    <th class="th-fee text-end">COBRO DE RECARGO</th>
                    <th class="th-base">FOLIO</th>
                    <th class="th-base">OBSERVACION</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
    #tblReporteCobrosMensuales thead th {
        white-space: nowrap;
        vertical-align: middle;
    }

    #tblReporteCobrosMensuales thead th.th-base {
        background: #676767 !important;
        color: #fff !important;
    }

    #tblReporteCobrosMensuales thead th.th-money,
    #tblReporteCobrosMensuales thead th.th-paid {
        background: #0511F2 !important;
        color: #fff !important;
    }

    #tblReporteCobrosMensuales thead th.th-down {
        background: #FFD54F !important;
        color: #0D0D0D !important;
    }

    #tblReporteCobrosMensuales thead th.th-fee {
        background: #D9042B !important;
        color: #fff !important;
    }

    #tblReporteCobrosMensuales tbody td.col-money,
    #tblReporteCobrosMensuales tbody td.col-paid {
        background: rgba(5, 17, 242, .08);
        font-weight: 700;
    }

    #tblReporteCobrosMensuales tbody td.col-down {
        background: rgba(255, 213, 79, .22);
        font-weight: 700;
    }

    #tblReporteCobrosMensuales tbody td.col-fee {
        background: rgba(217, 4, 43, .10);
        font-weight: 700;
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const monthNames = @json($months);

    function moneyRender(data) {
        return Number(data || 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function params() {
        return {
            month: $('#filterMonth').val(),
            year: $('#filterYear').val()
        };
    }

    function exportUrl() {
        const p = params();

        return '{{ route('lotificaciones.monthly_collection_report.export') }}' +
            '?month=' + encodeURIComponent(p.month) +
            '&year=' + encodeURIComponent(p.year);
    }

    function updateHeader() {
        const month = $('#filterMonth').val();

        $('#thRealPagado').text('REAL PAGADO ' + (monthNames[month] || ''));
        $('#btnExport').attr('href', exportUrl());
    }

    const table = $('#tblReporteCobrosMensuales').DataTable({
        ajax: {
            url: '{{ route('lotificaciones.monthly_collection_report.data') }}',
            data: function (d) {
                return Object.assign(d, params());
            },
            dataSrc: function (json) {
                if (json.meta && json.meta.real_paid_heading) {
                    $('#thRealPagado').text(json.meta.real_paid_heading);
                }

                return json.data || [];
            }
        },
        columns: [
            { data: 'oficina' },
            { data: 'lotificacion' },
            { data: 'lote' },
            { data: 'nombre_cliente' },
            { data: 'num' },
            { data: 'mensualidad', className: 'text-end col-money', render: moneyRender },
            { data: 'real_pagado', className: 'text-end col-paid', render: moneyRender },
            { data: 'apartados_enganches', className: 'text-end col-down', render: moneyRender },
            { data: 'cobro_recargo', className: 'text-end col-fee', render: moneyRender },
            { data: 'folio' },
            { data: 'observacion' }
        ],
        pageLength: 25,
        order: [[1, 'asc'], [3, 'asc']],
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
        }
    });

    $('#btnSearch').on('click', function () {
        updateHeader();
        table.ajax.reload();
    });

    $('#filterMonth, #filterYear').on('change keyup', updateHeader);

    updateHeader();
})();
</script>
@endpush