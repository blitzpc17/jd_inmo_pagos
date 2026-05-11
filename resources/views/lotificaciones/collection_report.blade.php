@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Reporte de cobranza por lotificación</h3>
            <div class="text-muted">Resumen de contratos, enganches, cobros y saldo pendiente por lotificación</div>
        </div>

        <a href="{{ route('lotificaciones.collection_report.export') }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblReporteCobranzaLotificaciones">
            <thead>
                <tr>
                    <th class="th-lotificacion">LOTIFICACION</th>
                    <th class="th-contratos text-end">CONTRATOS</th>
                    <th class="th-enganches text-end">ENGANCHES</th>
                    <th class="th-cobrado text-end">COBRADO</th>
                    <th class="th-resto text-end">RESTO POR COBRAR</th>
                    <th class="th-ingreso text-end">INGRESO MENSUAL</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
    #tblReporteCobranzaLotificaciones thead th.th-lotificacion{
        background:#676767 !important;
        color:#fff !important;
    }
    #tblReporteCobranzaLotificaciones thead th.th-contratos{
        background:#0511F2 !important;
        color:#fff !important;
    }
    #tblReporteCobranzaLotificaciones thead th.th-enganches{
        background:#FFD54F !important;
        color:#0D0D0D !important;
    }
    #tblReporteCobranzaLotificaciones thead th.th-cobrado{
        background:#D9042B !important;
        color:#fff !important;
    }
    #tblReporteCobranzaLotificaciones thead th.th-resto{
        background:#F20505 !important;
        color:#fff !important;
    }
    #tblReporteCobranzaLotificaciones thead th.th-ingreso{
        background:#0D0D0D !important;
        color:#fff !important;
    }

    #tblReporteCobranzaLotificaciones tbody td.col-contratos{
        background:rgba(5,17,242,.08);
        font-weight:700;
    }
    #tblReporteCobranzaLotificaciones tbody td.col-enganches{
        background:rgba(255,213,79,.22);
        font-weight:700;
    }
    #tblReporteCobranzaLotificaciones tbody td.col-cobrado{
        background:rgba(217,4,43,.10);
        font-weight:700;
    }
    #tblReporteCobranzaLotificaciones tbody td.col-resto{
        background:rgba(242,5,5,.08);
        font-weight:700;
    }
    #tblReporteCobranzaLotificaciones tbody td.col-ingreso{
        background:rgba(13,13,13,.04);
        font-weight:700;
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    function moneyRender(data) {
        return Number(data || 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    $('#tblReporteCobranzaLotificaciones').DataTable({
        ajax: {
            url: '{{ route('lotificaciones.collection_report.data') }}',
            dataSrc: 'data'
        },
        columns: [
            { data: 'lotificacion' },
            { data: 'contratos', className: 'text-end fw-bold col-contratos', render: moneyRender },
            { data: 'enganches', className: 'text-end fw-bold col-enganches', render: moneyRender },
            { data: 'cobrado', className: 'text-end fw-bold col-cobrado', render: moneyRender },
            { data: 'resto_por_cobrar', className: 'text-end fw-bold col-resto', render: moneyRender },
            { data: 'ingreso_mensual', className: 'text-end fw-bold col-ingreso', render: moneyRender }
        ],
        pageLength: 15,
        order: [[0, 'asc']],
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
})();
</script>
@endpush