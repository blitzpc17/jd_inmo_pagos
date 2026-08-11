@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Boletas de Acreedores</h3>
            <div class="text-muted">Gestión de proyectos, contratos y abonos por lotificación</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoPagoAcreedor">
            <i class="fa-solid fa-plus me-1"></i> Nueva Boleta
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblPagosAcreedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Folio</th>
                    <th>Acreedor</th>
                    <th>Lotificación</th>
                    <th>Capital</th>
                    <th>Total</th>
                    <th>Abonos</th>
                    <th>Resto</th>
                    <th>Plazo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal Nueva Boleta -->
<div class="modal fade" id="modalPagoAcreedor" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formPagoAcreedor">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Boleta / Proyecto de Acreedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Acreedor</label>
                            <select class="form-select select2-pp" id="creditor_id" name="creditor_id"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Lotificación</label>
                            <select class="form-select select2-pp" id="development_id" name="development_id"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Plazo (Meses)</label>
                            <input type="number" class="form-control" id="plazo" name="plazo" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Fecha Fin</label>
                            <input type="date" class="form-control" id="fecha_fin" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Capital ($)</label>
                            <input type="number" step="0.01" class="form-control" id="capital" name="capital" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Porcentaje Interés (%)</label>
                            <input type="number" step="0.01" class="form-control" id="porcentaje_interes" name="porcentaje_interes" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Monto Interés ($)</label>
                            <input type="number" step="0.01" class="form-control bg-light" id="monto_interes" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Total a Pagar ($)</label>
                            <input type="number" step="0.01" class="form-control bg-light" id="importe" readonly>
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

<!-- Modal Detalles y Abonos -->
<div class="modal fade" id="modalDetallePagoAcreedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Boleta y Abonos</h5>
                <div class="ms-auto d-flex gap-2 align-items-center">
                    <a id="btnImprimirBoletaAcreedor" href="#" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="fa-solid fa-file-pdf me-1"></i> Imprimir Boleta
                    </a>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <div class="modal-body">
                <!-- Dashboard Cabecera -->
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-contract me-2 text-primary"></i>Datos del Proyecto</h5>
                                <div class="row g-2 text-sm">
                                    <div class="col-md-6"><strong>Folio:</strong> <span id="dpp_ref"></span></div>
                                    <div class="col-md-6"><strong>Estado:</strong> <span id="dpp_estado"></span></div>
                                    <div class="col-md-6"><strong>Acreedor:</strong> <span id="dpp_acreedor"></span></div>
                                    <div class="col-md-6"><strong>Lotificación:</strong> <span id="dpp_lotificacion"></span></div>
                                    <div class="col-md-4"><strong>F. Inicio:</strong> <span id="dpp_fecha_inicio"></span></div>
                                    <div class="col-md-4"><strong>F. Fin:</strong> <span id="dpp_fecha_fin"></span></div>
                                    <div class="col-md-4"><strong>Plazo:</strong> <span id="dpp_plazo"></span> meses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0 bg-transparent">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3"><i class="fa-solid fa-calculator me-2 text-primary"></i>Finanzas</h5>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Capital:</span>
                                    <strong id="dpp_capital"></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">% Interés:</span>
                                    <strong id="dpp_porcentaje_interes"></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Monto Interés:</span>
                                    <strong id="dpp_monto_interes"></strong>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Total a Pagar:</span>
                                    <strong id="dpp_importe"></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Pago Mensual:</span>
                                    <strong id="dpp_pago_mensual"></strong>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Abono Capital:</span>
                                    <strong id="dpp_abonos"></strong>
                                </div>
                                <div class="d-flex justify-content-between fs-5">
                                    <span class="fw-bold">Resto:</span>
                                    <strong id="dpp_resto" class="text-danger"></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Partidas -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <h6 class="fw-bold mb-0">Partidas (Abonos Realizados)</h6>
                        <button class="btn btn-sm btn-success" id="btnAgregarAbono">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Abono
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Saldo</th>
                                    <th>Recargo</th>
                                    <th>Firma</th>
                                    <th>Nota</th>
                                    <th style="width: 50px;"></th>
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

<!-- Modal Agregar Abono -->
<div class="modal fade" id="modalAgregarAbono" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formAgregarAbono">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar Abono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="abono_boleta_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Fecha</label>
                            <input type="date" class="form-control" id="abono_fecha" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Monto ($)</label>
                            <input type="number" step="0.01" class="form-control" id="abono_monto" min="0.01" required>
                        </div>
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between p-2 border rounded bg-body-tertiary">
                                <div><span class="text-muted small fw-bold">Resto Actual:</span> <strong id="lbl_abono_resto_actual" class="text-body">$0.00</strong></div>
                                <div><span class="text-muted small fw-bold">Nuevo Resto:</span> <strong id="lbl_abono_nuevo_resto" class="text-danger">$0.00</strong></div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Forma de Pago</label>
                            <select class="form-select select2-abono" id="abono_payment_method_id" required></select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Concepto / Referencia</label>
                            <input type="text" class="form-control" id="abono_concepto" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalPagoAcreedor'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetallePagoAcreedor'));
    const modalAbono = new bootstrap.Modal(document.getElementById('modalAgregarAbono'));
    const form = document.getElementById('formPagoAcreedor');
    const formAbono = document.getElementById('formAgregarAbono');

    let table = null;
    let optionsCache = null;
    let currentBoletaResto = 0;

    const formatter = new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    });

    function initSelect2() {
        $('.select2-pp').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalPagoAcreedor')
        });
        $('.select2-abono').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalAgregarAbono')
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

        fillSelect('creditor_id', optionsCache.creditors);
        fillSelect('development_id', optionsCache.developments);
        fillSelect('abono_payment_method_id', optionsCache.payment_methods);

        return optionsCache;
    }

    function resetForm() {
        form.reset();
        $('.select2-pp').val(null).trigger('change');
        document.getElementById('fecha_inicio').value = new Date().toISOString().slice(0, 10);
        document.getElementById('fecha_fin').value = '';
    }

    function autoCalcFechaFin() {
        const fechaInStr = document.getElementById('fecha_inicio').value;
        const plazoStr = document.getElementById('plazo').value;
        
        if (fechaInStr && plazoStr) {
            const date = new Date(fechaInStr);
            const months = parseInt(plazoStr, 10);
            if (!isNaN(months)) {
                // Sumar meses
                date.setMonth(date.getMonth() + months);
                document.getElementById('fecha_fin').value = date.toISOString().slice(0, 10);
            }
        }
    }

    document.getElementById('fecha_inicio').addEventListener('change', autoCalcFechaFin);
    document.getElementById('plazo').addEventListener('input', autoCalcFechaFin);

    function initTable() {
        table = $('#tblPagosAcreedores').DataTable({
            ajax: { url: '/pagos-acreedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'acreedor' },
                { data: 'lotificacion', render: d => d || '-' },
                { data: 'capital', render: d => formatter.format(d) },
                { data: 'importe', render: d => formatter.format(d) },
                { data: 'abonos', render: d => formatter.format(d) },
                { data: 'resto', render: d => `<span class="text-danger fw-bold">${formatter.format(d)}</span>` },
                { data: 'plazo', render: d => d + ' meses' },
                { data: 'acciones', orderable: false, searchable: false }
            ],
            pageLength: 10,
            order: [],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
        });
    }

    function autoCalcInteres() {
        const capitalStr = document.getElementById('capital').value;
        const porcentajeStr = document.getElementById('porcentaje_interes').value;
        
        let capital = parseFloat(capitalStr) || 0;
        let porcentaje = parseFloat(porcentajeStr) || 0;
        
        let montoInteres = capital * (porcentaje / 100);
        let importe = capital + montoInteres;
        
        document.getElementById('monto_interes').value = montoInteres.toFixed(2);
        document.getElementById('importe').value = importe.toFixed(2);
    }
    
    document.getElementById('capital').addEventListener('input', autoCalcInteres);
    document.getElementById('porcentaje_interes').addEventListener('input', autoCalcInteres);

    async function openNew() {
        await loadOptions();
        resetForm();
        modal.show();
    }

    async function saveBoleta(e) {
        e.preventDefault();

        const payload = {
            creditor_id: document.getElementById('creditor_id').value,
            development_id: document.getElementById('development_id').value,
            plazo: document.getElementById('plazo').value,
            fecha_inicio: document.getElementById('fecha_inicio').value,
            capital: document.getElementById('capital').value,
            porcentaje_interes: document.getElementById('porcentaje_interes').value,
            observacion: document.getElementById('observacion').value
        };

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
        document.getElementById('abono_boleta_id').value = id; // Para el modal de abonos
        document.getElementById('btnImprimirBoletaAcreedor').href = `/pagos-acreedores/${id}/pdf/boleta`;
        currentBoletaResto = parseFloat(d.resto || 0);

        document.getElementById('dpp_ref').innerText = d.numero_referencia || '-';
        document.getElementById('dpp_estado').innerHTML = `<span class="badge bg-primary">${d.estado}</span>`;
        document.getElementById('dpp_acreedor').innerText = d.acreedor || '-';
        document.getElementById('dpp_lotificacion').innerText = d.lotificacion || '-';
        document.getElementById('dpp_fecha_inicio').innerText = d.fecha_inicio || '-';
        document.getElementById('dpp_fecha_fin').innerText = d.fecha_fin || '-';
        document.getElementById('dpp_plazo').innerText = d.plazo || '0';

        document.getElementById('dpp_capital').innerText = formatter.format(d.capital || 0);
        document.getElementById('dpp_porcentaje_interes').innerText = (d.porcentaje_interes || 0) + '%';
        document.getElementById('dpp_monto_interes').innerText = formatter.format(d.monto_interes || 0);
        document.getElementById('dpp_importe').innerText = formatter.format(d.importe || 0);
        
        let pagoMensual = parseFloat(d.importe || 0) / parseInt(d.plazo || 1);
        document.getElementById('dpp_pago_mensual').innerText = formatter.format(pagoMensual);
        
        document.getElementById('dpp_abonos').innerText = formatter.format(d.abonos || 0);
        document.getElementById('dpp_resto').innerText = formatter.format(d.resto || 0);

        const tbody = document.getElementById('dppItemsBody');
        tbody.innerHTML = '';

        let saldoVariable = parseFloat(d.importe || 0);

        if (!(d.items && d.items.length)) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted">No hay abonos registrados</td></tr>`;
        } else {
            d.items.forEach((item, index) => {
                const montoAbono = parseFloat(item.importe || 0);
                const isRecargo = /recargo|inter[eé]s/i.test(item.concepto || '');
                
                let textMonto = '';
                let textRecargo = '';
                
                if (isRecargo) {
                    textRecargo = formatter.format(montoAbono);
                } else {
                    textMonto = formatter.format(montoAbono);
                    saldoVariable -= montoAbono;
                }
                
                tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.fecha ?? '-'}</td>
                        <td class="fw-bold">${textMonto}</td>
                        <td class="text-primary fw-bold">${formatter.format(Math.max(0, saldoVariable))}</td>
                        <td class="text-danger fw-bold">${textRecargo}</td>
                        <td></td>
                        <td>${item.concepto ?? ''}</td>
                        <td>
                            <a href="/pagos-acreedores/${id}/pdf/recibo/${item.id}" target="_blank" class="btn btn-sm btn-outline-danger" title="Imprimir Recibo">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                `;
            });
        }

        modalDetalle.show();
    }

    document.getElementById('btnNuevoPagoAcreedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveBoleta);

    $('#tblPagosAcreedores').on('click', '.btn-view', function () {
        viewItem(this.dataset.id);
    });

    document.getElementById('btnAgregarAbono').addEventListener('click', async () => {
        await loadOptions();
        formAbono.reset();
        document.getElementById('abono_fecha').value = new Date().toISOString().slice(0, 10);
        $('.select2-abono').val(null).trigger('change');
        
        // Reset labels
        document.getElementById('lbl_abono_resto_actual').innerText = formatter.format(currentBoletaResto);
        document.getElementById('lbl_abono_nuevo_resto').innerText = formatter.format(currentBoletaResto);
        
        modalAbono.show();
    });

    document.getElementById('abono_monto').addEventListener('input', function() {
        const monto = parseFloat(this.value || 0);
        const nuevoResto = Math.max(0, currentBoletaResto - monto);
        document.getElementById('lbl_abono_nuevo_resto').innerText = formatter.format(nuevoResto);
    });

    formAbono.addEventListener('submit', async function(e) {
        e.preventDefault();
        const boletaId = document.getElementById('abono_boleta_id').value;
        const payload = {
            fecha: document.getElementById('abono_fecha').value,
            monto: document.getElementById('abono_monto').value,
            payment_method_id: document.getElementById('abono_payment_method_id').value,
            concepto: document.getElementById('abono_concepto').value
        };

        try {
            const res = await fetch(`/pagos-acreedores/${boletaId}/abono`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Error al guardar abono');

            modalAbono.hide();
            Swal.fire({
                icon: 'success',
                title: 'Abono registrado',
                timer: 1500,
                showConfirmButton: false
            });

            // Refrescar vistas
            table.ajax.reload(null, false);
            viewItem(boletaId); // recargar detalle

        } catch(err) {
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    });

    initSelect2();
    initTable();
})();
</script>
@endpush