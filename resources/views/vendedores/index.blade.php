@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-1">Vendedores</h3>
            <div class="text-muted">Administración de vendedores</div>
        </div>
        <button class="btn btn-primary" id="btnNuevoVendedor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblVendedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Clave</th>
                    <th>Empleado</th>
                    <th>Comisión</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalVendedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formVendedor">
                <div class="modal-header">
                    <h5 class="modal-title" id="vendedorModalTitle">Nuevo vendedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="vendedorId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Empleado</label>
                            <select class="form-select select2-vendedor" id="personal_id" name="personal_id"></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select select2-vendedor" id="status_id" name="status_id"></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Monto comisión</label>
                            <input type="number" step="0.01" class="form-control" id="monto_comision" name="monto_comision">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Clave generada</label>
                            <input type="text" class="form-control" value="Se genera automáticamente" readonly>
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
    const modal = new bootstrap.Modal(document.getElementById('modalVendedor'));
    const form = document.getElementById('formVendedor');
    const vendedorId = document.getElementById('vendedorId');
    let table = null;

    function initSelect2() {
        $('.select2-vendedor').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalVendedor')
        });
    }

    async function loadOptions() {
        const res = await fetch('/vendedores/options');
        const data = await res.json();
        fillSelect('personal_id', data.personals);
        fillSelect('status_id', data.statuses);
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => el.innerHTML += `<option value="${item.value}">${item.text}</option>`);
        $(el).trigger('change');
    }

    function resetForm() {
        form.reset();
        vendedorId.value = '';
        $('.select2-vendedor').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblVendedores').DataTable({
            ajax: { url: '/vendedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'clave' },
                { data: null, render: row => `${row.nombres} ${row.apellidos}` },
                { data: 'monto_comision' },
                { data: 'email', defaultContent: '' },
                { data: 'telefono', defaultContent: '' },
                { data: 'estado' },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
        });
    }

    async function openNew() {
        resetForm();
        await loadOptions();
        document.getElementById('vendedorModalTitle').textContent = 'Nuevo vendedor';
        modal.show();
    }

    async function editItem(id) {
        resetForm();
        await loadOptions();

        const res = await fetch(`/vendedores/${id}`);
        const json = await res.json();

        vendedorId.value = json.data.id;
        $('#personal_id').val(json.data.personal_id).trigger('change');
        $('#status_id').val(json.data.status_id).trigger('change');
        document.getElementById('monto_comision').value = json.data.monto_comision || '';

        document.getElementById('vendedorModalTitle').textContent = 'Editar vendedor';
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const id = vendedorId.value;
        const formData = new FormData(form);
        if (id) formData.append('_method', 'PUT');

        const url = id ? `/vendedores/${id}` : '/vendedores';

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
            title: '¿Dar de baja vendedor?',
            showCancelButton: true,
            confirmButtonText: 'Sí, dar de baja',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const res = await fetch(`/vendedores/${id}`, {
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

    document.getElementById('btnNuevoVendedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblVendedores').on('click', '.btn-edit', function () {
        editItem(this.dataset.id);
    });

    $('#tblVendedores').on('click', '.btn-delete', function () {
        deleteItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush