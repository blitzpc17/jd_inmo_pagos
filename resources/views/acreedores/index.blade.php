@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Acreedores</h3>
            <div class="text-muted">Catálogo de acreedores (Empresas)</div>
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
                    <th>Nombre de Empresa</th>
                    <th>Teléfonos</th>
                    <th>Direcciones</th>
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
                <input type="hidden" id="acreedorId">

                <div class="modal-header">
                    <h5 class="modal-title" id="acreedorModalTitle">Nuevo acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nombre de Empresa</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfonos</label>
                            <textarea class="form-control" id="telefonos" name="telefonos" rows="3"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Direcciones</label>
                            <textarea class="form-control" id="direcciones" name="direcciones" rows="3"></textarea>
                        </div>

                        <div class="col-md-6" id="divEstado">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="chkActivo" checked>
                                <label class="form-check-label" for="chkActivo">Activo</label>
                            </div>
                            <!-- Hidden input to send status_id -->
                            <input type="hidden" id="status_id" name="status_id">
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
    const acreedorId = document.getElementById('acreedorId');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        // Any other select2 init if needed
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;
        const res = await fetch('/acreedores/options');
        optionsCache = await res.json();
        return optionsCache;
    }

    function resetForm() {
        form.reset();
        acreedorId.value = '';
        $('#chkActivo').prop('checked', true);
        document.getElementById('acreedorModalTitle').textContent = 'Nuevo acreedor';
    }

    function initTable() {
        table = $('#tblAcreedores').DataTable({
            ajax: { url: '/acreedores/datatable', dataSrc: 'data' },
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
        document.getElementById('divEstado').style.display = 'none';
        modal.show();
    }

    async function editItem(id) {
        await loadOptions();
        resetForm();

        const res = await fetch(`/acreedores/${id}`);
        const json = await res.json();

        acreedorId.value = json.data.id;
        document.getElementById('nombre').value = json.data.nombre || '';
        document.getElementById('telefonos').value = json.data.telefonos || '';
        document.getElementById('direcciones').value = json.data.direcciones || '';
        document.getElementById('direcciones').value = json.data.direcciones || '';
        
        const activeStatus = optionsCache.statuses.find(s => s.text.toUpperCase() === 'ACTIVO' || s.value == 1 || s.clave === 'ACTIVE' || s.text === 'Activo'); // We'll just find the one that doesn't say INACTIVO
        // Usually statuses list returns value and text. Let's find ACTIVE or assume value=1 if not found.
        const isActiveId = optionsCache.statuses.find(s => (s.text && s.text.toUpperCase() === 'ACTIVO') || s.clave === 'ACTIVE')?.value || 1;
        $('#chkActivo').prop('checked', json.data.status_id == isActiveId);
        $('#status_id').val(json.data.status_id);

        document.getElementById('divEstado').style.display = 'block';

        document.getElementById('acreedorModalTitle').textContent = 'Editar acreedor';
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        if (optionsCache) {
            // Find active/inactive statuses by text
            const activeStatus = optionsCache.statuses.find(s => s.text.toUpperCase() === 'ACTIVO' || s.clave === 'ACTIVE') || {value: 1};
            const inactiveStatus = optionsCache.statuses.find(s => s.text.toUpperCase() === 'INACTIVO' || s.clave === 'INACTIVE') || {value: 2};
            const chkActivo = document.getElementById('chkActivo').checked;
            document.getElementById('status_id').value = chkActivo ? activeStatus.value : inactiveStatus.value;
        }

        const id = acreedorId.value;
        const formData = new FormData(form);
        if (id) formData.append('_method', 'PUT');

        const url = id ? `/acreedores/${id}` : '/acreedores';

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

    document.getElementById('btnNuevoAcreedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblAcreedores').on('click', '.btn-edit', function () {
        editItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush