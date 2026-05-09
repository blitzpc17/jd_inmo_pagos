@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold mb-1">Usuarios</h3>
            <div class="text-muted">Administración de usuarios del sistema</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoUsuario">
            <i class="fa-solid fa-plus me-1"></i> Nuevo
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblUsuarios">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Alias</th>
                    <th>Nombre</th>
                    <th>Puesto</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formUsuario">
                <div class="modal-header">
                    <h5 class="modal-title" id="usuarioModalTitle">Nuevo usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="usuarioId">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Alias</label>
                            <input type="text" class="form-control" id="alias" name="alias">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Rol</label>
                            <select class="form-select select2-user" id="role_id" name="role_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="nombres" name="nombres">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Puesto</label>
                            <select class="form-select select2-user" id="position_id" name="position_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select select2-user" id="status_id" name="status_id"></select>
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
    const modalEl = document.getElementById('modalUsuario');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('formUsuario');
    const usuarioId = document.getElementById('usuarioId');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-user').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalUsuario')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch('/usuarios/create-options');
        optionsCache = await res.json();

        fillSelect('role_id', optionsCache.roles);
        fillSelect('position_id', optionsCache.positions);
        fillSelect('status_id', optionsCache.statuses);

        return optionsCache;
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
        $(el).trigger('change');
    }

    function resetForm() {
        form.reset();
        usuarioId.value = '';
        $('.select2-user').val(null).trigger('change');
        document.getElementById('password').required = true;
    }

    function initTable() {
        table = $('#tblUsuarios').DataTable({
            ajax: {
                url: '/usuarios/datatable',
                dataSrc: 'data'
            },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'alias' },
                { data: null, render: row => `${row.nombres} ${row.apellidos}` },
                { data: 'puesto', defaultContent: '' },
                { data: 'rol' },
                { data: 'estado' },
                { data: 'email', defaultContent: '' },
                { data: 'telefono', defaultContent: '' },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            }
        });
    }

    async function openNew() {
        await loadOptions();
        resetForm();
        document.getElementById('usuarioModalTitle').textContent = 'Nuevo usuario';
        modal.show();
    }

    async function editUser(id) {
        await loadOptions();
        resetForm();

        const res = await fetch(`/usuarios/${id}`);
        const json = await res.json();

        usuarioId.value = json.data.id;
        document.getElementById('alias').value = json.data.alias || '';
        document.getElementById('nombres').value = json.data.nombres || '';
        document.getElementById('apellidos').value = json.data.apellidos || '';
        document.getElementById('telefono').value = json.data.telefono || '';
        document.getElementById('email').value = json.data.email || '';
        document.getElementById('direccion').value = json.data.direccion || '';

        $('#role_id').val(json.data.role_id).trigger('change');
        $('#position_id').val(json.data.position_id).trigger('change');
        $('#status_id').val(json.data.status_id).trigger('change');

        document.getElementById('password').required = false;
        document.getElementById('usuarioModalTitle').textContent = 'Editar usuario';
        modal.show();
    }

    async function saveUser(e) {
        e.preventDefault();

        const id = usuarioId.value;
        const formData = new FormData(form);

        if (id) {
            formData.append('_method', 'PUT');
        }

        const url = id ? `/usuarios/${id}` : '/usuarios';

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();

            if (!res.ok) {
                let txt = json.message || 'No se pudo guardar';
                if (json.errors) {
                    txt = Object.values(json.errors).flat().join('\n');
                }
                throw new Error(txt);
            }

            modal.hide();
            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1500,
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

    async function deleteUser(id) {
        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Desactivar usuario?',
            text: 'Se cambiará a inactivo.',
            showCancelButton: true,
            confirmButtonText: 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await fetch(`/usuarios/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();

            if (!res.ok) throw new Error(json.message || 'No se pudo desactivar');

            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1500,
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

    document.getElementById('btnNuevoUsuario').addEventListener('click', openNew);
    form.addEventListener('submit', saveUser);

    $('#tblUsuarios').on('click', '.btn-edit', function () {
        editUser(this.dataset.id);
    });

    $('#tblUsuarios').on('click', '.btn-delete', function () {
        deleteUser(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush