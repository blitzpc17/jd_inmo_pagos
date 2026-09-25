@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Boletas de Acreedores</h3>
            <div class="text-muted">Gestión de boletas y abonos de acreedores</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoPagoAcreedor">
            <i class="fa-solid fa-plus me-1"></i> Nueva Boleta
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100 text-nowrap" id="tblPagosAcreedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Folio</th>
                    <th>Acreedor</th>
                    <th>Concepto</th>
                    <th>Total</th>
                    <th>Enganche</th>
                    <th>Abonos Cap.</th>
                    <th>Resto Cap.</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Totales:</th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                    <th class="text-start"></th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal Nueva Boleta -->
<div class="modal fade" id="modalPagoAcreedor" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formPagoAcreedor">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Boleta de Acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Acreedor</label>
                            <select class="form-select select2-pp" id="creditor_id" name="creditor_id"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Oficina <span class="text-danger">*</span></label>
                            <select class="form-select select2-pp" id="office_id" name="office_id" required></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Concepto (Opcional)</label>
                            <textarea class="form-control" id="concepto" name="concepto" rows="1" maxlength="350"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total Boleta ($)</label>
                            <input type="number" step="0.01" class="form-control" id="total" name="total" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Enganche ($)</label>
                            <input type="number" step="0.01" class="form-control" id="enganche" name="enganche" min="0" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Observaciones</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Guardar Boleta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detalle e Historial -->
<div class="modal fade" id="modalDetalleBoletaAcreedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        Detalle de Boleta: <span id="dpp_ref" class="text-primary fw-bold"></span>
                        <span id="dpp_estado_badge"></span>
                    </h5>
                    <div class="text-muted small" id="dpp_acreedor_nombre"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <small class="text-muted d-block">Total</small>
                            <strong id="dpp_total" class="fs-6"></strong>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted d-block">Enganche</small>
                            <strong id="dpp_enganche" class="fs-6"></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Capital Pagado</small>
                            <strong id="dpp_pagado" class="text-success fs-6"></strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Capital Pendiente</small>
                            <strong id="dpp_pendiente" class="text-danger fs-6"></strong>
                        </div>
                    </div>
                </div>
                
                <div class="p-3 border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-start gap-4">
                                <div class="text-start">
                                    <small class="text-muted d-block">Interés Generado</small>
                                    <strong id="prog_int_acum"></strong>
                                </div>
                                <div class="text-start">
                                    <small class="text-muted d-block">Interés Pagado</small>
                                    <strong id="prog_int_pag" class="text-success"></strong>
                                </div>
                                <div class="text-start">
                                    <small class="text-muted d-block">Interés Pendiente</small>
                                    <strong id="prog_int_pend" class="text-danger"></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Historial de Pagos y Movimientos</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-success" id="btnAgregarAbono">
                                <i class="fa-solid fa-plus me-1"></i> Registrar Operación
                            </button>
                            <a href="#" id="btnImprimirBoletaAcreedor" target="_blank" class="btn btn-sm btn-outline-secondary">
                                <i class="fa-solid fa-print me-1"></i> Boleta PDF
                            </a>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
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
                            <tbody id="dppItemsBody">
                            </tbody>
                            <tfoot id="dppItemsFoot">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold align-top pt-2">Resumen de Totales:</td>
                                    <td colspan="4">
                                        <div class="d-flex flex-column small">
                                            <div><span class="text-muted" style="display:inline-block; width:130px;">Abonos a Capital:</span> <strong class="text-success" id="tf_cap_pagado"></strong></div>
                                            <div><span class="text-muted" style="display:inline-block; width:130px;">Cargos de Interés:</span> <strong class="text-danger" id="tf_int_gen"></strong></div>
                                            <div><span class="text-muted" style="display:inline-block; width:130px;">Pagos de Interés:</span> <strong class="text-warning text-dark" id="tf_int_pag"></strong></div>
                                            <hr class="my-1">
                                            <div><span class="text-muted" style="display:inline-block; width:130px;">Capital Pendiente:</span> <strong class="text-primary" id="tf_cap_restante"></strong></div>
                                            <div><span class="text-muted" style="display:inline-block; width:130px;">Interés Pendiente:</span> <strong class="text-danger" id="tf_int_restante"></strong></div>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Registrar Abono/Interés -->
<div class="modal fade" id="modalAbonoAcreedor" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <form class="modal-content" id="formAbonoAcreedor">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Operación (Abono / Interés)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="abono_boleta_id">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle" id="tblAbonosDynamic">
                        <thead>
                            <tr>
                                <th style="min-width: 160px">Tipo de Operación</th>
                                <th style="min-width: 120px">Monto</th>
                                <th style="min-width: 140px">Fecha</th>
                                <th style="min-width: 160px">Forma Pago</th>
                                <th style="min-width: 180px">Observaciones</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="btnAddAbonoRow">
                    <i class="fa-solid fa-plus me-1"></i> Agregar Fila
                </button>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" type="submit">Guardar Operaciones</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function() {
    let table;
    const modalPago = new bootstrap.Modal(document.getElementById('modalPagoAcreedor'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleBoletaAcreedor'));
    const modalAbono = new bootstrap.Modal(document.getElementById('modalAbonoAcreedor'));
    
    const formPago = document.getElementById('formPagoAcreedor');
    const formAbono = document.getElementById('formAbonoAcreedor');
    
    let currentVoucherId = null;
    let paymentMethods = [];
    let rowCount = 0;

    const fCurrency = v => '$ ' + parseFloat(v || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const fDate = d => {
        if (!d) return '';
        const parts = d.split(' ')[0].split('-');
        if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
        return d;
    };

    async function initOptions() {
        const res = await fetch('/pagos-acreedores/options');
        const json = await res.json();
        
        const selCreditor = $('#creditor_id');
        selCreditor.empty();
        selCreditor.append(new Option('Seleccione...', ''));
        json.creditors.forEach(c => selCreditor.append(new Option(c.text, c.value)));

        const selOffice = $('#office_id');
        selOffice.empty();
        selOffice.append(new Option('Seleccione...', ''));
        (json.offices || []).forEach(o => selOffice.append(new Option(o.text, o.value)));
        
        if (json.offices && json.offices.length === 1) {
            selOffice.val(json.offices[0].value).trigger('change');
        }

        paymentMethods = json.payment_methods || [];
    }

    function initTable() {
        table = $('#tblPagosAcreedores').DataTable({
            processing: true,
            serverSide: false,
            ajax: '/pagos-acreedores/datatable',
            columns: [
                { data: 'id' },
                { data: 'numero_referencia' },
                { data: 'acreedor' },
                { data: 'concepto' },
                { data: 'importe', render: v => `<span class="fw-bold">${fCurrency(v)}</span>` },
                { data: 'enganche', render: v => `<span class="text-muted">${fCurrency(v)}</span>` },
                { data: 'total_pagado', render: v => `<span class="text-success">${fCurrency(v)}</span>` },
                { data: 'saldo_pendiente', render: v => `<span class="text-danger fw-bold">${fCurrency(v)}</span>` },
                { data: 'estado_pago_badge' },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
            footerCallback: function (row, data, start, end, display) {
                let api = this.api();
                let intVal = function (i) {
                    return typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                };
                
                [4, 5, 6, 7].forEach(function(colIndex) {
                    let total = api.column(colIndex, { search: 'applied' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                    $(api.column(colIndex).footer()).html(fCurrency(total));
                });
            }
        });
    }
    
    // No mensualidad calculation needed

    document.getElementById('btnNuevoPagoAcreedor').addEventListener('click', () => {
        formPago.reset();
        $('#creditor_id').val('').trigger('change');
        $('#office_id').val('').trigger('change');
        modalPago.show();
    });

    formPago.addEventListener('submit', async e => {
        e.preventDefault();

        const officeId = document.getElementById('office_id').value;
        if (!officeId) {
            Swal.fire('Aviso', 'Por favor selecciona la oficina.', 'warning');
            return;
        }

        const payload = Object.fromEntries(new FormData(formPago));
        
        try {
            const res = await fetch('/pagos-acreedores', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if(!res.ok) throw new Error(json.message || 'Error guardando');
            
            modalPago.hide();
            table.ajax.reload(null, false);
            Swal.fire({ icon: 'success', title: 'Boleta Registrada', showConfirmButton: false, timer: 1500 });
        } catch(err) {
            Swal.fire('Error', err.message, 'error');
        }
    });

    $('#tblPagosAcreedores').on('click', '.btn-view', function() {
        viewDetails(this.dataset.id);
    });

    async function viewDetails(id) {
        currentVoucherId = id;
        try {
            const res = await fetch(`/pagos-acreedores/${id}`);
            const json = await res.json();
            if(!res.ok) throw new Error();
            const d = json.data;
            
            document.getElementById('abono_boleta_id').value = id;
            document.getElementById('btnImprimirBoletaAcreedor').href = `/pagos-acreedores/${id}/pdf/boleta`;
            
            document.getElementById('dpp_ref').innerText = d.numero_referencia;
            document.getElementById('dpp_acreedor_nombre').innerText = d.acreedor;
            
            let badgeClass = 'bg-secondary';
            if(d.estado_pago === 'VIGENTE') badgeClass = 'bg-success';
            if(d.estado_pago === 'PAGADO') badgeClass = 'bg-primary';
            
            document.getElementById('dpp_estado_badge').innerHTML = `<span class="badge ${badgeClass} ms-2 fs-6">${d.estado_pago}</span>`;
            
            document.getElementById('dpp_total').innerText = fCurrency(d.total);
            document.getElementById('dpp_enganche').innerText = fCurrency(d.enganche);
            document.getElementById('dpp_pagado').innerText = fCurrency(d.total_pagado);
            document.getElementById('dpp_pendiente').innerText = fCurrency(d.saldo_pendiente);
            
            document.getElementById('prog_int_acum').innerText = fCurrency(d.interes_acumulado);
            document.getElementById('prog_int_pag').innerText = fCurrency(d.interes_pagado);
            document.getElementById('prog_int_pend').innerText = fCurrency(d.interes_pendiente);
            
            // Populate tfoot totals
            document.getElementById('tf_cap_pagado').innerText = fCurrency(d.total_pagado);
            document.getElementById('tf_int_gen').innerText = fCurrency(d.interes_acumulado);
            document.getElementById('tf_int_pag').innerText = fCurrency(d.interes_pagado);
            document.getElementById('tf_cap_restante').innerText = fCurrency(d.saldo_pendiente);
            document.getElementById('tf_int_restante').innerText = fCurrency(d.interes_pendiente);
            
            const tbody = document.getElementById('dppItemsBody');
            tbody.innerHTML = '';
            
            let allItems = [];
            (d.items || []).forEach(i => {
                let rec = '';
                if(i.id) rec = `<a href="/pagos-acreedores/${id}/pdf/recibo/${i.id}" target="_blank" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf"></i></a>`;
                
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
                allItems.push({ fecha: i.created_at, html: `
                    <tr>
                        <td>-</td>
                        <td>${fDate(i.created_at)}</td>
                        <td><span class="badge bg-danger">Cargo Interés</span></td>
                        <td class="fw-bold text-danger">${fCurrency(i.cantidad)}</td>
                        <td>Generación Automática / Manual</td>
                        <td>-</td>
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
            
            modalDetalle.show();
        } catch(err) {
            Swal.fire('Error', 'No se pudo cargar la boleta', 'error');
        }
    }

    // Modal de Abonos y Generación de Interés
    document.getElementById('btnAgregarAbono').addEventListener('click', () => {
        document.querySelector('#tblAbonosDynamic tbody').innerHTML = '';
        addAbonoRow();
        modalAbono.show();
    });

    document.getElementById('btnAddAbonoRow').addEventListener('click', addAbonoRow);

    function addAbonoRow() {
        rowCount++;
        const tbody = document.querySelector('#tblAbonosDynamic tbody');
        
        let pmOptions = '<option value="">(Ninguna)</option>';
        paymentMethods.forEach(pm => {
            pmOptions += `<option value="${pm.value}">${pm.text}</option>`;
        });

        const today = new Date().toISOString().slice(0, 10);
        
        const tr = document.createElement('tr');
        tr.id = `row_abono_${rowCount}`;
        tr.innerHTML = `
            <td>
                <select class="form-select form-select-sm" name="items[${rowCount}][tipo]" onchange="toggleAbonoRow(${rowCount}, this)">
                    <option value="abono_capital">Abono a Capital</option>
                    <option value="pago_interes">Pago de Interés</option>
                    <option value="generar_interes">Generar Cargo por Interés</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.01" class="form-control form-control-sm" name="items[${rowCount}][monto]" min="0.01" required>
            </td>
            <td>
                <input type="date" class="form-control form-control-sm" name="items[${rowCount}][fecha_recibido]" value="${today}" required>
            </td>
            <td>
                <select class="form-select form-select-sm row-pm" name="items[${rowCount}][payment_method_id]">
                    ${pmOptions}
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="items[${rowCount}][observaciones]" placeholder="Opcional">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }
    
    window.toggleAbonoRow = function(id, sel) {
        const tr = document.getElementById(`row_abono_${id}`);
        const pmSel = tr.querySelector('.row-pm');
        if (sel.value === 'generar_interes') {
            pmSel.disabled = true;
            pmSel.value = '';
        } else {
            pmSel.disabled = false;
        }
    };

    formAbono.addEventListener('submit', async e => {
        e.preventDefault();
        const rows = document.querySelectorAll('#tblAbonosDynamic tbody tr');
        if (rows.length === 0) {
            Swal.fire('Atención', 'Debes agregar al menos una operación.', 'warning');
            return;
        }

        const fd = new FormData(formAbono);
        const payload = {
            creditor_payment_id: document.getElementById('abono_boleta_id').value,
            items: []
        };
        
        // Parse Form Data arrays
        const obj = Object.fromEntries(fd);
        for(let key in obj) {
            const match = key.match(/^items\[(\d+)\]\[(.+)\]$/);
            if (match) {
                const idx = match[1];
                const prop = match[2];
                let item = payload.items.find(i => i._idx === idx);
                if (!item) {
                    item = { _idx: idx };
                    payload.items.push(item);
                }
                item[prop] = obj[key];
            }
        }

        try {
            const res = await fetch(`/pagos-acreedores/${payload.creditor_payment_id}/abono`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if(!res.ok) throw new Error(json.message || 'Error al procesar.');
            
            modalAbono.hide();
            Swal.fire({ icon: 'success', title: 'Éxito', text: json.message, timer: 1500, showConfirmButton: false });
            
            table.ajax.reload(null, false);
            viewDetails(payload.creditor_payment_id);
            
        } catch(err) {
            Swal.fire('Error', err.message, 'error');
        }
    });

    $(document).ready(() => {
        $('.select2-pp').select2({ dropdownParent: $('#modalPagoAcreedor'), width: '100%' });
        initOptions();
        initTable();
    });

})();
</script>
@endpush