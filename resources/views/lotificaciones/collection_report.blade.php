@extends('layouts.app')

@section('title', 'Reporte de cobranza por lotificación')

@section('content')
<div class="container-fluid py-3">

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fa-solid fa-chart-column me-2"></i>
                Reporte de cobranza por lotificación
            </h4>
            <div class="text-muted small">
                Consulta de contratos, enganches, cobrado, resto por cobrar e ingreso mensual agrupado por lotificación.
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
            <div class="card shadow-sm border-0 h-100 report-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Contratos</small>
                            <h5 id="totalContratos" class="mb-0 fw-bold">$0.00</h5>
                        </div>
                        <span class="report-icon bg-primary-subtle text-primary">
                            <i class="fa-solid fa-file-signature"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 report-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Enganches</small>
                            <h5 id="totalEnganches" class="mb-0 fw-bold">$0.00</h5>
                        </div>
                        <span class="report-icon bg-info-subtle text-info">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 report-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Cobrado</small>
                            <h5 id="totalCobrado" class="mb-0 fw-bold">$0.00</h5>
                        </div>
                        <span class="report-icon bg-success-subtle text-success">
                            <i class="fa-solid fa-circle-dollar-to-slot"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 report-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Resto por cobrar</small>
                            <h5 id="totalRestoPorCobrar" class="mb-0 fw-bold">$0.00</h5>
                        </div>
                        <span class="report-icon bg-warning-subtle text-warning">
                            <i class="fa-solid fa-clock"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl">
            <div class="card shadow-sm border-0 h-100 report-total-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <small class="text-muted d-block">Ingreso mensual</small>
                            <h5 id="totalIngresoMensual" class="mb-0 fw-bold">$0.00</h5>
                        </div>
                        <span class="report-icon bg-secondary-subtle text-secondary">
                            <i class="fa-solid fa-calendar-days"></i>
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
                    El rango de fechas afecta contratos, cobros y calendario de pagos según la lógica del módulo de cobranza.
                </small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="collectionReportTable" class="table table-striped table-hover table-bordered align-middle w-100">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Lotificación</th>
                            <th class="text-end">Contratos</th>
                            <th class="text-end">Enganches</th>
                            <th class="text-end">Cobrado</th>
                            <th class="text-end">Resto por cobrar</th>
                            <th class="text-end">Ingreso mensual</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Totales:</th>
                            <th id="footerContratos" class="text-end">$0.00</th>
                            <th id="footerEnganches" class="text-end">$0.00</th>
                            <th id="footerCobrado" class="text-end">$0.00</th>
                            <th id="footerRestoPorCobrar" class="text-end">$0.00</th>
                            <th id="footerIngresoMensual" class="text-end">$0.00</th>
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
    .report-total-card {
        border-radius: 16px;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .report-total-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .08) !important;
    }

    .report-icon {
        width: 42px;
        height: 42px;
        min-width: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        font-size: 18px;
    }

    #collectionReportTable thead th {
        white-space: nowrap;
        vertical-align: middle;
    }

    #collectionReportTable tfoot th {
        background: rgba(0, 0, 0, .035);
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

    .dark-mode #collectionReportTable tfoot th,
    [data-bs-theme="dark"] #collectionReportTable tfoot th {
        background: rgba(255, 255, 255, .08);
        color: #f9fafb;
    }

    .flatpickr-calendar {
        z-index: 99999 !important;
    }

    @media (max-width: 767.98px) {
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_length {
            text-align: left !important;
        }

        #collectionReportTable {
            font-size: 13px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const money = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    });

    const defaultStartDate = "{{ now()->startOfMonth()->format('Y-m-d') }}";
    const defaultEndDate = "{{ now()->endOfMonth()->format('Y-m-d') }}";

    initDatePickers();

    const table = $('#collectionReportTable').DataTable({
        processing: true,
        serverSide: false,
        responsive: true,
        autoWidth: false,
        ajax: {
            url: "{{ route('lotificaciones.collection_report.data') }}",
            type: 'GET',
            data: function (d) {
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            },
            dataSrc: function (json) {
                renderTotals(json.totals || {});
                return json.data || [];
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'No se pudo consultar el reporte de cobranza.';

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message
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
                data: 'contratos',
                className: 'text-end',
                render: function (value) {
                    return money.format(Number(value || 0));
                }
            },
            {
                data: 'enganches',
                className: 'text-end',
                render: function (value) {
                    return money.format(Number(value || 0));
                }
            },
            {
                data: 'cobrado',
                className: 'text-end',
                render: function (value) {
                    return money.format(Number(value || 0));
                }
            },
            {
                data: 'resto_por_cobrar',
                className: 'text-end',
                render: function (value) {
                    return money.format(Number(value || 0));
                }
            },
            {
                data: 'ingreso_mensual',
                className: 'text-end',
                render: function (value) {
                    return money.format(Number(value || 0));
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

        const url = "{{ route('lotificaciones.collection_report.export') }}"
            + '?start_date=' + encodeURIComponent(startDate)
            + '&end_date=' + encodeURIComponent(endDate);

        window.location.href = url;
    });

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
        const contratos = Number(totals.contratos || 0);
        const enganches = Number(totals.enganches || 0);
        const cobrado = Number(totals.cobrado || 0);
        const restoPorCobrar = Number(totals.resto_por_cobrar || 0);
        const ingresoMensual = Number(totals.ingreso_mensual || 0);

        $('#totalContratos').text(money.format(contratos));
        $('#totalEnganches').text(money.format(enganches));
        $('#totalCobrado').text(money.format(cobrado));
        $('#totalRestoPorCobrar').text(money.format(restoPorCobrar));
        $('#totalIngresoMensual').text(money.format(ingresoMensual));

        $('#footerContratos').text(money.format(contratos));
        $('#footerEnganches').text(money.format(enganches));
        $('#footerCobrado').text(money.format(cobrado));
        $('#footerRestoPorCobrar').text(money.format(restoPorCobrar));
        $('#footerIngresoMensual').text(money.format(ingresoMensual));
    }
});
</script>
@endpush