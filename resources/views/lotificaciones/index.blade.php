@extends('layouts.app')

@section('content')
<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="fw-bold mb-1">Lotificaciones</h3>
            <div class="text-muted">Administración de lotificaciones</div>
        </div>
        <button class="btn btn-primary" id="btnNuevaLotificacion">
            <i class="fa-solid fa-plus me-1"></i> Nueva
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblLotificaciones">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Manzanas</th>
                    <th>Lotes</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalLotificacion" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formLotificacion">
                <div class="modal-header">
                    <h5 class="modal-title" id="lotificacionModalTitle">Nueva lotificación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="lotificacionId">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Manzanas</label>
                            <input type="number" class="form-control" id="manzanas" name="manzanas" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lotes por manzana</label>
                            <input type="number" class="form-control" id="lotes" name="lotes" min="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select select2-dev" id="status_id" name="status_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Oficinas</label>
                            <select class="form-select select2-dev" id="office_ids" name="office_ids[]" multiple></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Socios</label>
                            <select class="form-select select2-dev" id="partner_ids" name="partner_ids[]" multiple></select>
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
    const modal = new bootstrap.Modal(document.getElementById('modalLotificacion'));
    const form = document.getElementById('formLotificacion');
    const lotificacionId = document.getElementById('lotificacionId');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-dev').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalLotificacion')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;
        const res = await fetch('/lotificaciones/options');
        optionsCache = await res.json();

        fillSelect('status_id', optionsCache.statuses, false);
        fillSelect('office_ids', optionsCache.offices, true);
        fillSelect('partner_ids', optionsCache.partners, true);

        return optionsCache;
    }

    function fillSelect(id, items, multiple = false) {
        const el = document.getElementById(id);
        el.innerHTML = multiple ? '' : '<option value="">Seleccione...</option>';

        items.forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });

        $(el).trigger('change');
    }

    function resetForm() {
        form.reset();
        lotificacionId.value = '';
        $('.select2-dev').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblLotificaciones').DataTable({
            ajax: { url: '/lotificaciones/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'nombre' },
                { data: 'manzanas' },
                { data: 'lotes' },
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
        document.getElementById('lotificacionModalTitle').textContent = 'Nueva lotificación';
        modal.show();
    }

    async function editItem(id) {
        await loadOptions();
        resetForm();

        const res = await fetch(`/lotificaciones/${id}`);
        const json = await res.json();

        lotificacionId.value = json.data.id;
        document.getElementById('nombre').value = json.data.nombre || '';
        document.getElementById('manzanas').value = json.data.manzanas || 0;
        document.getElementById('lotes').value = json.data.lotes || 0;
        $('#status_id').val(json.data.status_id).trigger('change');
        $('#office_ids').val(json.data.office_ids || []).trigger('change');
        $('#partner_ids').val(json.data.partner_ids || []).trigger('change');

        document.getElementById('lotificacionModalTitle').textContent = 'Editar lotificación';
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const id = lotificacionId.value;
        const formData = new FormData(form);
        if (id) formData.append('_method', 'PUT');

        const url = id ? `/lotificaciones/${id}` : '/lotificaciones';

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
            title: '¿Dar de baja lotificación?',
            showCancelButton: true,
            confirmButtonText: 'Sí, dar de baja',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const res = await fetch(`/lotificaciones/${id}`, {
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

    document.getElementById('btnNuevaLotificacion').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblLotificaciones').on('click', '.btn-edit', function () {
        editItem(this.dataset.id);
    });

    $('#tblLotificaciones').on('click', '.btn-delete', function () {
        deleteItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush