@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">Boletas acreedor</h3>
            <div class="text-muted">Alta de deuda por acreedor</div>
        </div>
        <button class="btn btn-primary" id="btnNuevaBoletaAcreedor">
            <i class="fa-solid fa-plus me-1"></i> Nueva boleta
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblPagosAcreedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Referencia</th>
                    <th>Acreedor</th>
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

<div class="modal fade" id="modalBoletaAcreedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formBoletaAcreedor">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva boleta de acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Acreedor</label>
                            <select class="form-select select2-acreedor" id="creditor_id" name="creditor_id"></select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Costo (Total)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="total" name="total">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Enganche</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="enganche" name="enganche">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Meses</label>
                            <input type="number" min="1" class="form-control" id="meses" name="meses">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Núm. Socios</label>
                            <input type="number" min="1" value="1" class="form-control" id="num_socios" name="num_socios">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="equal_split" checked>
                                <label class="form-check-label" for="equal_split" style="font-size: 11px;">Dividir en partes iguales</label>
                            </div>
                            <div id="partner_percentages_container" class="mt-2 row g-2"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Observación</label>
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

<div class="modal fade" id="modalDetalleBoletaAcreedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle boleta acreedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-12"><label class="form-label">Referencia</label><input type="text" class="form-control fw-bold" id="dba_ref" readonly></div>
                    <div class="col-md-12"><label class="form-label">Acreedor</label><input type="text" class="form-control" id="dba_acreedor" readonly></div>
                </div>
                
                <div class="row">
                    <!-- PANEL GENERAL -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
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
                        <div class="card bg-light border-0 h-100">
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
                    <div class="col-md-12"><label class="form-label">Observación</label><textarea class="form-control" id="dba_observacion" rows="2" readonly></textarea></div>
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
                            <tbody id="detalleBoletaAcreedorItemsBody"></tbody>
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
    const modal = new bootstrap.Modal(document.getElementById('modalBoletaAcreedor'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleBoletaAcreedor'));
    const form = document.getElementById('formBoletaAcreedor');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-acreedor').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalBoletaAcreedor')
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
        const res = await fetch('/pagos-acreedores/options');
        optionsCache = await res.json();
        fillSelect('creditor_id', optionsCache.creditors || []);
        return optionsCache;
    }

    function resetForm() {
        form.reset();
        $('.select2-acreedor').val(null).trigger('change');
    }

    function initTable() {
        table = $('#tblPagosAcreedores').DataTable({
            ajax: { url: '/pagos-acreedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'acreedor' },
                { data: 'total' },
                { data: 'meses' },
                { data: 'mensualidad' },
                { data: 'total_pagado' },
                { data: 'saldo_pendiente' },
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
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const payload = {
            creditor_id: document.getElementById('creditor_id').value,
            total: document.getElementById('total').value,
            enganche: document.getElementById('enganche').value,
            meses: document.getElementById('meses').value,
            num_socios: document.getElementById('num_socios').value,
            fecha_inicio: document.getElementById('fecha_inicio').value,
            observacion: document.getElementById('observacion').value
        };

        const equalSplit = document.getElementById('equal_split').checked;
        if (!equalSplit) {
            const inputs = document.querySelectorAll('.partner-pct-input');
            let sum = 0;
            const pcts = [];
            inputs.forEach(el => {
                const val = parseFloat(el.value) || 0;
                sum += val;
                pcts.push(val);
            });
            if (Math.abs(sum - 100) > 0.01) {
                Swal.fire('Error', 'La suma de los porcentajes debe ser exactamente 100.', 'error');
                return;
            }
            payload.partner_percentages = pcts;
        } else {
            payload.partner_percentages = null;
        }

        try {
            const res = await fetch('/pagos-acreedores', {
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
        const res = await fetch(`/pagos-acreedores/${id}`);
        const json = await res.json();
        
        const d = json.data;
        const total = parseFloat(d.total) || 0;
        const enganche = parseFloat(d.enganche) || 0;
        const meses = parseInt(d.meses) || 1;
        const numSocios = parseInt(d.num_socios) || 1;
        const mensualidad = parseFloat(d.mensualidad) || 0;
        const debe = parseFloat(d.saldo_pendiente) || 0;

        document.getElementById('dba_ref').value = d.numero_referencia || '';
        document.getElementById('dba_acreedor').value = d.acreedor || '';
        
        const fCurrency = v => '$ ' + parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // Generales
        document.getElementById('dba_total').value = fCurrency(total);
        document.getElementById('dba_enganche').value = fCurrency(enganche);
        document.getElementById('dba_debe').value = fCurrency(debe);
        document.getElementById('dba_meses').value = meses;
        document.getElementById('dba_mensualidad').value = fCurrency(mensualidad);
        
        // Por Socio Dinámico
        const partnerContainer = document.getElementById('dba_partner_container');
        let partnerHtml = `<h6 class="fw-bold mb-3"><i class="fa-solid fa-users me-1"></i> Desglose por Socio (${numSocios})</h6>`;
        
        let pcts = d.partner_percentages;
        if (typeof pcts === 'string') {
            try { pcts = JSON.parse(pcts); } catch(e) { pcts = null; }
        }

        if (!Array.isArray(pcts) || pcts.length !== numSocios) {
            pcts = Array(numSocios).fill(100 / numSocios);
        }

        partnerHtml += `<div class="accordion" id="accordionSocios">`;
        pcts.forEach((pct, i) => {
            const factor = pct / 100;
            const socioTotal = total * factor;
            const socioEnganche = enganche * factor;
            const socioDebe = debe * factor;
            const socioMensualidad = mensualidad * factor;

            partnerHtml += `
                <div class="accordion-item mb-1 border-0 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-2 rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSocio${i}">
                            Socio ${i+1} <span class="badge bg-secondary ms-2">${pct.toFixed(2)}%</span>
                        </button>
                    </h2>
                    <div id="collapseSocio${i}" class="accordion-collapse collapse" data-bs-parent="#accordionSocios">
                        <div class="accordion-body p-2 bg-white border rounded mt-1">
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

        const tbody = document.getElementById('detalleBoletaAcreedorItemsBody');
        tbody.innerHTML = '';

        (json.data.items || []).forEach((item, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${item.fecha_pago_programada ?? ''}</td>
                    <td>${fCurrency(item.cantidad_a_pagar ?? 0)}</td>
                    <td>${item.fecha_recibido ?? ''}</td>
                    <td><span class="text-success fw-bold">${fCurrency(item.cantidad ?? 0)}</span></td>
                    <td><span class="text-danger">${fCurrency(item.interes_pagado ?? 0)}</span></td>
                    <td>${item.forma_pago ?? ''}</td>
                    <td>${item.observaciones ?? ''}</td>
                </tr>
            `;
        });

        modalDetalle.show();
    }

    document.getElementById('btnNuevaBoletaAcreedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#tblPagosAcreedores').on('click', '.btn-view', function () {
        viewItem(this.dataset.id);
    });

    function renderPercentages() {
        const container = document.getElementById('partner_percentages_container');
        const num = parseInt(document.getElementById('num_socios').value) || 1;
        const equalSplit = document.getElementById('equal_split').checked;

        if (equalSplit || num <= 1) {
            container.innerHTML = '';
            container.classList.add('d-none');
            return;
        }

        container.classList.remove('d-none');
        let html = '';
        for (let i = 1; i <= num; i++) {
            html += `
                <div class="col-6">
                    <label class="form-label mb-0" style="font-size:11px;">Socio ${i} (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm partner-pct-input" required>
                </div>
            `;
        }
        container.innerHTML = html;
    }

    document.getElementById('num_socios').addEventListener('input', renderPercentages);
    document.getElementById('equal_split').addEventListener('change', renderPercentages);

    initSelect2();
    initTable();
})();
</script>
@endpush