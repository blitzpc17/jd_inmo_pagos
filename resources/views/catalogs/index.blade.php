@extends('layouts.app')

@php
    $title = $catalog['title'];
@endphp

@section('content')
<div class="page-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold mb-1">{{ $catalog['title'] }}</h3>
            <div class="text-muted">Administración del catálogo</div>
        </div>

        <button class="btn btn-primary" id="btnNuevo">
            <i class="fa-solid fa-plus me-1"></i> Nuevo
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblCatalogo">
            <thead>
                <tr>
                    <th style="width:70px;">#</th>
                    @foreach($catalog['datatable'] as $col)
                        <th>{{ ucfirst(str_replace('_', ' ', $col)) }}</th>
                    @endforeach
                    <th style="width:110px;">Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formCatalogo">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="recordId">

                    <div class="row g-3">
                        @foreach($catalog['fields'] as $field => $meta)
                            <div class="col-md-6">
                                <label class="form-label">{{ $meta['label'] }}</label>

                                @if(in_array($meta['type'], ['text', 'number']))
                                    <input
                                        type="{{ $meta['type'] === 'number' ? 'number' : 'text' }}"
                                        class="form-control"
                                        name="{{ $field }}"
                                        id="{{ $field }}"
                                        {{ !empty($meta['required']) ? 'required' : '' }}
                                    >
                                @elseif(in_array($meta['type'], ['select', 'select_nullable', 'general_status']))
                                    <select
                                        class="form-select select2-field"
                                        name="{{ $field }}"
                                        id="{{ $field }}"
                                        data-field="{{ $field }}"
                                        {{ !empty($meta['required']) ? 'required' : '' }}
                                    ></select>
                                @elseif($meta['type'] === 'checkbox')
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="{{ $field }}" name="{{ $field }}" value="1">
                                        <label class="form-check-label" for="{{ $field }}">
                                            Sí
                                        </label>
                                    </div>
                                @endif

                                <div class="invalid-feedback" id="error_{{ $field }}"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" type="submit" id="btnGuardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const catalogKey = @json($catalogKey);
    const catalog = @json($catalog);
    const modalEl = document.getElementById('modalCatalogo');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('formCatalogo');
    const recordId = document.getElementById('recordId');

    let table = null;

    function baseUrl(path = '') {
        return `/catalogos/${catalogKey}${path}`;
    }

    function clearErrors() {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function resetForm() {
        form.reset();
        recordId.value = '';
        clearErrors();

        $('.select2-field').val(null).trigger('change');
    }

    async function loadSelect(fieldName) {
        const select = document.getElementById(fieldName);
        if (!select) return;

        const url = `${baseUrl('/select/options')}?field=${fieldName}`;
        const res = await fetch(url);
        const items = await res.json();

        select.innerHTML = '';

        const fieldMeta = catalog.fields[fieldName];
        if (fieldMeta.type === 'select_nullable') {
            select.innerHTML += `<option value="">Seleccione...</option>`;
        }

        items.forEach(item => {
            select.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });

        $(select).trigger('change.select2');
    }

    async function loadAllSelects() {
        const fields = Object.keys(catalog.fields);
        for (const field of fields) {
            const meta = catalog.fields[field];
            if (['select', 'select_nullable', 'general_status'].includes(meta.type)) {
                await loadSelect(field);
            }
        }
    }

    function initSelect2() {
        $('.select2-field').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalCatalogo'),
            placeholder: 'Seleccione...'
        });
    }

    function buildColumns() {
        const columns = [{
            data: null,
            render: (_, __, ___, meta) => meta.row + 1
        }];

        catalog.datatable.forEach(col => {
            columns.push({ data: col, defaultContent: '' });
        });

        columns.push({
            data: 'acciones',
            orderable: false,
            searchable: false
        });

        return columns;
    }

    function initTable() {
        table = $('#tblCatalogo').DataTable({
            ajax: {
                url: baseUrl('/datatable'),
                dataSrc: 'data'
            },
            columns: buildColumns(),
            responsive: true,
            processing: true,
            pageLength: 10,
            order: [],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            }
        });
    }

    async function editRecord(id) {
        clearErrors();

        const res = await fetch(baseUrl(`/${id}`));
        const json = await res.json();

        resetForm();
        recordId.value = json.data.id;

        for (const key in catalog.fields) {
            const el = document.getElementById(key);
            if (!el) continue;

            const meta = catalog.fields[key];
            const value = json.data[key];

            if (meta.type === 'checkbox') {
                el.checked = !!value;
            } else {
                $(el).val(value).trigger('change');
            }
        }

        document.getElementById('modalTitle').textContent = `Editar ${catalog.title}`;
        modal.show();
    }

    async function saveRecord(e) {
        e.preventDefault();
        clearErrors();

        const id = recordId.value;
        const formData = new FormData(form);

        Object.keys(catalog.fields).forEach(field => {
            const meta = catalog.fields[field];
            if (meta.type === 'checkbox' && !formData.has(field)) {
                formData.append(field, '0');
            }
        });

        let url = baseUrl();
        if (id) {
            url = baseUrl(`/${id}`);
            formData.append('_method', 'PUT');
        }

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
                if (json.errors) {
                    Object.keys(json.errors).forEach(field => {
                        const input = document.getElementById(field);
                        const error = document.getElementById(`error_${field}`);
                        if (input) input.classList.add('is-invalid');
                        if (error) error.textContent = json.errors[field][0];
                    });
                    return;
                }

                throw new Error(json.message || 'Error al guardar');
            }

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
                text: err.message || 'No se pudo guardar'
            });
        }
    }

    async function deleteRecord(id) {
        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Eliminar registro?',
            text: 'Esta acción no se puede deshacer.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            const res = await fetch(baseUrl(`/${id}`), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();

            if (!res.ok) {
                throw new Error(json.message || 'No se pudo eliminar');
            }

            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: json.message,
                timer: 1400,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message || 'No se pudo eliminar'
            });
        }
    }

    document.getElementById('btnNuevo').addEventListener('click', async function () {
        resetForm();
        await loadAllSelects();
        document.getElementById('modalTitle').textContent = `Nuevo ${catalog.title}`;
        modal.show();
    });

    form.addEventListener('submit', saveRecord);

    $('#tblCatalogo').on('click', '.btn-edit', async function () {
        await loadAllSelects();
        editRecord(this.dataset.id);
    });

    $('#tblCatalogo').on('click', '.btn-delete', function () {
        deleteRecord(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush