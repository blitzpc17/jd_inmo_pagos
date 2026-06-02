@extends('layouts.app')

@section('title', 'Resumen general de lotificaciones')

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fa-solid fa-layer-group me-2"></i>
                Resumen general de lotificaciones
            </h4>
            <div class="text-muted small">
                Consulta general de lotes agrupados por lotificación y clasificados por estado.
            </div>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label for="start_date" class="form-label fw-semibold">Fecha inicio</label>
                    <input
                        type="text"
                        id="start_date"
                        name="start_date"
                        class="form-control js-date"
                        value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}"
                        autocomplete="off"
                        placeholder="YYYY-MM-DD"
                    >
                </div>

                <div class="col-12 col-md-3">
                    <label for="end_date" class="form-label fw-semibold">Fecha fin</label>
                    <input
                        type="text"
                        id="end_date"
                        name="end_date"
                        class="form-control js-date"
                        value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}"
                        autocomplete="off"
                        placeholder="YYYY-MM-DD"
                    >
                </div>

                <div class="col-12 col-md-6 d-flex flex-wrap gap-2 justify-content-md-end">
                    <button type="button" id="btnSearchReport" class="btn btn-primary">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>
                        Consultar
                    </button>

                    <button type="button" id="btnClearReport" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-eraser me-1"></i>
                        Limpiar
                    </button>

                    <button type="button" id="btnExportReport" class="btn btn-success">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- TOTALES --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 summary-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Total lotes</small>
                            <h5 id="totalLotes" class="mb-0 fw-bold">0</h5>
                        </div>
                        <span class="summary-icon total-icon">
                            <i class="fa-solid fa-border-all"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 summary-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Libres</small>
                            <h5 id="totalLibres" class="mb-0 fw-bold">0</h5>
                        </div>
                        <span class="summary-icon libre-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 summary-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Apartados</small>
                            <h5 id="totalApartados" class="mb-0 fw-bold">0</h5>
                        </div>
                        <span class="summary-icon apartado-icon">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 summary-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Ocupados / Vendidos</small>
                            <h5 id="totalOcupados" class="mb-0 fw-bold">0</h5>
                        </div>
                        <span class="summary-icon ocupado-icon">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 summary-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Liberados</small>
                            <h5 id="totalLiberados" class="mb-0 fw-bold">0</h5>
                        </div>
                        <span class="summary-icon liberado-icon">
                            <i class="fa-solid fa-unlock"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 summary-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Otros</small>
                            <h5 id="totalOtros" class="mb-0 fw-bold">0</h5>
                        </div>
                        <span class="summary-icon otros-icon">
                            <i class="fa-solid fa-ellipsis"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h6 class="mb-0 fw-bold">Detalle por lotificación</h6>
                <small class="text-muted">
                    El rango de fechas consulta lotes actualizados dentro del periodo seleccionado.
                </small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="summaryReportTable" class="table table-striped table-hover table-bordered align-middle w-100">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Lotificación</th>
                            <th class="text-center">Total lotes</th>
                            <th class="text-center">Disponibles</th>
                            <th class="text-center">Apartados</th>
                            <th class="text-center">Vendidos</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Totales:</th>
                            <th id="footerTotalLotes" class="text-center">0</th>
                            <th id="footerLibres" class="text-center estado-libre">0</th>
                            <th id="footerApartados" class="text-center estado-apartado">0</th>
                            <th id="footerOcupados" class="text-center estado-ocupado">0</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
    .summary-total-card {
        border-radius: 16px;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .summary-total-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .08) !important;
    }

    .summary-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 18px;
    }

    .total-icon {
        background: #E2E3E5;
        color: #41464B;
    }

    .libre-icon {
        background: #FFFFFF;
        color: #0D0D0D;
        border: 1px solid #CED4DA;
    }

    .apartado-icon {
        background: #FFF3CD;
        color: #664D03;
    }

    .ocupado-icon {
        background: #F8D7DA;
        color: #842029;
    }

    .liberado-icon {
        background: #D1E7DD;
        color: #0F5132;
    }

    .otros-icon {
        background: #E2E3E5;
        color: #41464B;
    }

    #summaryReportTable thead th {
        white-space: nowrap;
        vertical-align: middle;
    }

    #summaryReportTable tfoot th {
        background: rgba(0, 0, 0, .035);
        font-weight: 700;
        vertical-align: middle;
    }

    .estado-libre {
        background-color: transparent !important;
        color: #0D0D0D !important;
        font-weight: 700;
    }

    .estado-apartado {
        background-color: #FFF3CD !important;
        color: #664D03 !important;
        font-weight: 700;
    }

    .estado-ocupado {
        background-color: #F8D7DA !important;
        color: #842029 !important;
        font-weight: 700;
    }

    .estado-liberado {
        background-color: #D1E7DD !important;
        color: #0F5132 !important;
        font-weight: 700;
    }

    .estado-otros {
        background-color: #E2E3E5 !important;
        color: #41464B !important;
        font-weight: 700;
    }

    .dark-mode .card,
    [data-bs-theme="dark"] .card {
        background-color: #1f2937;
        color: #f9fafb;
    }

    .dark-mode .card-header,
    [data-bs-theme="dark"] .card-header {
        background-color: #1f2937 !important;
        color: #f9fafb;
    }

    .dark-mode .text-muted,
    [data-bs-theme="dark"] .text-muted {
        color: #9ca3af !important;
    }

    .dark-mode #summaryReportTable tfoot th,
    [data-bs-theme="dark"] #summaryReportTable tfoot th {
        background: rgba(255, 255, 255, .08);
        color: #f9fafb;
    }

    .dark-mode .estado-libre,
    [data-bs-theme="dark"] .estado-libre {
        background-color: #FFFFFF !important;
        color: #0D0D0D !important;
    }

    .flatpickr-calendar {
        z-index: 99999 !important;
    }

    @media (max-width: 767.98px) {
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length {
            text-align: left !important;
        }

        #summaryReportTable {
            font-size: 13px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const defaultStartDate = "{{ now()->startOfMonth()->format('Y-m-d') }}";
    const defaultEndDate = "{{ now()->endOfMonth()->format('Y-m-d') }}";

    initDatePickers();

    const table = $('#summaryReportTable').DataTable({
        processing: true,
        serverSide: false,
        responsive: true,
        autoWidth: false,
        ajax: {
            url: "{{ route('lotificaciones.summary.data') }}",
            type: 'GET',
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            },
            dataSrc: function (json) {
                const rows = normalizeRows(json.data || json || []);
                renderTotals(calculateTotals(rows));
                return rows;
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'No se pudo consultar el resumen general.'
                });
            }
        },
        columns: [
            {
                data: null,
                className: 'text-center',
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            {
                data: 'lotificacion',
                defaultContent: '',
                render: function (value) {
                    return value || 'SIN LOTIFICACIÓN';
                }
            },
            {
                data: 'total',
                className: 'text-center fw-bold',
                render: function (value) {
                    return Number(value || 0);
                }
            },
            {
                data: 'disponibles',
                className: 'text-center estado-libre',
                render: function (value) {
                    return Number(value || 0);
                }
            },
            {
                data: 'apartados',
                className: 'text-center estado-apartado',
                render: function (value) {
                    return Number(value || 0);
                }
            },
            {
                data: 'vendidos',
                className: 'text-center estado-ocupado',
                render: function (value) {
                    return Number(value || 0);
                }
            }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-MX.json'
        }
    });

    $('#btnSearchReport').on('click', function () {
        if (!validateDates()) {
            return;
        }

        table.ajax.reload();
    });

    $('#btnClearReport').on('click', function () {
        setDateValue('#start_date', defaultStartDate);
        setDateValue('#end_date', defaultEndDate);

        table.ajax.reload();
    });

    $('#btnExportReport').on('click', function () {
        if (!validateDates()) {
            return;
        }

        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        const url = "{{ route('lotificaciones.summary.export') }}"
            + '?start_date=' + encodeURIComponent(startDate)
            + '&end_date=' + encodeURIComponent(endDate);

        window.location.href = url;
    });

    function normalizeRows(rows) {
        return rows.map(function (row) {
            const disponibles = Number(row.disponibles ?? row.libres ?? 0);
            const apartados = Number(row.apartados ?? 0);
            const vendidos = Number(row.vendidos ?? row.ocupados ?? 0);
            const total = Number(row.total ?? row.total_lotes ?? 0);

            return {
                lotificacion: row.lotificacion,
                total: total,
                disponibles: disponibles,
                apartados: apartados,
                vendidos: vendidos
            };
        });
    }

    function calculateTotals(rows) {
        return rows.reduce(function (acc, row) {
            acc.total += Number(row.total || 0);
            acc.disponibles += Number(row.disponibles || 0);
            acc.apartados += Number(row.apartados || 0);
            acc.vendidos += Number(row.vendidos || 0);

            return acc;
        }, {
            total: 0,
            disponibles: 0,
            apartados: 0,
            vendidos: 0
        });
    }

    function initDatePickers() {
        if (typeof flatpickr !== 'undefined') {
            $('.js-date').flatpickr({
                dateFormat: 'Y-m-d',
                allowInput: true,
                locale: typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.es
                    ? flatpickr.l10ns.es
                    : undefined
            });

            return;
        }

        $('.js-date').attr('type', 'date');
    }

    function setDateValue(selector, value) {
        const input = $(selector);

        if (input[0] && input[0]._flatpickr) {
            input[0]._flatpickr.setDate(value, true, 'Y-m-d');
        } else {
            input.val(value);
        }
    }

    function validateDates() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();

        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Fechas requeridas',
                text: 'Selecciona fecha inicio y fecha fin.'
            });

            return false;
        }

        if (startDate > endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Rango inválido',
                text: 'La fecha inicio no puede ser mayor a la fecha fin.'
            });

            return false;
        }

        return true;
    }

    function renderTotals(totals) {
        const total = Number(totals.total || 0);
        const disponibles = Number(totals.disponibles || 0);
        const apartados = Number(totals.apartados || 0);
        const vendidos = Number(totals.vendidos || 0);

        $('#totalLotes').text(total);
        $('#totalLibres').text(disponibles);
        $('#totalApartados').text(apartados);
        $('#totalOcupados').text(vendidos);

        $('#footerTotalLotes').text(total);
        $('#footerLibres').text(disponibles);
        $('#footerApartados').text(apartados);
        $('#footerOcupados').text(vendidos);
    }
});
</script>
@endpush