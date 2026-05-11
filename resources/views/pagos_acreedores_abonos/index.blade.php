@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Abonos acreedores</h3>
            <div class="text-muted">Registro de pagos sobre boletas existentes</div>
        </div>
        <button class="btn btn-primary" id="btnNuevoAbonoAcreedor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo abono
        </button>
    </div>
</div>

<div class="modal fade" id="modalAbonoAcreedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formAbonoAcreedor">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar abono acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Acreedor</label>
                            <select class="form-select select2-abono-acreedor" id="creditor_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Boleta</label>
                            <select class="form-select select2-abono-acreedor" id="creditor_voucher_id" name="creditor_voucher_id"></select>
                        </div>
                    </div>

                    <div class="page-card mb-3">
                        <h6 class="fw-bold mb-3">Resumen boleta</h6>
                        <div class="row g-3">
                            <div class="col-md-3"><label class="form-label">Referencia</label><input type="text" class="form-control" id="s_ref" readonly></div>
                            <div class="col-md-3"><label class="form-label">Acreedor</label><input type="text" class="form-control" id="s_acreedor" readonly></div>
                            <div class="col-md-2"><label class="form-label">Total</label><input type="text" class="form-control" id="s_total" readonly></div>
                            <div class="col-md-2"><label class="form-label">Mensualidad</label><input type="text" class="form-control" id="s_mensualidad" readonly></div>
                            <div class="col-md-2"><label class="form-label">Meses</label><input type="text" class="form-control" id="s_meses" readonly></div>

                            <div class="col-md-3"><label class="form-label">Pagado</label><input type="text" class="form-control" id="s_pagado" readonly></div>
                            <div class="col-md-3"><label class="form-label">Debe</label><input type="text" class="form-control" id="s_debe" readonly></div>
                            <div class="col-md-3"><label class="form-label">Debería llevar</label><input type="text" class="form-control" id="s_deberia" readonly></div>
                            <div class="col-md-3"><label class="form-label">Estado pago</label><input type="text" class="form-control" id="s_estado_pago" readonly></div>
                        </div>
                    </div>

                    <div class="page-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">Abonos a registrar</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddAbonoAcreedor">
                                <i class="fa-solid fa-plus me-1"></i> Agregar abono
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha recibido</th>
                                        <th>Forma de pago</th>
                                        <th>Cantidad</th>
                                        <th>Usuario registro</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="abonoAcreedorItemsBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="page-card mt-3">
                        <h6 class="fw-bold mb-3">Pagos registrados</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha recibido</th>
                                        <th>Forma de pago</th>
                                        <th>Cantidad</th>
                                        <th>Usuario registro</th>
                                    </tr>
                                </thead>
                                <tbody id="historicoAbonosBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" type="submit">Guardar abonos</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalAbonoAcreedor'));
    const form = document.getElementById('formAbonoAcreedor');
    let optionsCache = null;
    let rowIndex = 0;

    function initSelect2() {
        $('.select2-abono-acreedor').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalAbonoAcreedor')
        });
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        (items || []).forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
        $(el).trigger('change.select2');
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch('/abonos-acreedores/options');
        const json = await res.json();
        optionsCache = json;

        fillSelect('creditor_id', json.creditors || []);
        fillSelect('creditor_voucher_id', []);

        return optionsCache;
    }

    function paymentMethodOptionsHtml() {
        const methods = optionsCache?.payment_methods || [];
        let html = '<option value="">Seleccione...</option>';
        methods.forEach(item => {
            html += `<option value="${item.value}">${item.text}</option>`;
        });
        return html;
    }

    function resetSummary() {
        ['s_ref','s_acreedor','s_total','s_mensualidad','s_meses','s_pagado','s_debe','s_deberia','s_estado_pago']
            .forEach(id => document.getElementById(id).value = '');
        document.getElementById('historicoAbonosBody').innerHTML = '';
    }

    function resetForm() {
        form.reset();
        $('.select2-abono-acreedor').val(null).trigger('change');
        fillSelect('creditor_voucher_id', []);
        document.getElementById('abonoAcreedorItemsBody').innerHTML = '';
        rowIndex = 0;
        addItem();
        resetSummary();
    }

    async function loadVouchers(creditorId) {
        fillSelect('creditor_voucher_id', []);
        resetSummary();

        if (!creditorId) return;

        try {
            const res = await fetch(`/abonos-acreedores/creditor/${creditorId}/vouchers`);
            const rows = await res.json();
            fillSelect('creditor_voucher_id', rows || []);
        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las boletas del acreedor.'
            });
        }
    }

    async function loadVoucherSummary(voucherId) {
        resetSummary();
        if (!voucherId) return;

        try {
            const res = await fetch(`/abonos-acreedores/voucher/${voucherId}/summary`);
            const json = await res.json();
            if (!res.ok) return;

            const d = json.data;
            document.getElementById('s_ref').value = d.numero_referencia || '';
            document.getElementById('s_acreedor').value = d.acreedor || '';
            document.getElementById('s_total').value = d.total || '';
            document.getElementById('s_mensualidad').value = d.mensualidad || '';
            document.getElementById('s_meses').value = d.meses || '';
            document.getElementById('s_pagado').value = d.total_pagado || '';
            document.getElementById('s_debe').value = d.saldo_pendiente || '';
            document.getElementById('s_deberia').value = d.deberia_llevar || '';
            document.getElementById('s_estado_pago').value = d.estado_pago || '';

            const tbody = document.getElementById('historicoAbonosBody');
            tbody.innerHTML = '';

            (d.items || []).forEach((item, index) => {
                tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.fecha_recibido ?? ''}</td>
                        <td>${item.forma_pago ?? ''}</td>
                        <td>${item.cantidad ?? ''}</td>
                        <td>${item.usuario_registro ?? ''}</td>
                        <td>
                            <a class="btn btn-sm btn-outline-danger" target="_blank" href="/abonos-acreedores/recibo/${item.id}" title="Recibo PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                `;
            });
            
        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cargar el resumen de la boleta.'
            });
        }
    }

    function addItem() {
        rowIndex++;
        const today = new Date().toISOString().slice(0, 10);

        document.getElementById('abonoAcreedorItemsBody').insertAdjacentHTML('beforeend', `
            <tr data-row="${rowIndex}">
                <td>${rowIndex}</td>
                <td><input type="date" class="form-control item-fecha" value="${today}"></td>
                <td><select class="form-select item-payment-method">${paymentMethodOptionsHtml()}</select></td>
                <td><input type="number" step="0.01" class="form-control item-cantidad"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    }

    function buildPayload() {
        const rows = document.querySelectorAll('#abonoAcreedorItemsBody tr');
        const items = [];

        rows.forEach(row => {
            const fecha_recibido = row.querySelector('.item-fecha')?.value || '';
            const payment_method_id = row.querySelector('.item-payment-method')?.value || '';
            const cantidad = parseFloat(row.querySelector('.item-cantidad')?.value || 0);

            if (fecha_recibido && payment_method_id && cantidad > 0) {
                items.push({
                    fecha_recibido,
                    payment_method_id: parseInt(payment_method_id, 10),
                    cantidad
                });
            }
        });

        return items;
    }

    async function openNew() {
        await loadOptions();
        resetForm();
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const voucherId = document.getElementById('creditor_voucher_id').value;
        if (!voucherId) {
            return Swal.fire({ icon: 'warning', title: 'Selecciona una boleta' });
        }

        const items = buildPayload();
        if (!items.length) {
            return Swal.fire({ icon: 'warning', title: 'Debes capturar al menos un abono válido' });
        }

        const payload = {
            creditor_voucher_id: voucherId,
            items
        };

        try {
            const res = await fetch('/abonos-acreedores', {
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

            await loadVoucherSummary(voucherId);

            document.getElementById('abonoAcreedorItemsBody').innerHTML = '';
            rowIndex = 0;
            addItem();

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1600,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    document.getElementById('btnNuevoAbonoAcreedor').addEventListener('click', openNew);
    document.getElementById('btnAddAbonoAcreedor').addEventListener('click', addItem);
    form.addEventListener('submit', saveItem);

    $('#creditor_id').on('change', function () {
        loadVouchers(this.value);
    });

    $('#creditor_voucher_id').on('change', function () {
        loadVoucherSummary(this.value);
    });

    document.getElementById('abonoAcreedorItemsBody').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-item');
        if (!btn) return;
        btn.closest('tr').remove();
    });

    initSelect2();
})();
</script>
@endpush