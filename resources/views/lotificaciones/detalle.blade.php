@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Detalle de lotificación</h3>
            <div class="text-muted">{{ $development->nombre }}</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-warning" id="btnModificacionMasiva">
                <i class="fa-solid fa-layer-group me-1"></i> Modificación masiva
            </button>
            <button class="btn btn-outline-primary" id="btnGenerarLotes">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Generar lotes
            </button>
            <button class="btn btn-primary" id="btnNuevoLote">
                <i class="fa-solid fa-plus me-1"></i> Nuevo lote
            </button>
        </div>
    </div>
</div>

<div class="page-card mb-3">
    <div class="d-flex flex-wrap gap-2">
        <span class="badge rounded-pill px-3 py-2" style="background:#ffffff;color:#111827;border:1px solid #d1d5db;">Libre</span>
        <span class="badge rounded-pill px-3 py-2" style="background:#f59e0b;color:#ffffff;border:1px solid #d97706;">Apartado</span>
        <span class="badge rounded-pill px-3 py-2" style="background:#dc2626;color:#ffffff;border:1px solid #b91c1c;">Vendido</span>
        <span class="badge rounded-pill px-3 py-2" style="background:#2563eb;color:#ffffff;border:1px solid #1d4ed8;">Liquidado</span>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblLotes">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Identificador</th>
                    <th>Manzana</th>
                    <th>Precio contado</th>
                    <th>Precio crédito</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalLote" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formLote">
                <div class="modal-header">
                    <h5 class="modal-title" id="loteModalTitle">Nuevo lote</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="loteId">

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Identificador</label>
                            <input type="text" class="form-control" id="identificador" name="identificador">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Manzana</label>
                            <input type="text" class="form-control" id="manzana" name="manzana">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <input type="text" class="form-control" id="estado_texto" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Oficinas</label>
                            <select class="form-select select2-lote" id="office_ids" name="office_ids[]" multiple></select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Socios</label>
                            <select class="form-select select2-lote" id="partner_ids" name="partner_ids[]" multiple></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Precio contado</label>
                            <input type="number" step="0.01" class="form-control" id="precio_contado" name="precio_contado">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Precio crédito</label>
                            <input type="number" step="0.01" class="form-control" id="precio_credito" name="precio_credito">
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

