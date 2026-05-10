@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Acreedores</h3>
            <div class="text-muted">Catálogo de acreedores</div>
        </div>
        <button class="btn btn-primary" id="btnNuevoAcreedor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo acreedor
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblAcreedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalAcreedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formAcreedor">
                <input type="hidden" id="acreedor_id">

                <div class="modal-header">
                    <h5 class="modal-title" id="acreedorModalTitle">Nuevo acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="nombres" name="nombres">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalAcreedor'));
    const form = document.getElementById('formAcreedor');
    let table = null;

    function resetForm() {
        form.reset();
        document.getElementById('acreedor_id').value = '';
        document.getElementById('acreedorModalTitle').textContent = 'Nuevo acreedor';
    }

    function initTable() {
        table = $('#tblAcreedores').DataTable({
            ajax: { url: '/acreedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'nombre_completo' },
                { data: 'telefono' },
                { data: 'direccion' },
                { data: 'estado' },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [],
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
    }

    async function openNew() {
        resetForm();
        modal.show();
    }

    async function editItem(id) {
        const res = await fetch(`/acreedores/${id}`);
        const json = await res.json();

        document.getElementById('acreedor_id').value = json.data.id;
        document.getElementById('nombres').value = json.data.nombres || '';
        document.getElementById('apellidos').value = json.data.apellidos || '';
        document.getElementById('telefono').value = json.data.telefono || '';
        document.getElementById('direccion').value = json.data.direccion || '';
        document.getElementById('acreedorModalTitle').textContent = 'Editar acreedor';

        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const id = document.getElementById('acreedor_id').value;
        const isEdit = !!id;

        const payload = {
            nombres: document.getElementById('nombres').value,
            apellidos: document.getElementById('apellidos').value,
            telefono: document.getElementById('telefono').value,
            direccion: document.getElementById('direccion').value
        };

        try {
            const res = await fetch(isEdit ? `/acreedores/${id}` : '/acreedores', {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo guardar');

            modal.hide();
            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1600,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        }
    }

    async function deleteItem(id) {
        const ask = await Swal.fire({
            icon: 'warning',
            title: '¿Dar de baja acreedor?',
            showCancelButton: true,
            confirmButtonText: 'Sí, dar de baja',
            cancelButtonText: 'Cancelar'
        });

        if (!ask.isConfirmed) return;

        try {
            const res = await fetch(`/acreedores/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo dar de baja');

            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1600,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        }
    }

    document.getElementById('btnNuevoAcreedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblAcreedores').on('click', '.btn-edit', function () {
        editItem(this.dataset.id);
    });

    $('#tblAcreedores').on('click', '.btn-delete', function () {
        deleteItem(this.dataset.id);
    });

    initTable();
})();
</script>
@endpush