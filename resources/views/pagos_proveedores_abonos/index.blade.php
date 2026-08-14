@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Abonos proveedores</h3>
            <div class="text-muted">Registro de pagos sobre boletas existentes</div>
        </div>
        <button class="btn btn-primary" id="btnNuevoAbonoProveedor">
            <i class="fa-solid fa-plus me-1"></i> Nuevo abono
        </button>
    </div>
</div>

<div class="modal fade" id="modalAbonoProveedor">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formAbonoProveedor">
            <div class="modal-header">
                <h5 class="modal-title">Registrar abono proveedor</h5>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a id="btnImprimirBoletaProveedor" href="#" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="fa-solid fa-file-pdf me-1"></i> Imprimir Boleta
                    </a>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                </div>
            </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Proveedor</label>
                            <select class="form-select select2-abono-proveedor" id="supplier_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Boleta</label>
                            <select class="form-select select2-abono-proveedor" id="supplier_voucher_id" name="supplier_voucher_id"></select>
                        </div>
                    </div>

                    <div class="row">
                        <!-- PANEL GENERAL -->
                        <div class="col-md-12">
                            <div class="card bg-transparent border-0 mb-3">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie me-1"></i> Totales Generales Boleta</h6>
                                    <div class="row g-3">
                                        <div class="col-md-2 col-6"><label class="form-label text-muted small mb-0">Total Boleta</label><input type="text" class="form-control form-control-sm" id="s_total" readonly></div>
                                        <div class="col-md-2 col-6"><label class="form-label text-muted small mb-0">Enganche Total</label><input type="text" class="form-control form-control-sm" id="s_enganche" readonly></div>
                                        <div class="col-md-2 col-6"><label class="form-label text-muted small mb-0">Meses</label><input type="text" class="form-control form-control-sm" id="s_meses" readonly></div>
                                        <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Capital Pendiente</label><input type="text" class="form-control form-control-sm text-danger fw-bold" id="s_debe" readonly></div>
                                        <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Letra Mensual</label><input type="text" class="form-control form-control-sm text-primary fw-bold" id="s_mensualidad" readonly></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑAS POR SOCIO -->
                    <div id="globalVoucherAlert"></div>
                    <ul class="nav nav-tabs mb-3" id="partnerTabs" role="tablist"></ul>
                    <div class="tab-content" id="partnerTabsContent">
                        <div class="text-muted text-center p-4">Seleccione una boleta para ver los detalles de los socios.</div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                    <button class="btn btn-primary" type="submit">Guardar abonos</button>
                </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalAbonoProveedor'));
    const form = document.getElementById('formAbonoProveedor');
    let optionsCache = null;
    let currentVoucher = null;
    
    // Track row indices per partner
    let rowIndices = {}; 

    function initSelect2() {
        $('.select2-abono-proveedor').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalAbonoProveedor')
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
        const res = await fetch('/abonos-proveedores/options');
        optionsCache = await res.json();
        fillSelect('supplier_id', optionsCache.suppliers || []);
        fillSelect('supplier_voucher_id', []);
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
        ['s_total','s_mensualidad','s_meses','s_debe','s_enganche'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.getElementById('partnerTabs').innerHTML = '';
        document.getElementById('partnerTabsContent').innerHTML = '<div class="text-muted text-center p-4">Seleccione una boleta para ver los detalles de los socios.</div>';
        const globalAlert = document.getElementById('globalVoucherAlert');
        if (globalAlert) globalAlert.innerHTML = '';
        rowIndices = {};
    }

    function resetForm() {
        form.reset();
        $('.select2-abono-proveedor').val(null).trigger('change');
        fillSelect('supplier_voucher_id', []);
        currentVoucher = null;
        resetSummary();
    }

    async function loadVouchers(supplierId) {
        fillSelect('supplier_voucher_id', []);
        resetSummary();
        if (!supplierId) return;
        try {
            const res = await fetch(`/abonos-proveedores/supplier/${supplierId}/vouchers`);
            const rows = await res.json();
            fillSelect('supplier_voucher_id', rows || []);
        } catch (e) {
            Swal.fire('Error', 'No se pudieron cargar las boletas.', 'error');
        }
    }

    const fCurrency = v => '$ ' + parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    async function loadVoucherSummary(voucherId) {
        resetSummary();
        if (!voucherId) return;

        try {
            const res = await fetch(`/abonos-proveedores/voucher/${voucherId}/summary`);
            const json = await res.json();
            if (!res.ok) return;

            const d = json.data;
            currentVoucher = d;
            
            document.getElementById('btnImprimirBoletaProveedor').href = `/abonos-proveedores/${voucherId}/pdf/boleta`;
            
            // Generales
            document.getElementById('s_total').value = fCurrency(d.total || 0);
            document.getElementById('s_enganche').value = fCurrency(d.enganche || 0);
            document.getElementById('s_debe').value = fCurrency(d.saldo_pendiente || 0);
            document.getElementById('s_meses').value = d.meses || 1;
            document.getElementById('s_mensualidad').value = fCurrency(d.mensualidad || 0);
            
            // Tabs Generacion
            const tabsUl = document.getElementById('partnerTabs');
            const tabsContent = document.getElementById('partnerTabsContent');
            tabsUl.innerHTML = '';
            tabsContent.innerHTML = '';
            rowIndices = {};
            
            const partners = d.partners || [];
            let allPartnersPaid = true;
            
            partners.forEach((p, index) => {
                const isActive = index === 0 ? 'active' : '';
                const tabId = `tab-partner-${p.id}`;
                
                rowIndices[p.id] = 0; // Initialize row index for this partner
                
                const prog = p.progress || {};
                const isFullyPaid = (parseFloat(prog.saldo_pendiente) <= 0.01 && parseFloat(prog.interes_pendiente) <= 0.01);
                if (!isFullyPaid) allPartnersPaid = false;

                const badgeTitular = p.es_titular ? '<span class="badge bg-primary ms-1" style="font-size:0.6rem;">TITULAR</span>' : '';
                const badgePagado = isFullyPaid ? '<span class="badge bg-success ms-1" style="font-size:0.6rem;">PAGADO</span>' : '';
                
                tabsUl.innerHTML += `
                    <li class="nav-item" role="presentation">
                        <button class="nav-link ${isActive}" id="${tabId}-tab" data-bs-toggle="tab" data-bs-target="#${tabId}" type="button" role="tab">
                            ${p.nombre} (${parseFloat(p.porcentaje).toFixed(2)}%) ${badgeTitular} ${badgePagado}
                        </button>
                    </li>
                `;
                const historicoHtml = (p.items || []).map((item, idx) => `
                    <tr>
                        <td>${idx + 1}</td>
                        <td>${item.fecha_pago_programada ?? ''}</td>
                        <td>${fCurrency(item.cantidad_a_pagar ?? 0)}</td>
                        <td>${item.fecha_recibido ?? ''}</td>
                        <td><span class="text-success fw-bold">${fCurrency(item.cantidad ?? 0)}</span></td>
                        <td><span class="text-danger">${fCurrency(item.interes_pagado ?? 0)}</span></td>
                        <td>${item.forma_pago ?? ''}</td>
                        <td>${item.observaciones ?? ''}</td>
                        <td>
                            <a class="btn btn-sm btn-outline-danger" target="_blank" href="/abonos-proveedores/${voucherId}/pdf/recibo/${item.id}" title="Recibo PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                `).join('');
                
                let schedulesHtml = '<tr><td colspan="6" class="text-muted">No hay calendario de pagos generado.</td></tr>';
                if (p.schedules && p.schedules.length > 0) {
                    schedulesHtml = p.schedules.map(sc => {
                        let badge = sc.status === 'PAID' ? '<span class="badge bg-success">PAGADO</span>' : (sc.status === 'PARTIAL' ? '<span class="badge bg-warning text-dark">PARCIAL</span>' : '<span class="badge bg-secondary">PENDIENTE</span>');
                        const pend = Math.max(0, parseFloat(sc.amount) - parseFloat(sc.amount_paid));
                        return `
                            <tr>
                                <td>${sc.installment_number}</td>
                                <td>${sc.due_date}</td>
                                <td>${fCurrency(sc.amount)}</td>
                                <td><span class="text-success">${fCurrency(sc.amount_paid)}</span></td>
                                <td><span class="text-danger">${fCurrency(pend)}</span></td>
                                <td>${badge}</td>
                            </tr>
                        `;
                    }).join('');
                }
                
                tabsContent.innerHTML += `
                    <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="${tabId}" role="tabpanel" tabindex="0">
                        <!-- Panel de Totales del Socio -->
                        <div class="card bg-transparent border-0 mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-user me-1"></i> Totales del Socio</h6>
                                <div class="row g-3">
                                    <div class="col-md-2 col-6"><label class="form-label text-muted small mb-0">Total Asignado</label><input type="text" class="form-control form-control-sm" value="${fCurrency(prog.capital_total || 0)}" readonly></div>
                                    <div class="col-md-2 col-6"><label class="form-label text-muted small mb-0">Enganche Asignado</label><input type="text" class="form-control form-control-sm" value="${fCurrency(prog.enganche || 0)}" readonly></div>
                                    <div class="col-md-2 col-6"><label class="form-label text-muted small mb-0">Meses</label><input type="text" class="form-control form-control-sm" value="${prog.meses_exigibles || 1}" readonly></div>
                                    <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Capital Pendiente</label><input type="text" class="form-control form-control-sm text-danger fw-bold" value="${fCurrency(prog.saldo_pendiente || 0)}" readonly></div>
                                    <div class="col-md-3 col-6"><label class="form-label text-muted small mb-0">Estado de Pago</label><input type="text" class="form-control form-control-sm fw-bold" value="${prog.estado_pago || ''}" readonly></div>
                                    
                                    <div class="col-md-4 col-12 mt-4"><label class="form-label text-muted small mb-0">Interés Generado (Total)</label><input type="text" class="form-control form-control-sm text-warning fw-bold" value="${fCurrency(prog.interes_acumulado || 0)}" readonly></div>
                                    <div class="col-md-4 col-12 mt-4"><label class="form-label text-muted small mb-0">Interés Pagado</label><input type="text" class="form-control form-control-sm text-success fw-bold" value="${fCurrency(prog.interes_pagado || 0)}" readonly></div>
                                    <div class="col-md-4 col-12 mt-4"><label class="form-label text-muted small mb-0">Interés Pendiente</label><input type="text" class="form-control form-control-sm text-danger fw-bold" value="${fCurrency(prog.interes_pendiente || 0)}" readonly></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Calendario -->
                        <div class="page-card mb-3">
                            <h6 class="fw-bold mb-3"><i class="fa-solid fa-calendar-alt me-1"></i> Calendario de Pagos del Socio</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle mb-0 text-center" style="font-size: 0.85rem;">
                                    <thead>
                                        <tr>
                                            <th># Pago</th>
                                            <th>F. Programada</th>
                                            <th>Cantidad</th>
                                            <th>Abonado</th>
                                            <th>Pendiente</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>${schedulesHtml}</tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Abonos a registrar -->
                        ${isFullyPaid ? `
                        <div class="alert alert-success text-center fw-bold mt-4">
                            <i class="fa-solid fa-check-circle me-2"></i> Este socio ya no presenta adeudos ni intereses por pagar.
                        </div>
                        ` : `
                        <div class="page-card mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <h6 class="fw-bold mb-0">Registrar movimientos - ${p.nombre}</h6>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-abono" data-partner="${p.id}">
                                        <i class="fa-solid fa-plus me-1"></i> Agregar movimiento
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0 table-abonos-registrar" data-partner="${p.id}">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Tipo de Entrada</th>
                                            <th>F. Programada</th>
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
                                <button type="button" class="btn btn-success btn-sm btn-guardar-abonos" data-partner="${p.id}">
                                    Guardar abonos de ${p.nombre}
                                </button>
                            </div>
                        </div>
                        `}

                        <!-- Historial -->
                        <div class="page-card mt-3">
                            <h6 class="fw-bold mb-3">Pagos registrados - ${p.nombre}</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th># Pago</th>
                                            <th>F. Programada</th>
                                            <th>Cant. a Pagar</th>
                                            <th>F. de Pago (Real)</th>
                                            <th>Monto Pagado (Abono)</th>
                                            <th>Interés Pagado</th>
                                            <th>Forma de pago</th>
                                            <th>Observaciones</th>
                                            <th>Recibo</th>
                                        </tr>
                                    </thead>
                                    <tbody>${historicoHtml}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Auto add one row for each partner
            partners.forEach(p => {
                addItemToPartner(p.id);
            });
            
            const globalAlert = document.getElementById('globalVoucherAlert');
            if (globalAlert) {
                if (allPartnersPaid && partners.length > 0) {
                    globalAlert.innerHTML = `
                        <div class="alert alert-success mt-3 mb-4 text-center h5">
                            <i class="fa-solid fa-check-circle me-2"></i> <strong>BOLETA LIQUIDADA</strong>: Todos los socios han saldado su deuda e intereses.
                        </div>
                    `;
                    // Optional: hide tabs if you want, but showing history is nice.
                } else {
                    globalAlert.innerHTML = '';
                }
            }
            
        } catch (e) {
            console.error(e);
            Swal.fire('Error', 'No se pudo cargar el resumen de la boleta.', 'error');
        }
    }

    function addItemToPartner(partnerId) {
        const tableBody = document.querySelector(`.table-abonos-registrar[data-partner="${partnerId}"] tbody`);
        if (!tableBody) return;
        
        rowIndices[partnerId]++;
        const rowIdx = rowIndices[partnerId];
        const today = new Date().toISOString().slice(0, 10);
        
        let progStr = today;
        let cantPagar = '';
        let initialCapital = '';
        let initialInterest = '';
        let observacionStr = '';
        
        const p = currentVoucher?.partners?.find(x => x.id == partnerId);
        if (p && p.schedules) {
            let alreadyAllocatedCapital = 0;
            let alreadyAllocatedInterest = 0;
            tableBody.querySelectorAll('tr').forEach(row => {
                alreadyAllocatedCapital += parseFloat(row.querySelector('.item-cantidad')?.value || 0);
                alreadyAllocatedInterest += parseFloat(row.querySelector('.item-interes')?.value || 0);
            });

            let pendingAmount = 0;
            let targetSchedule = null;

            for (const sc of p.schedules) {
                if (sc.status !== 'PAID' && sc.status !== 'PAGADO') {
                    let schedulePending = parseFloat(sc.amount) - parseFloat(sc.amount_paid);
                    if (alreadyAllocatedCapital >= schedulePending) {
                        alreadyAllocatedCapital -= schedulePending;
                    } else {
                        schedulePending -= alreadyAllocatedCapital;
                        alreadyAllocatedCapital = 0;
                        targetSchedule = sc;
                        pendingAmount = schedulePending;
                        break;
                    }
                }
            }
            if (targetSchedule) {
                progStr = targetSchedule.due_date;
                cantPagar = pendingAmount.toFixed(2);
                initialCapital = cantPagar;
            } else {
                let interesPendiente = parseFloat(p.progress?.interes_pendiente) || 0;
                if (interesPendiente > alreadyAllocatedInterest) {
                    cantPagar = (interesPendiente - alreadyAllocatedInterest).toFixed(2);
                    initialCapital = cantPagar;
                    observacionStr = 'Pago de interés';
                    progStr = today;
                }
            }
        }

        let tipoAbonoSelected = 'selected';
        let tipoInteresSelected = '';
        let montoClass = 'text-success';
        let defaultMonto = initialCapital; // Store the original calculated capital

        if (observacionStr === 'Pago de interés') {
            initialCapital = 0; // Set to 0 if defaults to interest
            tipoAbonoSelected = '';
            tipoInteresSelected = 'selected';
            montoClass = 'text-danger';
        }

        let disableAbonoCapital = (parseFloat(p.progress?.saldo_pendiente) <= 0.01) ? 'disabled style="display:none;"' : '';

        tableBody.insertAdjacentHTML('beforeend', `
            <tr data-row="${rowIdx}">
                <td>${rowIdx}</td>
                <td style="min-width: 160px;">
                    <select class="form-select form-select-sm item-tipo">
                        <option value="abono_capital" ${tipoAbonoSelected} ${disableAbonoCapital}>Abono a Saldo</option>
                        <option value="pago_interes" ${tipoInteresSelected}>Pago de Interés</option>
                        <option value="generar_interes">Generar Interés (Multa)</option>
                    </select>
                </td>
                <td style="min-width: 150px;"><input type="date" class="form-control form-control-sm item-programada" value="${progStr}"></td>
                <td style="min-width: 130px;"><input type="number" step="0.01" class="form-control form-control-sm ${montoClass} fw-bold item-monto" data-default-amount="${defaultMonto}" value="${initialCapital}"></td>
                <td style="min-width: 150px;"><input type="date" class="form-control form-control-sm item-fecha" value="${today}"></td>
                <td style="min-width: 160px;"><select class="form-select form-select-sm item-payment-method">${paymentMethodOptionsHtml()}</select></td>
                <td style="min-width: 160px;"><input type="text" class="form-control form-control-sm item-observacion" value="${observacionStr}"></td>
                <td class="text-center" style="min-width: 60px;">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    }

    async function saveItemsForPartner(partnerId) {
        const voucherId = document.getElementById('supplier_voucher_id').value;
        if (!voucherId) return Swal.fire({ icon: 'warning', title: 'Selecciona una boleta' });
        
        const tableBody = document.querySelector(`.table-abonos-registrar[data-partner="${partnerId}"] tbody`);
        if (!tableBody) return;
        
        const rows = tableBody.querySelectorAll('tr');
        const items = [];
        let totalCapitalToPay = 0;

        rows.forEach(row => {
            const tipo = row.querySelector('.item-tipo')?.value || 'abono_capital';
            const fecha_pago_programada = row.querySelector('.item-programada')?.value || null;
            const monto = parseFloat(row.querySelector('.item-monto')?.value || 0);
            const fecha_recibido = row.querySelector('.item-fecha')?.value || '';
            const payment_method_id = row.querySelector('.item-payment-method')?.value || '';
            const observaciones = row.querySelector('.item-observacion')?.value || null;

            if (fecha_recibido && monto > 0) {
                if (tipo !== 'generar_interes' && !payment_method_id) {
                    return; // Forma de pago obligatoria si no es generar interés
                }

                items.push({
                    tipo,
                    fecha_pago_programada,
                    monto,
                    fecha_recibido,
                    payment_method_id: payment_method_id ? parseInt(payment_method_id, 10) : null,
                    observaciones
                });

                if (tipo === 'abono_capital') {
                    totalCapitalToPay += monto;
                }
            }
        });

        if (!items.length) {
            return Swal.fire({ icon: 'warning', title: 'Debes capturar al menos un abono válido para este socio' });
        }

        const p = currentVoucher?.partners?.find(x => x.id == partnerId);
        if (p) {
            const remainingCapital = parseFloat(p.progress?.saldo_pendiente || 0);
            if (parseFloat(totalCapitalToPay.toFixed(2)) > parseFloat(remainingCapital.toFixed(2))) {
                return Swal.fire({ 
                    icon: 'warning', 
                    title: 'Monto Excedido', 
                    text: `El abono a capital ($${totalCapitalToPay.toLocaleString()}) no puede ser mayor al saldo pendiente de capital del socio ($${remainingCapital.toLocaleString()}).`
                });
            }
        }

        try {
            const res = await fetch('/abonos-proveedores', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    supplier_voucher_id: voucherId,
                    supplier_voucher_partner_id: partnerId,
                    items
                })
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'No se pudo guardar');

            Swal.fire({
                icon: 'success', title: 'Correcto', text: json.message, timer: 1600, showConfirmButton: false
            });
            await loadVoucherSummary(voucherId);
            
            // Restore active tab
            setTimeout(() => {
                const tabBtn = document.getElementById(`tab-partner-${partnerId}-tab`);
                if(tabBtn) {
                    const tab = new bootstrap.Tab(tabBtn);
                    tab.show();
                }
            }, 100);

        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

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

    document.getElementById('btnNuevoAbonoProveedor').addEventListener('click', openNew);
    
    // Prevent default form submit as we handle it per-tab now
    form.addEventListener('submit', e => e.preventDefault());

    $('#supplier_id').on('change', function () {
        loadVouchers(this.value);
    });

    $('#supplier_voucher_id').on('change', function () {
        loadVoucherSummary(this.value);
    });

    // Delegated events for dynamic tab content
    document.getElementById('partnerTabsContent').addEventListener('click', function (e) {
        const btnRemove = e.target.closest('.btn-remove-item');
        if (btnRemove) {
            btnRemove.closest('tr').remove();
            return;
        }
        
        const btnAddAbono = e.target.closest('.btn-add-abono');
        if (btnAddAbono) {
            addItemToPartner(btnAddAbono.dataset.partner);
            return;
        }
        
        const btnGuardar = e.target.closest('.btn-guardar-abonos');
        if (btnGuardar) {
            saveItemsForPartner(btnGuardar.dataset.partner);
            return;
        }
        
        const btnAddInteres = e.target.closest('.btn-add-interes');
        if (btnAddInteres) {
            // Already handled by add abono now (since it's in the dropdown)
            return;
        }
    });

    initSelect2();
})();
</script>
@endpush