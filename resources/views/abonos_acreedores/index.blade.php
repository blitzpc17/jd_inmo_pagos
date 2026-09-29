@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Abonos a Acreedores</h3>
            <div class="text-muted">Registro de abonos e intereses sobre boletas de acreedores</div>
        </div>
        <button class="btn btn-primary" id="btnNuevoAbonoAcreedor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo movimiento
        </button>
    </div>
</div>

<div class="modal fade" id="modalAbonoAcreedor">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formAbonoAcreedor">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Abono / Interés a Acreedor</h5>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a id="btnImprimirBoletaAcreedor" href="#" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="fa-solid fa-file-pdf me-1"></i> Estado de Cuenta
                    </a>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Acreedor</label>
                        <select class="form-select select2-abono-acreedor" id="creditor_id"></select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Boleta</label>
                        <select class="form-select select2-abono-acreedor" id="creditor_voucher_id" name="creditor_payment_id"></select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card bg-transparent border-0 mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie me-1"></i> Totales Generales</h6>
                                <div class="row g-3">
                                    <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Total Boleta</label><input type="text" class="form-control form-control-sm" id="s_total" readonly></div>
                                    <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Enganche</label><input type="text" class="form-control form-control-sm" id="s_enganche" readonly></div>
                                    <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Capital Pendiente</label><input type="text" class="form-control form-control-sm text-danger fw-bold" id="s_debe" readonly></div>
                                    <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Estado</label><input type="text" class="form-control form-control-sm fw-bold" id="s_estado" readonly></div>
                                    
                                    <div class="col-md-4 col-12 mt-4"><label class="form-label text-muted small mb-0">Interés Generado</label><input type="text" class="form-control form-control-sm text-warning fw-bold" id="s_int_acumulado" readonly></div>
                                    <div class="col-md-4 col-12 mt-4"><label class="form-label text-muted small mb-0">Interés Pagado</label><input type="text" class="form-control form-control-sm text-success fw-bold" id="s_int_pagado" readonly></div>
                                    <div class="col-md-4 col-12 mt-4"><label class="form-label text-muted small mb-0">Interés Pendiente</label><input type="text" class="form-control form-control-sm text-danger fw-bold" id="s_int_pendiente" readonly></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="globalVoucherAlert"></div>

                <div id="voucherContent" style="display: none;">
                    <div class="page-card mb-3" id="panelRegistrarAbonos">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                            <h6 class="fw-bold mb-0">Registrar movimientos</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddAbonoRow">
                                <i class="fa-solid fa-plus me-1"></i> Agregar fila
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0" id="tblAbonosRegistrar">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tipo de Operación</th>
                                        <th>Monto</th>
                                        <th>F. Movimiento</th>
                                        <th>Forma de pago</th>
                                        <th>Observaciones</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-success btn-sm" id="btnGuardarAbonos">
                                Guardar Operaciones
                            </button>
                        </div>
                    </div>

                    <div class="page-card mt-3">
                        <h6 class="fw-bold mb-3">Historial de Operaciones</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Monto</th>
                                        <th>Concepto/Obs.</th>
                                        <th>Usuario</th>
                                        <th>Recibo</th>
                                    </tr>
                                </thead>
                                <tbody id="historicoBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalAbonoAcreedor'));
    const form = document.getElementById('formAbonoAcreedor');
    let optionsCache = null;
    let currentVoucher = null;
    let rowIdx = 0; 

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
        optionsCache = await res.json();
        fillSelect('creditor_id', optionsCache.creditors || []);
        fillSelect('creditor_voucher_id', []);
        return optionsCache;
    }

    function paymentMethodOptionsHtml() {
        const methods = optionsCache?.payment_methods || [];
        let html = '<option value="">(Ninguna)</option>';
        methods.forEach(item => {
            html += `<option value="${item.value}">${item.text}</option>`;
        });
        return html;
    }

    function resetSummary() {
        ['s_total','s_enganche','s_debe','s_estado','s_int_acumulado','s_int_pagado','s_int_pendiente'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.getElementById('voucherContent').style.display = 'none';
        const globalAlert = document.getElementById('globalVoucherAlert');
        if (globalAlert) globalAlert.innerHTML = '';
        rowIdx = 0;
        document.querySelector('#tblAbonosRegistrar tbody').innerHTML = '';
    }

    function resetForm() {
        form.reset();
        $('.select2-abono-acreedor').val(null).trigger('change');
        fillSelect('creditor_voucher_id', []);
        currentVoucher = null;
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
            Swal.fire('Error', 'No se pudieron cargar las boletas.', 'error');
        }
    }

    const fCurrency = v => '$ ' + parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const fDate = d => {
        if (!d) return '';
        const parts = d.split(' ')[0].split('-');
        if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
        return d;
    };

    async function loadVoucherSummary(voucherId) {
        resetSummary();
        if (!voucherId) return;

        try {
            const res = await fetch(`/abonos-acreedores/voucher/${voucherId}/summary`);
            const json = await res.json();
            if (!res.ok) return;

            const d = json.data;
            currentVoucher = d;
            
            document.getElementById('btnImprimirBoletaAcreedor').href = `/pagos-acreedores/${voucherId}/pdf/boleta`;
            
            const prog = d.progress || {};

            document.getElementById('s_total').value = fCurrency(d.total || 0);
            document.getElementById('s_enganche').value = fCurrency(d.enganche || 0);
            document.getElementById('s_debe').value = fCurrency(prog.saldo_pendiente || 0);
            document.getElementById('s_estado').value = prog.estado_pago || d.estado;
            
            document.getElementById('s_int_acumulado').value = fCurrency(prog.interes_acumulado || 0);
            document.getElementById('s_int_pagado').value = fCurrency(prog.interes_pagado || 0);
            document.getElementById('s_int_pendiente').value = fCurrency(prog.interes_pendiente || 0);
            
            document.getElementById('voucherContent').style.display = 'block';

            const tbody = document.getElementById('historicoBody');
            tbody.innerHTML = '';

            let allItems = [];
            (d.items || []).forEach(i => {
                let rec = '';
                if(i.id) rec = `<a href="/pagos-acreedores/${voucherId}/pdf/recibo/${i.id}" target="_blank" class="btn btn-sm btn-outline-danger" title="Recibo PDF"><i class="fa-solid fa-file-pdf"></i></a>`;
                
                if (parseFloat(i.importe) > 0) {
                    allItems.push({ fecha: i.fecha, html: `
                        <tr>
                            <td>-</td>
                            <td>${fDate(i.fecha)}</td>
                            <td><span class="badge bg-success">Abono Capital</span></td>
                            <td class="fw-bold text-success">${fCurrency(i.importe)}</td>
                            <td>${i.concepto || ''}</td>
                            <td>${i.usuario_registro || '-'}</td>
                            <td>${rec}</td>
                        </tr>
                    `});
                }
                if (parseFloat(i.interes_pagado) > 0) {
                    allItems.push({ fecha: i.fecha, html: `
                        <tr>
                            <td>-</td>
                            <td>${fDate(i.fecha)}</td>
                            <td><span class="badge bg-warning text-dark">Pago Interés</span></td>
                            <td class="fw-bold text-warning">${fCurrency(i.interes_pagado)}</td>
                            <td>${i.concepto || ''}</td>
                            <td>${i.usuario_registro || '-'}</td>
                            <td>${rec}</td>
                        </tr>
                    `});
                }
            });
            
            (d.interests || []).forEach(i => {
                allItems.push({ fecha: i.fecha_registro, html: `
                    <tr>
                        <td>-</td>
                        <td>${fDate(i.fecha_registro)}</td>
                        <td><span class="badge bg-danger">Cargo Interés</span></td>
                        <td class="fw-bold text-danger">${fCurrency(i.cantidad)}</td>
                        <td>Generación Automática / Manual</td>
                        <td>${i.usuario_registro || '-'}</td>
                        <td></td>
                    </tr>
                `});
            });
            
            allItems.sort((a,b) => new Date(a.fecha) - new Date(b.fecha));
            
            if(allItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No hay movimientos.</td></tr>';
            } else {
                allItems.forEach((itm, idx) => {
                    let html = itm.html.replace('<td>-</td>', `<td>${idx+1}</td>`);
                    tbody.innerHTML += html;
                });
            }

            const isFullyPaid = (parseFloat(prog.saldo_pendiente) <= 0.01 && parseFloat(prog.interes_pendiente) <= 0.01);
            
            if (isFullyPaid) {
                document.getElementById('panelRegistrarAbonos').style.display = 'none';
                document.getElementById('globalVoucherAlert').innerHTML = `
                    <div class="alert alert-success mt-3 mb-4 text-center h5">
                        <i class="fa-solid fa-check-circle me-2"></i> <strong>BOLETA LIQUIDADA</strong>: Ya no presenta adeudos ni intereses por pagar.
                    </div>
                `;
            } else {
                document.getElementById('panelRegistrarAbonos').style.display = 'block';
                document.getElementById('globalVoucherAlert').innerHTML = '';
                addAbonoRow();
            }

        } catch (e) {
            console.error(e);
            Swal.fire('Error', 'No se pudo cargar el resumen de la boleta.', 'error');
        }
    }

    function addAbonoRow() {
        const tableBody = document.querySelector('#tblAbonosRegistrar tbody');
        if (!tableBody) return;
        
        rowIdx++;
        const today = new Date().toISOString().slice(0, 10);
        
        let prog = currentVoucher?.progress || {};
        let initialCapital = 0;
        let observacionStr = '';
        let tipoAbonoSelected = 'selected';
        let tipoInteresSelected = '';
        let montoClass = 'text-success';

        let alreadyAllocatedCapital = 0;
        let alreadyAllocatedInterest = 0;
        tableBody.querySelectorAll('tr').forEach(row => {
            let tipo = row.querySelector('.item-tipo')?.value;
            let val = parseFloat(row.querySelector('.item-monto')?.value || 0);
            if(tipo === 'abono_capital') alreadyAllocatedCapital += val;
            if(tipo === 'pago_interes') alreadyAllocatedInterest += val;
        });

        let saldo = parseFloat(prog.saldo_pendiente || 0);
        let intPendiente = parseFloat(prog.interes_pendiente || 0);

        if (saldo > alreadyAllocatedCapital) {
            initialCapital = (saldo - alreadyAllocatedCapital).toFixed(2);
        } else if (intPendiente > alreadyAllocatedInterest) {
            initialCapital = (intPendiente - alreadyAllocatedInterest).toFixed(2);
            observacionStr = 'Pago de interés';
            tipoAbonoSelected = '';
            tipoInteresSelected = 'selected';
            montoClass = 'text-danger';
        }

        let disableAbonoCapital = (saldo <= 0.01) ? 'disabled style="display:none;"' : '';

        tableBody.insertAdjacentHTML('beforeend', `
            <tr data-row="${rowIdx}">
                <td>${rowIdx}</td>
                <td style="min-width: 160px;">
                    <select class="form-select form-select-sm item-tipo">
                        <option value="abono_capital" ${tipoAbonoSelected} ${disableAbonoCapital}>Abono a Saldo</option>
                        <option value="pago_interes" ${tipoInteresSelected}>Pago de Interés</option>
                        <option value="generar_interes">Generar Interés (Cargo)</option>
                    </select>
                </td>
                <td style="min-width: 130px;"><input type="number" step="0.01" class="form-control form-control-sm ${montoClass} fw-bold item-monto" data-default-amount="${initialCapital}" value="${initialCapital}"></td>
                <td style="min-width: 150px;"><input type="date" class="form-control form-control-sm item-fecha" value="${today}"></td>
                <td style="min-width: 160px;"><select class="form-select form-select-sm item-payment-method">${paymentMethodOptionsHtml()}</select></td>
                <td style="min-width: 160px;"><input type="text" class="form-control form-control-sm item-observacion" value="${observacionStr}" placeholder="Opcional"></td>
                <td class="text-center" style="min-width: 60px;">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    }

    document.getElementById('btnGuardarAbonos').addEventListener('click', async () => {
        const voucherId = document.getElementById('creditor_voucher_id').value;
        if (!voucherId) return Swal.fire({ icon: 'warning', title: 'Selecciona una boleta' });
        
        const tableBody = document.querySelector('#tblAbonosRegistrar tbody');
        const rows = tableBody.querySelectorAll('tr');
        const items = [];
        let totalCapitalToPay = 0;
        let totalInterestToPay = 0;

        rows.forEach(row => {
            const tipo = row.querySelector('.item-tipo')?.value || 'abono_capital';
            const monto = parseFloat(row.querySelector('.item-monto')?.value || 0);
            const fecha_recibido = row.querySelector('.item-fecha')?.value || '';
            const payment_method_id = row.querySelector('.item-payment-method')?.value || '';
            const observaciones = row.querySelector('.item-observacion')?.value || null;

            if (fecha_recibido && monto > 0) {
                if (tipo !== 'generar_interes' && !payment_method_id) {
                    return; 
                }

                items.push({
                    tipo,
                    monto,
                    fecha_recibido,
                    payment_method_id: payment_method_id ? parseInt(payment_method_id, 10) : null,
                    observaciones
                });

                if (tipo === 'abono_capital') totalCapitalToPay += monto;
                else if (tipo === 'pago_interes') totalInterestToPay += monto;
            }
        });

        if (!items.length) {
            return Swal.fire({ icon: 'warning', title: 'Debes capturar al menos una operación válida con monto > 0 y forma de pago' });
        }

        const prog = currentVoucher?.progress || {};
        const remainingCapital = parseFloat(prog.saldo_pendiente || 0);
        if (parseFloat(totalCapitalToPay.toFixed(2)) > parseFloat(remainingCapital.toFixed(2))) {
            return Swal.fire({ 
                icon: 'warning', 
                title: 'Monto Excedido', 
                text: `El abono a capital ($${totalCapitalToPay.toLocaleString()}) excede el saldo pendiente ($${remainingCapital.toLocaleString()}).`
            });
        }

        try {
            const res = await fetch('/abonos-acreedores', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    creditor_payment_id: voucherId,
                    items
                })
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo guardar');

            Swal.fire({ icon: 'success', title: 'Correcto', text: json.message, timer: 1600, showConfirmButton: false });
            await loadVoucherSummary(voucherId);

        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });

    $(document).on('change', '.item-tipo', function() {
        const tr = $(this).closest('tr');
        const tipo = $(this).val();
        const paymentMethod = tr.find('.item-payment-method');
        const montoInput = tr.find('.item-monto');

        if (tipo === 'generar_interes') {
            paymentMethod.hide();
            paymentMethod.val('');
            montoInput.removeClass('text-success').addClass('text-danger');
            montoInput.val('0.00');
        } else if (tipo === 'pago_interes') {
            paymentMethod.show();
            paymentMethod.prop('disabled', false);
            montoInput.removeClass('text-success').addClass('text-danger');
            montoInput.val('0.00');
        } else {
            paymentMethod.show();
            paymentMethod.prop('disabled', false);
            montoInput.removeClass('text-danger').addClass('text-success');
            montoInput.val(montoInput.data('default-amount'));
        }
    });

    $(document).on('click', '.btn-remove-item', function() {
        $(this).closest('tr').remove();
    });

    async function openNew() {
        await loadOptions();
        resetForm();
        modal.show();
    }

    document.getElementById('btnNuevoAbonoAcreedor').addEventListener('click', openNew);
    document.getElementById('btnAddAbonoRow').addEventListener('click', addAbonoRow);
    
    form.addEventListener('submit', e => e.preventDefault());

    $('#creditor_id').on('change', function () {
        loadVouchers(this.value);
    });

    $('#creditor_voucher_id').on('change', function () {
        loadVoucherSummary(this.value);
    });

    initSelect2();
})();
</script>
@endpush
