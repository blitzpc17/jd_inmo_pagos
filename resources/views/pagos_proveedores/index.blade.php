@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Boletas proveedor</h3>
            <div class="text-muted">Alta de deuda por proveedor</div>
        </div>
        <button class="btn btn-primary" id="btnNuevaBoletaProveedor">
            <i class="fa-solid fa-plus me-1"></i> Nueva boleta
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
                    <th>Proveedor</th>
                    <th>Lotificación</th>
                    <th>Motivo</th>
                    <th>Observación</th>
                    <th>Total</th>
                    <th>Meses</th>
                    <th>Mensualidad</th>
                    <th>Pagado</th>
                    <th>Debe</th>
                    <th>Estado pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalBoletaProveedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formBoletaProveedor">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva boleta de proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Proveedor</label>
                            <select class="form-select select2-proveedor" id="supplier_id" name="supplier_id"></select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Lotificación</label>
                            <select class="form-select select2-proveedor" id="development_id" name="development_id"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Total Boleta ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="total" id="total" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Enganche ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="enganche" id="enganche" value="0" readonly required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Meses</label>
                            <input type="number" min="1" class="form-control" id="meses" name="meses">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Núm. Socios</label>
                            <input type="number" min="1" value="1" class="form-control" id="num_socios" name="num_socios">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="equal_split" checked>
                                <label class="form-check-label" for="equal_split" style="font-size: 11px;">Dividir % igual</label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label mb-1">Detalle de Socios</label>
                            <div id="partner_container" class="table-responsive border rounded">
                                <table class="table table-sm table-borderless mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nombre del Socio</th>
                                            <th style="width: 100px;">Porcentaje</th>
                                            <th style="width: 120px;">Enganche</th>
                                            <th style="width: 80px;" class="text-center">Titular</th>
                                        </tr>
                                    </thead>
                                    <tbody id="partner_tbody">
                                        <!-- Dynamic inputs -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Motivo de la Boleta (Máx 350 caracteres)</label>
                            <textarea class="form-control" id="motivo" name="motivo" rows="2" maxlength="350"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Observación Interna</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3"></textarea>
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

<div class="modal fade" id="modalDetalleBoletaProveedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle boleta proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><label class="form-label">Referencia</label><input type="text" class="form-control fw-bold" id="dba_ref" readonly></div>
                    <div class="col-md-6"><label class="form-label">Lotificación</label><input type="text" class="form-control fw-bold" id="dba_lotificacion" readonly></div>
                    <div class="col-md-12"><label class="form-label">Proveedor</label><input type="text" class="form-control fw-bold" id="dba_proveedor" readonly></div>
                    <div class="col-md-12"><label class="form-label">Motivo de la Boleta</label><textarea class="form-control fw-bold" id="dba_motivo" rows="2" readonly></textarea></div>
                </div>
                
                <div class="row">
                    <!-- PANEL GENERAL -->
                    <div class="col-md-6">
                        <div class="card bg-transparent border-0">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie me-1"></i> Totales Generales</h6>
                                <div class="row g-3">
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Total a Pagar</label><input type="text" class="form-control form-control-sm" id="dba_total" readonly></div>
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Enganche</label><input type="text" class="form-control form-control-sm" id="dba_enganche" readonly></div>
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Resta por Pagar</label><input type="text" class="form-control form-control-sm text-danger fw-bold" id="dba_debe" readonly></div>
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Tiempo a Pagar (Meses)</label><input type="text" class="form-control form-control-sm" id="dba_meses" readonly></div>
                                    <div class="col-12"><label class="form-label text-muted small mb-0">Letra Mensual</label><input type="text" class="form-control form-control-sm text-primary fw-bold" id="dba_mensualidad" readonly></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PANEL POR SOCIO -->
                    <div class="col-md-6">
                        <div class="card bg-transparent border-0 h-100">
                            <div class="card-body" id="dba_partner_container">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-users me-1"></i> Totales Por Socio (<span id="dba_num_socios_lbl"></span>)</h6>
                                <div class="row g-3">
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Total a Pagar (Socio)</label><input type="text" class="form-control form-control-sm" id="dba_total_socio" readonly></div>
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Enganche (Socio)</label><input type="text" class="form-control form-control-sm" id="dba_enganche_socio" readonly></div>
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Resta por Pagar (Socio)</label><input type="text" class="form-control form-control-sm text-danger fw-bold" id="dba_debe_socio" readonly></div>
                                    <div class="col-6"><label class="form-label text-muted small mb-0">Tiempo a Pagar (Meses)</label><input type="text" class="form-control form-control-sm" id="dba_meses_socio" readonly></div>
                                    <div class="col-12"><label class="form-label text-muted small mb-0">Letra Mensual (Socio)</label><input type="text" class="form-control form-control-sm text-primary fw-bold" id="dba_mensualidad_socio" readonly></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12 mt-2"><label class="form-label">Observación</label><textarea class="form-control" id="dba_observacion" rows="2" readonly></textarea></div>
                </div>

                <div class="page-card">
                    <h6 class="fw-bold mb-3">Abonos registrados</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th># Pago</th>
                                    <th>F. Programada</th>
                                    <th>Cant. a Pagar</th>
                                    <th>F. de Pago (Real)</th>
                                    <th>Monto Pagado</th>
                                    <th>Interés</th>
                                    <th>Forma de pago</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody id="detalleBoletaProveedorItemsBody"></tbody>
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
    const modal = new bootstrap.Modal(document.getElementById('modalBoletaProveedor'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleBoletaProveedor'));
    const form = document.getElementById('formBoletaProveedor');
    let table = null;
    let optionsCache = null;

    const fCurrency = v => {
        if (!v || isNaN(v)) return '$ 0.00';
        return '$ ' + parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    };

    const fDate = d => {
        if (!d) return '';
        const parts = d.split('-');
        if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
        return d;
    };

    function initSelect2() {
        $('.select2-proveedor').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalBoletaProveedor')
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
        fillSelect('development_id', optionsCache.developments);
        return optionsCache;
    }

    function resetForm() {
        form.reset();
        $('.select2-proveedor').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblPagosProveedores').DataTable({
            ajax: { url: '/pagos-proveedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'proveedor' },
                { data: 'lotificacion', render: d => d || '-' },
                { data: 'motivo', title: 'Motivo', render: function(data) {
                    if (!data) return '';
                    return data.length > 50 ? data.substring(0, 47) + '...' : data;
                }},
                { data: 'observacion', title: 'Observación' },
                { data: 'total', className: 'text-end', render: (data, type) => type === 'display' ? fCurrency(data) : data },
                { data: 'meses', className: 'text-end' },
                { data: 'mensualidad', className: 'text-end', render: (data, type) => type === 'display' ? fCurrency(data) : data },
                { data: 'total_pagado', className: 'text-end', render: (data, type) => type === 'display' ? fCurrency(data) : data },
                { data: 'saldo_pendiente', className: 'text-end', render: (data, type) => type === 'display' ? fCurrency(data) : data },
                { data: 'estado_pago_badge', orderable: false, searchable: false },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [],
            language: {
                processing: "Procesando...",
                lengthMenu: "Mostrar _MENU_ registros",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "No hay datos disponibles",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                search: "Buscar:",
                loadingRecords: "Cargando...",
                paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
            }
        });
    }

    async function openNew() {
        await loadOptions();
        resetForm();
        renderPercentages();
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const payload = {
            supplier_id: document.getElementById('supplier_id').value,
            development_id: document.getElementById('development_id').value,
            total: document.getElementById('total').value,
            enganche: document.getElementById('enganche').value,
            meses: document.getElementById('meses').value,
            num_socios: document.getElementById('num_socios').value,
            fecha_inicio: document.getElementById('fecha_inicio').value,
            motivo: document.getElementById('motivo').value,
            observacion: document.getElementById('observacion').value
        };

        const inputsPct = document.querySelectorAll('.partner-pct-input');
        const inputsName = document.querySelectorAll('.partner-name-input');
        const inputsEnganche = document.querySelectorAll('.partner-enganche-input');
        const radioTitular = document.querySelector('.partner-titular-radio:checked');
        
        let sum = 0;
        const pcts = [];
        const names = [];
        const enganches = [];
        const titularIndex = radioTitular ? parseInt(radioTitular.value) : 0;
        const equalSplit = document.getElementById('equal_split') ? document.getElementById('equal_split').checked : false;

        inputsPct.forEach((el, idx) => {
            const val = equalSplit ? (100 / inputsPct.length) : (parseFloat(el.value) || 0);
            sum += val;
            pcts.push(val);
            names.push(inputsName[idx].value || `Socio ${idx + 1}`);
            enganches.push(parseFloat(inputsEnganche[idx].value) || 0);
        });

        if (Math.abs(sum - 100) > 0.01) {
            Swal.fire('Error', 'La suma de los porcentajes debe ser exactamente 100.', 'error');
            return;
        }

        payload.partner_percentages = pcts;
        payload.partner_names = names;
        payload.partner_enganches = enganches;
        payload.titular_index = titularIndex;

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
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    async function viewItem(id) {
        const res = await fetch(`/pagos-proveedores/${id}`);
        const json = await res.json();
        
        const d = json.data;
        const total = parseFloat(d.total) || 0;
        const enganche = parseFloat(d.enganche) || 0;
        const meses = parseInt(d.meses) || 1;
        const numSocios = parseInt(d.num_socios) || 1;
        const mensualidad = parseFloat(d.mensualidad) || 0;
        const debe = parseFloat(d.saldo_pendiente) || 0;

        document.getElementById('dba_ref').value = d.numero_referencia || '';
        document.getElementById('dba_lotificacion').value = d.lotificacion || '-';
        document.getElementById('dba_proveedor').value = d.proveedor || '';

        // Generales
        document.getElementById('dba_total').value = fCurrency(total);
        document.getElementById('dba_enganche').value = fCurrency(enganche);
        document.getElementById('dba_debe').value = fCurrency(debe);
        document.getElementById('dba_meses').value = meses;
        document.getElementById('dba_mensualidad').value = fCurrency(mensualidad);
        
        // Por Socio Dinámico
        const partnerContainer = document.getElementById('dba_partner_container');
        let partnerHtml = `<h6 class="fw-bold mb-3"><i class="fa-solid fa-users me-1"></i> Desglose por Socio (${numSocios})</h6>`;
        
        let partners = d.partners || [];
        
        partnerHtml += `<div class="accordion" id="accordionSocios">`;
        partners.forEach((p, i) => {
            const pct = parseFloat(p.porcentaje) || 0;
            const factor = pct / 100;
            const socioTotal = total * factor;
            const socioEnganche = parseFloat(p.enganche) || 0;
            const socioDebe = parseFloat(p.progress?.saldo_pendiente || 0);
            const socioMensualidad = mensualidad * factor;
            
            const titularBadge = p.es_titular ? `<span class="badge bg-primary ms-1">TITULAR</span>` : '';

            partnerHtml += `
                <div class="accordion-item mb-1 border-0 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSocio${i}">
                            ${p.nombre} <span class="badge bg-secondary ms-2">${pct.toFixed(2)}%</span> ${titularBadge}
                        </button>
                    </h2>
                    <div id="collapseSocio${i}" class="accordion-collapse collapse" data-bs-parent="#accordionSocios">
                        <div class="accordion-body p-2 bg-transparent border rounded mt-1">
                            <div class="row g-2">
                                <div class="col-6"><label class="form-label text-muted small mb-0">Total</label><input type="text" class="form-control form-control-sm" value="${fCurrency(socioTotal)}" readonly></div>
                                <div class="col-6"><label class="form-label text-muted small mb-0">Enganche</label><input type="text" class="form-control form-control-sm" value="${fCurrency(socioEnganche)}" readonly></div>
                                <div class="col-6"><label class="form-label text-muted small mb-0">Resta</label><input type="text" class="form-control form-control-sm text-danger fw-bold" value="${fCurrency(socioDebe)}" readonly></div>
                                <div class="col-6"><label class="form-label text-muted small mb-0">Mensualidad</label><input type="text" class="form-control form-control-sm text-primary fw-bold" value="${fCurrency(socioMensualidad)}" readonly></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        partnerHtml += `</div>`;
        partnerContainer.innerHTML = partnerHtml;
        
        document.getElementById('dba_observacion').value = d.observacion || '';
        document.getElementById('dba_motivo').value = d.motivo || '';

        const tbody = document.getElementById('detalleBoletaProveedorItemsBody');
        tbody.innerHTML = '';

        (json.data.items || []).forEach((item, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${fDate(item.fecha_pago_programada)}</td>
                    <td>${fCurrency(item.cantidad_a_pagar ?? 0)}</td>
                    <td>${fDate(item.fecha_recibido)}</td>
                    <td><span class="text-success fw-bold">${fCurrency(item.cantidad ?? 0)}</span></td>
                    <td><span class="text-danger">${fCurrency(item.interes_pagado ?? 0)}</span></td>
                    <td>${item.forma_pago ?? ''}</td>
                    <td>${item.observaciones ?? ''}</td>
                </tr>
            `;
        });

        modalDetalle.show();
    }

    document.getElementById('btnNuevaBoletaProveedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblPagosProveedores').on('click', '.btn-view', function () {
        viewItem(this.dataset.id);
    });

    function renderPercentages() {
        const tbody = document.getElementById('partner_tbody');
        const num = parseInt(document.getElementById('num_socios').value) || 1;
        const equalSplit = document.getElementById('equal_split').checked;

        let html = '';
        for (let i = 0; i < num; i++) {
            const val = equalSplit ? (100 / num).toFixed(2) : 0;
            const isReadonly = equalSplit ? 'readonly' : '';
            const isChecked = (i === 0) ? 'checked' : '';
            html += `
                <tr>
                    <td>
                        <input type="text" class="form-control form-control-sm partner-name-input" placeholder="Nombre Socio ${i+1}" value="Socio ${i+1}" required>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm partner-pct-input text-end" value="${val}" ${isReadonly} required>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm partner-enganche-input text-end" value="0" required oninput="calcGlobalEnganche()">
                    </td>
                    <td class="text-center align-middle">
                        <input type="radio" class="form-check-input partner-titular-radio" name="titular_radio" value="${i}" ${isChecked}>
                    </td>
                </tr>
            `;
        }
        tbody.innerHTML = html;
        calcGlobalEnganche();
    }

    window.calcGlobalEnganche = function() {
        const inputs = document.querySelectorAll('.partner-enganche-input');
        let total = 0;
        inputs.forEach(el => {
            total += parseFloat(el.value) || 0;
        });
        document.getElementById('enganche').value = total.toFixed(2);
    };

    document.getElementById('num_socios').addEventListener('input', renderPercentages);
    document.getElementById('equal_split').addEventListener('change', renderPercentages);

    initSelect2();
    initTable();
})();
</script>
@endpush