<div class="modal fade" id="modalGenerador" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formGenerador">
                <div class="modal-header">
                    <h5 class="modal-title">Generar lotes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="crear_todos" name="crear_todos" value="1">
                        <label class="form-check-label" for="crear_todos">
                            Crear todos los lotes automáticamente
                        </label>
                    </div>

                    <div class="alert alert-light border small mb-3">
                        Si la lotificación tiene manzanas, se generarán como <strong>M1 L1</strong>, <strong>M1 L2</strong>, etc.  
                        Si no tiene manzanas, se generarán como <strong>SM L1</strong>, <strong>SM L2</strong>.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4" id="manzanaGroup">
                            <label class="form-label">Manzana</label>
                            <select class="form-select" id="g_manzana" name="manzana"></select>
                            <div class="form-text">Si no marcas “crear todos”, debes seleccionar una manzana.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Oficinas</label>
                            <select class="form-select select2-gen" id="g_office_ids" name="office_ids[]" multiple></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Socios</label>
                            <select class="form-select select2-gen" id="g_partner_ids" name="partner_ids[]" multiple></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio contado</label>
                            <input type="number" step="0.01" class="form-control" id="g_precio_contado" name="precio_contado">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio crédito</label>
                            <input type="number" step="0.01" class="form-control" id="g_precio_credito" name="precio_credito">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" type="submit">Generar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMasiva" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formMasiva">
                <div class="modal-header">
                    <h5 class="modal-title">Modificación masiva de lotes libres</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning small">
                        Esta acción solo afectará lotes en estado <strong>LIBRE</strong>.  
                        Los lotes no libres dentro del rango serán omitidos automáticamente.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4" id="m_manzana_group">
                            <label class="form-label">Manzana</label>
                            <select class="form-select" id="m_manzana" name="manzana"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Desde lote</label>
                            <input type="number" min="1" class="form-control" id="m_desde" name="desde">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Hasta lote</label>
                            <input type="number" min="1" class="form-control" id="m_hasta" name="hasta">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Oficinas</label>
                            <select class="form-select select2-masiva" id="m_office_ids" name="office_ids[]" multiple></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Socios</label>
                            <select class="form-select select2-masiva" id="m_partner_ids" name="partner_ids[]" multiple></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio contado</label>
                            <input type="number" step="0.01" class="form-control" id="m_precio_contado" name="precio_contado">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Precio crédito</label>
                            <input type="number" step="0.01" class="form-control" id="m_precio_credito" name="precio_credito">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-warning" type="submit">Aplicar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const developmentId = @json($development->id);
    const modalLote = new bootstrap.Modal(document.getElementById('modalLote'));
    const modalGenerador = new bootstrap.Modal(document.getElementById('modalGenerador'));
    const modalMasiva = new bootstrap.Modal(document.getElementById('modalMasiva'));
    const formLote = document.getElementById('formLote');
    const formGenerador = document.getElementById('formGenerador');
    const formMasiva = document.getElementById('formMasiva');
    const loteId = document.getElementById('loteId');
    const crearTodosChk = document.getElementById('crear_todos');
    const manzanaGroup = document.getElementById('manzanaGroup');
    const manzanaSelect = document.getElementById('g_manzana');
    const manzanaMasivaGroup = document.getElementById('m_manzana_group');
    const manzanaMasivaSelect = document.getElementById('m_manzana');

    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-lote').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalLote')
        });

        $('.select2-gen').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalGenerador')
        });

        $('.select2-masiva').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalMasiva')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch(`/lotificaciones/${developmentId}/lots/options`);
        optionsCache = await res.json();

        fillSelect('office_ids', optionsCache.offices, true);
        fillSelect('partner_ids', optionsCache.partners, true);
        fillSelect('g_office_ids', optionsCache.offices, true);
        fillSelect('g_partner_ids', optionsCache.partners, true);
        fillSelect('m_office_ids', optionsCache.offices, true);
        fillSelect('m_partner_ids', optionsCache.partners, true);
        fillManzanaSelects();

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

    function fillManzanaSelects() {
        [manzanaSelect, manzanaMasivaSelect].forEach(select => {
            select.innerHTML = '<option value="">Seleccione...</option>';
        });

        if (!optionsCache) return;

        if ((optionsCache.development.manzanas || 0) > 0) {
            optionsCache.manzanas.forEach(item => {
                manzanaSelect.innerHTML += `<option value="${item.value}">${item.text}</option>`;
                manzanaMasivaSelect.innerHTML += `<option value="${item.value}">${item.text}</option>`;
            });
        } else {
            manzanaSelect.innerHTML += `<option value="SM">Sin manzana</option>`;
            manzanaMasivaSelect.innerHTML += `<option value="SM">Sin manzana</option>`;
        }
    }

    function toggleGeneratorMode() {
        const hasManzanas = optionsCache && (optionsCache.development.manzanas || 0) > 0;
        const crearTodos = crearTodosChk.checked;

        if (!hasManzanas) {
            manzanaGroup.style.display = 'none';
            manzanaSelect.required = false;
            manzanaSelect.value = 'SM';
            return;
        }

        manzanaGroup.style.display = crearTodos ? 'none' : '';
        manzanaSelect.required = !crearTodos;
        if (crearTodos) {
            manzanaSelect.value = '';
        }
    }

    function toggleMasivaMode() {
        const hasManzanas = optionsCache && (optionsCache.development.manzanas || 0) > 0;

        if (!hasManzanas) {
            manzanaMasivaGroup.style.display = 'none';
            manzanaMasivaSelect.required = false;
            manzanaMasivaSelect.value = 'SM';
        } else {
            manzanaMasivaGroup.style.display = '';
            manzanaMasivaSelect.required = true;
        }
    }

    function resetLoteForm() {
        formLote.reset();
        loteId.value = '';
        $('#office_ids').val(null).trigger('change');
        $('#partner_ids').val(null).trigger('change');
        document.getElementById('estado_texto').value = '';
        document.getElementById('identificador').readOnly = false;
        document.getElementById('manzana').readOnly = false;
    }

    function resetGeneratorForm() {
        formGenerador.reset();
        $('#g_office_ids').val(null).trigger('change');
        $('#g_partner_ids').val(null).trigger('change');
    }

    function resetMasivaForm() {
        formMasiva.reset();
        $('#m_office_ids').val(null).trigger('change');
        $('#m_partner_ids').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblLotes').DataTable({
            ajax: { url: `/lotificaciones/${developmentId}/lots/datatable`, dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'identificador' },
                { data: 'manzana', defaultContent: '' },
                { data: 'precio_contado' },
                { data: 'precio_credito' },
                { data: 'estado_badge', orderable: false, searchable: false },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 15,
            order: [],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
        });
    }

    async function openNew() {
        await loadOptions();
        resetLoteForm();

        document.getElementById('estado_texto').value = 'Libre';
        document.getElementById('identificador').readOnly = false;
        document.getElementById('manzana').readOnly = false;

        document.getElementById('loteModalTitle').textContent = 'Nuevo lote';
        modalLote.show();
    }

    async function editItem(id) {
        await loadOptions();
        resetLoteForm();

        const res = await fetch(`/lotificaciones/${developmentId}/lots/${id}`);
        const json = await res.json();

        loteId.value = json.data.id;
        document.getElementById('identificador').value = json.data.identificador || '';
        document.getElementById('manzana').value = json.data.manzana || '';
        document.getElementById('precio_contado').value = json.data.precio_contado || 0;
        document.getElementById('precio_credito').value = json.data.precio_credito || 0;
        document.getElementById('estado_texto').value = json.data.estado_nombre || 'Libre';

        document.getElementById('identificador').readOnly = true;
        document.getElementById('manzana').readOnly = true;

        $('#office_ids').val(json.data.office_ids || []).trigger('change');
        $('#partner_ids').val(json.data.partner_ids || []).trigger('change');

        document.getElementById('loteModalTitle').textContent = 'Editar lote';
        modalLote.show();
    }

    async function saveLote(e) {
        e.preventDefault();

        const id = loteId.value;
        const formData = new FormData(formLote);
        if (id) formData.append('_method', 'PUT');

        const url = id
            ? `/lotificaciones/${developmentId}/lots/${id}`
            : `/lotificaciones/${developmentId}/lots`;

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

            modalLote.hide();
            table.ajax.reload(null, false);

            Swal.fire({ icon: 'success', title: 'Correcto', text: json.message, timer: 1500, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    async function deleteItem(id) {
        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Eliminar lote?',
            text: 'Solo se permite si está libre.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const res = await fetch(`/lotificaciones/${developmentId}/lots/${id}`, {
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
            text: json.message || 'No se pudo eliminar'
        });

        if (res.ok) table.ajax.reload(null, false);
    }

    async function generateLots(e) {
        e.preventDefault();

        if (!optionsCache) {
            await loadOptions();
        }

        if (!crearTodosChk.checked && manzanaGroup.style.display !== 'none' && !manzanaSelect.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una manzana'
            });
            return;
        }

        const formData = new FormData(formGenerador);

        try {
            const res = await fetch(`/lotificaciones/${developmentId}/lots/generate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo generar');

            modalGenerador.hide();
            table.ajax.reload(null, false);

            Swal.fire({ icon: 'success', title: 'Correcto', text: json.message, timer: 1500, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    async function bulkUpdateLots(e) {
        e.preventDefault();

        if (!optionsCache) {
            await loadOptions();
        }

        if (manzanaMasivaGroup.style.display !== 'none' && !manzanaMasivaSelect.value) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una manzana'
            });
            return;
        }

        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Aplicar modificación masiva?',
            text: 'Solo afectará lotes en estado libre dentro del rango seleccionado.',
            showCancelButton: true,
            confirmButtonText: 'Sí, aplicar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const formData = new FormData(formMasiva);

        try {
            const res = await fetch(`/lotificaciones/${developmentId}/lots/bulk-update`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo aplicar la modificación masiva');

            modalMasiva.hide();
            table.ajax.reload(null, false);

            Swal.fire({ icon: 'success', title: 'Correcto', text: json.message, timer: 1700, showConfirmButton: false });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    document.getElementById('btnNuevoLote').addEventListener('click', openNew);

    document.getElementById('btnGenerarLotes').addEventListener('click', async function () {
        if (!optionsCache) {
            await loadOptions();
        }

        resetGeneratorForm();
        fillManzanaSelects();
        crearTodosChk.checked = false;
        toggleGeneratorMode();
        modalGenerador.show();
    });

    document.getElementById('btnModificacionMasiva').addEventListener('click', async function () {
        if (!optionsCache) {
            await loadOptions();
        }

        resetMasivaForm();
        fillManzanaSelects();
        toggleMasivaMode();
        modalMasiva.show();
    });

    crearTodosChk.addEventListener('change', toggleGeneratorMode);

    formLote.addEventListener('submit', saveLote);
    formGenerador.addEventListener('submit', generateLots);
    formMasiva.addEventListener('submit', bulkUpdateLots);

    $('#tblLotes').on('click', '.btn-edit', function () {
        editItem(this.dataset.id);
    });

    $('#tblLotes').on('click', '.btn-delete', function () {
        deleteItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush