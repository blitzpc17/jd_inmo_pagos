@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Apartados</h3>
            <div class="text-muted">Reserva temporal de lotes por 15 días</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoApartado">
            <i class="fa-solid fa-plus me-1"></i> Nuevo apartado
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblApartados">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Referencia</th>
                    <th>Cliente</th>
                    <th>Lotificación</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th>Importe</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalApartado" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formApartado">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo apartado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <select class="form-select select2-apartado" id="client_id" name="client_id"></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lotificación</label>
                            <select class="form-select select2-apartado" id="development_id" name="development_id"></select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Lotes disponibles</label>
                            <select class="form-select select2-apartado" id="lot_ids" name="lot_ids[]" multiple></select>
                            <div class="form-text">Solo se muestran lotes en estado libre.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Importe apartado</label>
                            <input type="number" step="0.01" class="form-control" id="importe_apartado" name="importe_apartado">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
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

<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de apartado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Referencia</label>
                        <input type="text" class="form-control" id="d_numero_referencia" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="d_cliente" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lotificación</label>
                        <input type="text" class="form-control" id="d_lotificacion" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha emisión</label>
                        <input type="text" class="form-control" id="d_fecha_emision" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Fecha vencimiento</label>
                        <input type="text" class="form-control" id="d_fecha_vencimiento" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Importe</label>
                        <input type="text" class="form-control" id="d_importe_apartado" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <input type="text" class="form-control" id="d_estado" readonly>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" id="d_observaciones" rows="3" readonly></textarea>
                    </div>
                </div>

                <div class="page-card">
                    <h6 class="fw-bold mb-3">Lotes apartados</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="tblDetalleLotes">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Identificador</th>
                                    <th>Manzana</th>
                                    <th>Precio contado</th>
                                    <th>Precio crédito</th>
                                </tr>
                            </thead>
                            <tbody id="detalleLotesBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modalApartado = new bootstrap.Modal(document.getElementById('modalApartado'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalle'));
    const form = document.getElementById('formApartado');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-apartado').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalApartado')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch('/apartados/options');
        optionsCache = await res.json();

        fillSelect('client_id', optionsCache.clients, false);
        fillSelect('development_id', optionsCache.developments, false);
        fillSelect('lot_ids', [], true);

        return optionsCache;
    }

    function fillSelect(id, items, multiple = false) {
        const el = document.getElementById(id);
        el.innerHTML = multiple ? '' : '<option value="">Seleccione...</option>';

        items.forEach(item => {
            let extra = '';
            if (item.precio_contado !== undefined) {
                extra = ` data-contado="${item.precio_contado}" data-credito="${item.precio_credito}"`;
            }
            el.innerHTML += `<option value="${item.value}"${extra}>${item.text}</option>`;
        });

        $(el).trigger('change');
    }

    async function loadLotsByDevelopment(developmentId) {
        fillSelect('lot_ids', [], true);
        if (!developmentId) return;

        const res = await fetch(`/apartados/development/${developmentId}/lots`);
        const items = await res.json();
        fillSelect('lot_ids', items, true);
    }

    function resetForm() {
        form.reset();
        $('#client_id').val(null).trigger('change');
        $('#development_id').val(null).trigger('change');
        $('#lot_ids').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblApartados').DataTable({
            ajax: { url: '/apartados/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'cliente' },
                { data: 'lotificacion' },
                { data: 'fecha_emision' },
                { data: 'fecha_vencimiento' },
                { data: 'importe_apartado' },
                { data: 'estado_badge', orderable: false, searchable: false },
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
        modalApartado.show();
    }

    async function viewItem(id) {
        const res = await fetch(`/apartados/${id}`);
        const json = await res.json();

        document.getElementById('d_numero_referencia').value = json.data.numero_referencia || '';
        document.getElementById('d_cliente').value = json.data.cliente || '';
        document.getElementById('d_lotificacion').value = json.data.lotificacion || '';
        document.getElementById('d_fecha_emision').value = json.data.fecha_emision || '';
        document.getElementById('d_fecha_vencimiento').value = json.data.fecha_vencimiento || '';
        document.getElementById('d_importe_apartado').value = json.data.importe_apartado || '';
        document.getElementById('d_estado').value = json.data.estado_nombre || '';
        document.getElementById('d_observaciones').value = json.data.observaciones || '';

        const tbody = document.getElementById('detalleLotesBody');
        tbody.innerHTML = '';

        (json.data.lots || []).forEach((lot, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${lot.identificador ?? ''}</td>
                    <td>${lot.manzana ?? ''}</td>
                    <td>${lot.precio_contado ?? ''}</td>
                    <td>${lot.precio_credito ?? ''}</td>
                </tr>
            `;
        });

        modalDetalle.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch('/apartados', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo guardar');

            modalApartado.hide();
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
        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Cancelar apartado?',
            text: 'Se liberarán los lotes asociados.',
            showCancelButton: true,
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        const res = await fetch(`/apartados/${id}`, {
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
            text: json.message || 'No se pudo cancelar'
        });

        if (res.ok) table.ajax.reload(null, false);
    }

    document.getElementById('btnNuevoApartado').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#development_id').on('change', function () {
        loadLotsByDevelopment(this.value);
    });

    $('#tblApartados').on('click', '.btn-view', function () {
        viewItem(this.dataset.id);
    });

    $('#tblApartados').on('click', '.btn-delete', function () {
        deleteItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush