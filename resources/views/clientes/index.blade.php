@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-1">Clientes</h3>
            <div class="text-muted">Administración de clientes</div>
        </div>
        <button class="btn btn-primary" id="btnNuevoCliente">
            <i class="fa-solid fa-plus me-1"></i> Nuevo
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblClientes">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Teléfono</th>
                    <th>Dirección</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCliente">
                <div class="modal-header">
                    <h5 class="modal-title" id="clienteModalTitle">Nuevo cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="clienteId">
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
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select select2-cliente" id="status_id" name="status_id"></select>
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
    const modal = new bootstrap.Modal(document.getElementById('modalCliente'));
    const form = document.getElementById('formCliente');
    const clienteId = document.getElementById('clienteId');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-cliente').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalCliente')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;
        const res = await fetch('/clientes/options');
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
        clienteId.value = '';
        $('.select2-cliente').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblClientes').DataTable({
            ajax: { url: '/clientes/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'nombres' },
                { data: 'apellidos' },
                { data: 'telefono', defaultContent: '' },
                { data: 'direccion', defaultContent: '' },
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
        document.getElementById('clienteModalTitle').textContent = 'Nuevo cliente';
        modal.show();
    }

    async function editItem(id) {
        await loadOptions();
        resetForm();

        const res = await fetch(`/clientes/${id}`);
        const json = await res.json();

        clienteId.value = json.data.id;
        document.getElementById('nombres').value = json.data.nombres || '';
        document.getElementById('apellidos').value = json.data.apellidos || '';
        document.getElementById('telefono').value = json.data.telefono || '';
        document.getElementById('direccion').value = json.data.direccion || '';
        $('#status_id').val(json.data.status_id).trigger('change');

        document.getElementById('clienteModalTitle').textContent = 'Editar cliente';
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const id = clienteId.value;
        const formData = new FormData(form);
        if (id) formData.append('_method', 'PUT');

        const url = id ? `/clientes/${id}` : '/clientes';

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
            title: '¿Dar de baja cliente?',
            showCancelButton: true,
            confirmButtonText: 'Sí, dar de baja',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const res = await fetch(`/clientes/${id}`, {
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

    document.getElementById('btnNuevoCliente').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblClientes').on('click', '.btn-edit', function () {
        editItem(this.dataset.id);
    });

    $('#tblClientes').on('click', '.btn-delete', function () {
        deleteItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush