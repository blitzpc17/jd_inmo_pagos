@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Pagos a proveedores</h3>
            <div class="text-muted">Registro de salidas y conceptos por proveedor</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoPagoProveedor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo pago
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblPagosProveedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Referencia</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Forma pago</th>
                    <th>Importe</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalPagoProveedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formPagoProveedor">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo pago a proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Proveedor</label>
                            <select class="form-select select2-pp" id="supplier_id" name="supplier_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Forma de pago</label>
                            <select class="form-select select2-pp" id="payment_method_id" name="payment_method_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-control" id="fecha" name="fecha">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Observación</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="page-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Conceptos</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddConcepto">
                                <i class="fa-solid fa-plus me-1"></i> Agregar concepto
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th>Concepto</th>
                                        <th style="width:180px;">Importe</th>
                                        <th style="width:80px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="ppItemsBody"></tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2" class="text-end">Total</th>
                                        <th>
                                            <input type="text" class="form-control" id="importe_total" readonly>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
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

<div class="modal fade" id="modalDetallePagoProveedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle pago a proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><label class="form-label">Referencia</label><input type="text" class="form-control" id="dpp_ref" readonly></div>
                    <div class="col-md-3"><label class="form-label">Fecha</label><input type="text" class="form-control" id="dpp_fecha" readonly></div>
                    <div class="col-md-3"><label class="form-label">Proveedor</label><input type="text" class="form-control" id="dpp_proveedor" readonly></div>
                    <div class="col-md-3"><label class="form-label">Forma pago</label><input type="text" class="form-control" id="dpp_forma_pago" readonly></div>
                    <div class="col-md-3"><label class="form-label">Importe</label><input type="text" class="form-control" id="dpp_importe" readonly></div>
                    <div class="col-md-3"><label class="form-label">Estado</label><input type="text" class="form-control" id="dpp_estado" readonly></div>
                    <div class="col-md-12"><label class="form-label">Observación</label><textarea class="form-control" id="dpp_observacion" rows="2" readonly></textarea></div>
                </div>

                <div class="page-card">
                    <h6 class="fw-bold mb-3">Conceptos</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Concepto</th>
                                    <th>Importe</th>
                                </tr>
                            </thead>
                            <tbody id="dppItemsBody"></tbody>
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
    const modal = new bootstrap.Modal(document.getElementById('modalPagoProveedor'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetallePagoProveedor'));
    const form = document.getElementById('formPagoProveedor');
    const body = document.getElementById('ppItemsBody');

    let table = null;
    let optionsCache = null;
    let rowIndex = 0;

    function initSelect2() {
        $('.select2-pp').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalPagoProveedor')
        });
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
        $(el).trigger('change');
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch('/pagos-proveedores/options');
        optionsCache = await res.json();

        fillSelect('supplier_id', optionsCache.suppliers);
        fillSelect('payment_method_id', optionsCache.payment_methods);

        return optionsCache;
    }

    function addItem(concepto = '', importe = '') {
        rowIndex++;

        body.insertAdjacentHTML('beforeend', `
            <tr data-row="${rowIndex}">
                <td>${rowIndex}</td>
                <td>
                    <input type="text" class="form-control item-concepto" value="${concepto}">
                </td>
                <td>
                    <input type="number" step="0.01" class="form-control item-importe" value="${importe}">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);

        recalcTotal();
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.item-importe').forEach(input => {
            total += parseFloat(input.value || 0);
        });
        document.getElementById('importe_total').value = total.toFixed(2);
    }

    function resetForm() {
        form.reset();
        $('.select2-pp').val(null).trigger('change');
        body.innerHTML = '';
        rowIndex = 0;
        addItem();
        document.getElementById('fecha').value = new Date().toISOString().slice(0, 10);
    }

    function buildPayload() {
        const items = [];
        const conceptos = document.querySelectorAll('.item-concepto');
        const importes = document.querySelectorAll('.item-importe');

        for (let i = 0; i < conceptos.length; i++) {
            const concepto = conceptos[i].value.trim();
            const importe = parseFloat(importes[i].value || 0);

            if (concepto !== '' && importe > 0) {
                items.push({ concepto, importe });
            }
        }

        return items;
    }

    function initTable() {
        table = $('#tblPagosProveedores').DataTable({
            ajax: { url: '/pagos-proveedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'fecha' },
                { data: 'proveedor' },
                { data: 'forma_pago' },
                { data: 'importe' },
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
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const items = buildPayload();
        if (!items.length) {
            return Swal.fire({ icon: 'warning', title: 'Debes capturar al menos un concepto' });
        }

        const payload = {
            supplier_id: document.getElementById('supplier_id').value,
            payment_method_id: document.getElementById('payment_method_id').value,
            fecha: document.getElementById('fecha').value,
            observacion: document.getElementById('observacion').value,
            items
        };

        try {
            const res = await fetch('/pagos-proveedores', {
                method: 'POST',
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

    async function viewItem(id) {
        const res = await fetch(`/pagos-proveedores/${id}`);
        const json = await res.json();

        $('#dpp_ref').val(json.data.numero_referencia || '');
        $('#dpp_fecha').val(json.data.fecha || '');
        $('#dpp_proveedor').val(json.data.proveedor || '');
        $('#dpp_forma_pago').val(json.data.forma_pago || '');
        $('#dpp_importe').val(json.data.importe || '');
        $('#dpp_estado').val(json.data.estado || '');
        $('#dpp_observacion').val(json.data.observacion || '');

        const tbody = document.getElementById('dppItemsBody');
        tbody.innerHTML = '';

        (json.data.items || []).forEach((item, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.concepto ?? ''}</td>
                    <td>${item.importe ?? ''}</td>
                </tr>
            `;
        });

        modalDetalle.show();
    }

    document.getElementById('btnNuevoPagoProveedor').addEventListener('click', openNew);
    document.getElementById('btnAddConcepto').addEventListener('click', () => addItem());
    form.addEventListener('submit', saveItem);

    body.addEventListener('input', function(e) {
        if (e.target.classList.contains('item-importe')) {
            recalcTotal();
        }
    });

    body.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-item');
        if (!btn) return;

        btn.closest('tr').remove();
        recalcTotal();
    });

    $('#tblPagosProveedores').on('click', '.btn-view', function () {
        viewItem(this.dataset.id);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush