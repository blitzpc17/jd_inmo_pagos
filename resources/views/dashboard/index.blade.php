@extends('layouts.app')

@section('content')
<div class="jd-dashboard">
    <div class="jd-hero mb-4">
        <div>
            <h1 class="jd-title">Dashboard</h1>
            <p class="jd-subtitle mb-0">Resumen operativo y financiero del sistema</p>
        </div>

        <div class="jd-filter-card">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label jd-label">Desde</label>
                    <input type="datetime-local" id="filterFrom" class="form-control jd-input" value="{{ $from }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label jd-label">Hasta</label>
                    <input type="datetime-local" id="filterTo" class="form-control jd-input" value="{{ $to }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn jd-btn-primary" id="btnLoadStats">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Cargar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="jd-stat-card">
                <div class="jd-stat-icon bg-danger-soft"><i class="fa-solid fa-file-contract"></i></div>
                <div>
                    <div class="jd-stat-label">Contratos registrados</div>
                    <div class="jd-stat-value" id="cardContracts">{{ $initialStats['cards']['contracts'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="jd-stat-card">
                <div class="jd-stat-icon bg-blue-soft"><i class="fa-solid fa-money-check-dollar"></i></div>
                <div>
                    <div class="jd-stat-label">Pagos a proveedores</div>
                    <div class="jd-stat-value" id="cardSupplierPayments">{{ $initialStats['cards']['supplier_payments'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="jd-stat-card">
                <div class="jd-stat-icon bg-dark-soft"><i class="fa-solid fa-users"></i></div>
                <div>
                    <div class="jd-stat-label">Clientes</div>
                    <div class="jd-stat-value" id="cardClients">{{ $initialStats['cards']['clients'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="jd-stat-card">
                <div class="jd-stat-icon bg-gray-soft"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                <div>
                    <div class="jd-stat-label">Acreedores</div>
                    <div class="jd-stat-value" id="cardCreditors">{{ $initialStats['cards']['creditors'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="jd-stat-card">
                <div class="jd-stat-icon bg-red-soft"><i class="fa-solid fa-cash-register"></i></div>
                <div>
                    <div class="jd-stat-label">Pagos recibidos en rango</div>
                    <div class="jd-stat-value" id="cardChargesToday">{{ $initialStats['cards']['charges_today_count'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="jd-stat-card">
                <div class="jd-stat-icon bg-blue-soft"><i class="fa-solid fa-sack-dollar"></i></div>
                <div>
                    <div class="jd-stat-label">Monto recibido en rango</div>
                    <div class="jd-stat-value" id="cardChargesTodayAmount">${{ number_format($initialStats['cards']['charges_today_amount'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="jd-chart-card">
                <div class="jd-card-head">
                    <h5 class="mb-0">Cobros por tipo</h5>
                </div>
                <div class="jd-chart-wrap jd-chart-wrap-md">
                    <canvas id="chartChargesByType"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="jd-chart-card">
                <div class="jd-card-head">
                    <h5 class="mb-0">Cobros por forma de pago</h5>
                </div>
                <div class="jd-chart-wrap jd-chart-wrap-md">
                    <canvas id="chartPaymentsByMethod"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="jd-chart-card">
                <div class="jd-card-head">
                    <h5 class="mb-0">Cobros por día en el rango</h5>
                </div>
                <div class="jd-chart-wrap jd-chart-wrap-lg">
                    <canvas id="chartChargesPerDay"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .jd-dashboard{padding:4px 2px;}
    .jd-hero{display:grid;grid-template-columns:1fr;gap:16px;}
    .jd-title{font-size:2rem;font-weight:800;color:#0D0D0D;margin:0;}
    .jd-subtitle{color:#676767;font-size:.98rem;}

    .jd-filter-card,
    .jd-stat-card,
    .jd-chart-card{
        background:#fff;
        border:1px solid rgba(103,103,103,.18);
        border-radius:22px;
        box-shadow:0 10px 30px rgba(13,13,13,.06);
    }

    .jd-filter-card{padding:16px;}
    .jd-chart-card{padding:16px 18px;}

    .jd-card-head{
        display:flex;
        justify-content:space-between;
        align-items:center;
        margin-bottom:12px;
    }

    .jd-chart-wrap{
        position:relative;
        width:100%;
        overflow:hidden;
    }

    .jd-chart-wrap-md{
        height:280px;
        max-height:280px;
    }

    .jd-chart-wrap-lg{
        height:320px;
        max-height:320px;
    }

    .jd-chart-wrap canvas{
        width:100% !important;
        height:100% !important;
        display:block;
    }

    .jd-label{color:#676767;font-weight:700;font-size:.88rem;}
    .jd-input{
        border-radius:14px;
        border:1px solid rgba(103,103,103,.22);
        min-height:44px;
    }
    .jd-input:focus{
        border-color:#0511F2;
        box-shadow:0 0 0 .2rem rgba(5,17,242,.12);
    }

    .jd-btn-primary{
        background:#D9042B;
        color:#fff;
        border:none;
        border-radius:14px;
        min-height:44px;
        font-weight:700;
    }
    .jd-btn-primary:hover{background:#F20505;color:#fff;}

    .jd-stat-card{
        padding:18px;
        display:flex;
        gap:14px;
        align-items:center;
        min-height:108px;
    }

    .jd-stat-icon{
        width:58px;
        height:58px;
        border-radius:18px;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:1.35rem;
        flex-shrink:0;
    }

    .jd-stat-label{
        color:#676767;
        font-weight:700;
        font-size:.92rem;
    }

    .jd-stat-value{
        color:#0D0D0D;
        font-size:1.8rem;
        font-weight:800;
        line-height:1.1;
        margin-top:4px;
    }

    .bg-danger-soft{background:rgba(217,4,43,.10);color:#D9042B;}
    .bg-blue-soft{background:rgba(5,17,242,.10);color:#0511F2;}
    .bg-dark-soft{background:rgba(13,13,13,.08);color:#0D0D0D;}
    .bg-gray-soft{background:rgba(103,103,103,.12);color:#676767;}
    .bg-red-soft{background:rgba(242,5,5,.10);color:#F20505;}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {
    const initial = @json($initialStats);

    let chartChargesByType = null;
    let chartPaymentsByMethod = null;
    let chartChargesPerDay = null;

    function money(n){
        return '$' + Number(n || 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function renderCards(data){
        $('#cardContracts').text(data.cards.contracts);
        $('#cardSupplierPayments').text(data.cards.supplier_payments);
        $('#cardClients').text(data.cards.clients);
        $('#cardCreditors').text(data.cards.creditors);
        $('#cardChargesToday').text(data.cards.charges_today_count);
        $('#cardChargesTodayAmount').text(money(data.cards.charges_today_amount));
    }

    function buildBarChart(el, labels, values, label){
        const ctx = document.getElementById(el).getContext('2d');

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label,
                    data: values,
                    borderWidth: 0,
                    borderRadius: 10,
                    maxBarThickness: 42
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false }
                },
                layout: {
                    padding: { top: 6, right: 8, bottom: 4, left: 4 }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    function buildLineChart(el, labels, values, label){
        const ctx = document.getElementById(el).getContext('2d');

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label,
                    data: values,
                    tension: 0.3,
                    fill: false,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { display: false }
                },
                layout: {
                    padding: { top: 6, right: 8, bottom: 4, left: 4 }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    function renderCharts(data){
        if (chartChargesByType) chartChargesByType.destroy();
        if (chartPaymentsByMethod) chartPaymentsByMethod.destroy();
        if (chartChargesPerDay) chartChargesPerDay.destroy();

        chartChargesByType = buildBarChart(
            'chartChargesByType',
            (data.charts.charges_by_type || []).map(x => x.label),
            (data.charts.charges_by_type || []).map(x => Number(x.total)),
            'Cobros por tipo'
        );

        chartPaymentsByMethod = buildBarChart(
            'chartPaymentsByMethod',
            (data.charts.payments_by_method || []).map(x => x.label),
            (data.charts.payments_by_method || []).map(x => Number(x.total)),
            'Cobros por forma'
        );

        chartChargesPerDay = buildLineChart(
            'chartChargesPerDay',
            (data.charts.charges_per_day || []).map(x => x.label),
            (data.charts.charges_per_day || []).map(x => Number(x.total)),
            'Cobros por día'
        );
    }

    async function loadStats(){
        try {
            const from = $('#filterFrom').val();
            const to = $('#filterTo').val();

            const res = await fetch(`{{ route('dashboard.stats') }}?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`);
            const data = await res.json();

            renderCards(data);
            renderCharts(data);
        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las estadísticas.'
            });
        }
    }

    $('#btnLoadStats').on('click', loadStats);

    renderCards(initial);
    renderCharts(initial);
})();
</script>
@endpush