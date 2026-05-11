@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Resumen general de lotificaciones</h3>
            <div class="text-muted">Conteo de lotes por estado y total por lotificación</div>
        </div>

        <a href="{{ route('lotificaciones.summary.export') }}" class="btn btn-success">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
        </a>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblResumenLotificaciones">
            <thead>
                <tr>
                    <th class="th-lotificacion">LOTIFICACION</th>
                    <th class="th-vendidos text-center">VENDIDOS</th>
                    <th class="th-apartados text-center">APARTADOS</th>
                    <th class="th-disponibles text-center">DISPONIBLES</th>
                    <th class="th-total text-center">TOTAL</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
    #tblResumenLotificaciones thead th.th-lotificacion{
        background:#676767 !important;
        color:#fff !important;
    }

    #tblResumenLotificaciones thead th.th-vendidos{
        background:#F20505 !important;
        color:#fff !important;
    }

    #tblResumenLotificaciones thead th.th-apartados{
        background:#FFD54F !important;
        color:#0D0D0D !important;
    }

    #tblResumenLotificaciones thead th.th-disponibles{
        background:#FFFFFF !important;
        color:#0D0D0D !important;
        border-color:#d6d6d6 !important;
    }

    #tblResumenLotificaciones thead th.th-total{
        background:#0D0D0D !important;
        color:#fff !important;
    }

    #tblResumenLotificaciones tbody td.col-vendidos{
        background:rgba(242,5,5,.08);
        color:#B30000;
        font-weight:700;
    }

    #tblResumenLotificaciones tbody td.col-apartados{
        background:rgba(255,213,79,.22);
        color:#7A5A00;
        font-weight:700;
    }

    #tblResumenLotificaciones tbody td.col-disponibles{
        background:rgba(255,255,255,1);
        color:#0D0D0D;
        font-weight:700;
    }

    #tblResumenLotificaciones tbody td.col-total{
        background:rgba(13,13,13,.04);
        color:#0D0D0D;
        font-weight:800;
    }
</style>
@endpush

@push('scripts')
<script>
(() => {
    $('#tblResumenLotificaciones').DataTable({
        ajax: {
            url: '{{ route('lotificaciones.summary.data') }}',
            dataSrc: 'data'
        },
        columns: [
            { data: 'lotificacion' },
            { data: 'vendidos', className: 'text-center fw-bold col-vendidos' },
            { data: 'apartados', className: 'text-center fw-bold col-apartados' },
            { data: 'disponibles', className: 'text-center fw-bold col-disponibles' },
            { data: 'total', className: 'text-center fw-bold col-total' }
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