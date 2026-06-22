@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Boletas de Proveedores</h3>
            <div class="text-muted">Gestión de proyectos, contratos y abonos por lotificación</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoPagoProveedor">
            <i class="fa-solid fa-plus me-1"></i> Nueva Boleta
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblPagosProveedores">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Folio</th>
                    <th>Proveedor</th>
                    <th>Lotificación</th>
                    <th>Costo</th>
                    <th>Enganche</th>
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
<div class="modal fade" id="modalPagoProveedor" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formPagoProveedor">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Boleta / Proyecto de Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Proveedor</label>
                            <select class="form-select select2-pp" id="supplier_id" name="supplier_id"></select>
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
                            <input type="date" class="form-control bg-light" id="fecha_fin" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Enganche ($)</label>
                            <input type="number" step="0.01" class="form-control" id="enganche" name="enganche" min="0" value="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Total a Pagar (Costo) ($)</label>
                            <input type="number" step="0.01" class="form-control" id="importe" name="importe" min="0" required>
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
<div class="modal fade" id="modalDetallePagoProveedor" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Boleta y Abonos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body bg-light">
                <!-- Dashboard Cabecera -->
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3"><i class="fa-solid fa-file-contract me-2 text-primary"></i>Datos del Proyecto</h5>
                                <div class="row g-2 text-sm">
                                    <div class="col-md-6"><strong>Folio:</strong> <span id="dpp_ref"></span></div>
                                    <div class="col-md-6"><strong>Estado:</strong> <span id="dpp_estado"></span></div>
                                    <div class="col-md-6"><strong>Proveedor:</strong> <span id="dpp_proveedor"></span></div>
                                    <div class="col-md-6"><strong>Lotificación:</strong> <span id="dpp_lotificacion"></span></div>
                                    <div class="col-md-4"><strong>F. Inicio:</strong> <span id="dpp_fecha_inicio"></span></div>
                                    <div class="col-md-4"><strong>F. Fin:</strong> <span id="dpp_fecha_fin"></span></div>
                                    <div class="col-md-4"><strong>Plazo:</strong> <span id="dpp_plazo"></span> meses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0 bg-white">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3"><i class="fa-solid fa-calculator me-2 text-primary"></i>Finanzas</h5>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Costo:</span>
                                    <strong id="dpp_importe"></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Enganche:</span>
                                    <strong id="dpp_enganche"></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Abonos Acum.:</span>
                                    <strong id="dpp_abonos"></strong>
                                </div>
                                <hr class="my-2">
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
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h6 class="fw-bold mb-0">Partidas (Abonos Realizados)</h6>
                        <button class="btn btn-sm btn-success" id="btnAgregarAbono">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Abono
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Saldo Calculado</th>
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
                            <div class="d-flex justify-content-between p-2 bg-light border rounded">
                                <div><span class="text-muted small fw-bold">Resto Actual:</span> <strong id="lbl_abono_resto_actual" class="text-dark">$0.00</strong></div>
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
    const modal = new bootstrap.Modal(document.getElementById('modalPagoProveedor'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetallePagoProveedor'));
    const modalAbono = new bootstrap.Modal(document.getElementById('modalAgregarAbono'));
    const form = document.getElementById('formPagoProveedor');
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
            dropdownParent: $('#modalPagoProveedor')
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
        const res = await fetch('/pagos-proveedores/options');
        optionsCache = await res.json();

        fillSelect('supplier_id', optionsCache.suppliers);
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
        table = $('#tblPagosProveedores').DataTable({
            ajax: { url: '/pagos-proveedores/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'proveedor' },
                { data: 'lotificacion', render: d => d || '-' },
                { data: 'importe', render: d => formatter.format(d) },
                { data: 'enganche', render: d => formatter.format(d) },
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

    async function openNew() {
        await loadOptions();
        resetForm();
        modal.show();
    }

    async function saveBoleta(e) {
        e.preventDefault();

        const payload = {
            supplier_id: document.getElementById('supplier_id').value,
            development_id: document.getElementById('development_id').value,
            plazo: document.getElementById('plazo').value,
            fecha_inicio: document.getElementById('fecha_inicio').value,
            enganche: document.getElementById('enganche').value,
            importe: document.getElementById('importe').value,
            observacion: document.getElementById('observacion').value
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
            Swal.fire({ icon: 'error', title: 'Error', text: err.message });
        }
    }

    async function viewItem(id) {
        const res = await fetch(`/pagos-proveedores/${id}`);
        const json = await res.json();

        const d = json.data;
        document.getElementById('abono_boleta_id').value = id; // Para el modal de abonos
        currentBoletaResto = parseFloat(d.resto || 0);

        document.getElementById('dpp_ref').innerText = d.numero_referencia || '-';
        document.getElementById('dpp_estado').innerHTML = `<span class="badge bg-primary">${d.estado}</span>`;
        document.getElementById('dpp_proveedor').innerText = d.proveedor || '-';
        document.getElementById('dpp_lotificacion').innerText = d.lotificacion || '-';
        document.getElementById('dpp_fecha_inicio').innerText = d.fecha_inicio || '-';
        document.getElementById('dpp_fecha_fin').innerText = d.fecha_fin || '-';
        document.getElementById('dpp_plazo').innerText = d.plazo || '0';

        document.getElementById('dpp_importe').innerText = formatter.format(d.importe || 0);
        document.getElementById('dpp_enganche').innerText = formatter.format(d.enganche || 0);
        document.getElementById('dpp_abonos').innerText = formatter.format(d.abonos || 0);
        document.getElementById('dpp_resto').innerText = formatter.format(d.resto || 0);

        const tbody = document.getElementById('dppItemsBody');
        tbody.innerHTML = '';

        let saldoVariable = parseFloat(d.importe || 0) - parseFloat(d.enganche || 0);

        if (!(d.items && d.items.length)) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No hay abonos registrados</td></tr>`;
        } else {
            d.items.forEach((item, index) => {
                const montoAbono = parseFloat(item.importe || 0);
                saldoVariable -= montoAbono;
                
                tbody.innerHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${item.fecha ?? '-'}</td>
                        <td>${item.concepto ?? ''}</td>
                        <td class="fw-bold">${formatter.format(montoAbono)}</td>
                        <td class="text-danger fw-bold">${formatter.format(Math.max(0, saldoVariable))}</td>
                    </tr>
                `;
            });
        }

        modalDetalle.show();
    }

    document.getElementById('btnNuevoPagoProveedor').addEventListener('click', openNew);
    form.addEventListener('submit', saveBoleta);

    $('#tblPagosProveedores').on('click', '.btn-view', function () {
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
            const res = await fetch(`/pagos-proveedores/${boletaId}/abono`, {
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