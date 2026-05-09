@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-1">Proveedores</h3>
            <div class="text-muted">Administración de proveedores</div>
        </div>
        <button class="btn btn-primary" id="btnNuevoProveedor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblProveedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Teléfonos</th>
                    <th>Direcciones</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalProveedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formProveedor">
                <div class="modal-header">
                    <h5 class="modal-title" id="proveedorModalTitle">Nuevo proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="proveedorId">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfonos</label>
                            <textarea class="form-control" id="telefonos" name="telefonos" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Direcciones</label>
                            <textarea class="form-control" id="direcciones" name="direcciones" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select select2-prov" id="status_id" name="status_id"></select>
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
    const modal = new bootstrap.Modal(document.getElementById('modalProveedor'));
    const form = document.getElementById('formProveedor');
    const proveedorId = document.getElementById('proveedorId');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-prov').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalProveedor')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;
        const res = await fetch('/proveedores/options');
        optionsCache = await res.json();
        fillSelect('status_id', optionsCache.statuses);
        return optionsCache;
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => el.innerHTML += `<option value="${item.value}">${item.text}</option>`);
        $(el).trigger('change');
    }

    function resetForm() {
        form.reset();
        proveedorId.value = '';
        $('.select2-prov').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblProveedores').DataTable({
            ajax: { url: '/proveedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'nombre' },
                { data: 'telefonos', defaultContent: '' },
                { data: 'direcciones', defaultContent: '' },
                { data: 'estado' },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
        });
    }

    async function openNew() {
        await loadOptions();
        resetForm();
        document.getElementById('proveedorModalTitle').textContent = 'Nuevo proveedor';
        modal.show();
    }

    async function editItem(id) {
        await loadOptions();
        resetForm();

        const res = await fetch(`/proveedores/${id}`);
        const json = await res.json();

        proveedorId.value = json.data.id;
        document.getElementById('nombre').value = json.data.nombre || '';
        document.getElementById('telefonos').value = json.data.telefonos || '';
        document.getElementById('direcciones').value = json.data.direcciones || '';
        $('#status_id').val(json.data.status_id).trigger('change');

        document.getElementById('proveedorModalTitle').textContent = 'Editar proveedor';
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const id = proveedorId.value;
        const formData = new FormData(form);
        if (id) formData.append('_method', 'PUT');

        const url = id ? `/proveedores/${id}` : '/proveedores';

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
            if (!res.ok) throw new Error(json.message || 'No se pudo guardar');

            modal.hide();
            table.ajax.reload(null, false);

            Swal.fire({ icon: 'success', title: 'Correcto', text: json.message, timer: 1500, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    async function deleteItem(id) {
        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Dar de baja proveedor?',
            showCancelButton: true,
            confirmButtonText: 'Sí, dar de baja',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const res = await fetch(`/proveedores/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        Swal.fire({
            icon: res.ok ? 'success' : 'error',
            title: res.ok ? 'Correcto' : 'Error',
            text: json.message || 'No se pudo dar de baja'
        });

        if (res.ok) table.ajax.reload(null, false);
    }

    document.getElementById('btnNuevoProveedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblProveedores').on('click', '.btn-edit', function () {
        editItem(this.dataset.id);
    });

    $('#tblProveedores').on('click', '.btn-delete', function () {
        deleteItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush