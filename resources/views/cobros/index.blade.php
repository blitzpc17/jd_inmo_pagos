@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
<style>
    .charge-card {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 18px;
        background: var(--bs-body-bg);
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
        padding: 18px;
        margin-bottom: 16px;
    }

    .charge-title {
        font-weight: 800;
        margin: 0;
    }

    .charge-muted {
        color: #6b7280;
        font-size: .9rem;
    }

    .summary-box {
        border-radius: 16px;
        padding: 14px;
        border: 1px solid rgba(0,0,0,.08);
        background: rgba(248,249,250,.8);
        height: 100%;
    }

    .summary-box .label {
        font-size: .75rem;
        text-transform: uppercase;
        color: #6b7280;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .summary-box .value {
        font-size: 1.25rem;
        font-weight: 800;
        margin-top: 4px;
    }

    .concept-row {
        border-left: 5px solid #6b7280;
        border-radius: 12px;
        padding: 10px 12px;
        background: rgba(248,249,250,.9);
        margin-bottom: 8px;
    }

    .concept-row.danger {
        border-left-color: #dc2626;
    }

    .concept-row.warning {
        border-left-color: #f59e0b;
    }

    .concept-row.success {
        border-left-color: #16a34a;
    }

    .calendar-table th {
        white-space: nowrap;
        font-size: .78rem;
        text-transform: uppercase;
    }

    .badge-status {
        border-radius: 999px;
        padding: .35rem .65rem;
        font-size: .72rem;
        font-weight: 800;
    }

    .badge-status.success {
        background: #dcfce7;
        color: #166534;
    }

    .badge-status.danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-status.warning {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-status.secondary {
        background: #e5e7eb;
        color: #374151;
    }

    .money-input {
        font-size: 1.35rem;
        font-weight: 800;
    }

    .blocked-box {
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
        border-radius: 16px;
        padding: 14px;
        font-weight: 700;
    }

    .empty-state {
        border: 1px dashed rgba(0,0,0,.2);
        border-radius: 18px;
        padding: 32px;
        text-align: center;
        color: #6b7280;
    }

    #modalAssociatedCharges .modal-dialog {
        max-width: 1100px;
    }
</style>

<div class="page-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Cobros</h3>
            <div class="text-muted">
                Cobranza automática por mensualidad, parcialidades, atrasos, recargos, adelantos y liquidación.
            </div>
        </div>
    </div>
</div>

<div class="charge-card">
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Buscar cliente</label>
            <select id="client_id" class="form-select"></select>
        </div>

        <div class="col-md-7">
            <label class="form-label">Contrato del cliente</label>
            <select id="contract_id" class="form-select"></select>
        </div>
    </div>
</div>

<div id="blockedBox" class="blocked-box d-none mb-3"></div>

<div id="previewWrapper" class="d-none">
    <div class="charge-card">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <div class="d-flex align-items-center mb-1">
                    <h5 class="charge-title mb-0" id="contractTitle">Contrato</h5>
                    <span id="migrationBadge" class="badge bg-warning text-dark d-none ms-2 px-2 py-1"><i class="fa-solid fa-clock-rotate-left"></i> En Migración</span>
                </div>
                <div class="charge-muted" id="contractSubtitle"></div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-outline-danger btn-sm" id="btnPrintBoleta" type="button">
                    <i class="fa-solid fa-file-contract me-1"></i>
                    Imprimir contrato
                </button>

                <button class="btn btn-outline-secondary btn-sm" id="btnReloadPreview" type="button">
                    <i class="fa-solid fa-rotate me-1"></i>
                    Recalcular
                </button>
            </div>
        </div>

        <div class="row g-3" id="summaryBoxes"></div>
    </div>

    <div class="charge-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="charge-title">Desglose calculado</h5>
                <div class="charge-muted">
                    Conceptos que el sistema detecta que debe cubrir el cliente.
                </div>
            </div>

            <div class="text-end">
                <div class="charge-muted">Total mínimo a pagar</div>
                <div class="fs-4 fw-bold" id="minimumTotal">$0.00</div>
            </div>
        </div>

        <div id="conceptList"></div>
    </div>

    <div class="charge-card">
        <h5 class="charge-title mb-3">Registrar pago</h5>

        <form id="formCharge">
            @csrf

            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Monto recibido</label>
                    <input type="number" step="0.01" min="0.01" class="form-control money-input" id="monto" name="monto">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha y hora del cobro</label>
                    <input type="text" class="form-control" id="fecha_cobro" name="fecha_cobro" placeholder="Seleccione fecha/hora...">
                    <div class="form-check mt-2 d-none" id="waiveLateFeeContainer">
                        <input class="form-check-input" type="checkbox" id="waive_late_fee" name="waive_late_fee" value="1" checked>
                        <label class="form-check-label fw-bold text-primary small" for="waive_late_fee">
                            Exentar recargos (Migración)
                        </label>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Oficina recibe</label>
                    <select class="form-select" id="office_receives_charge_id" name="office_receives_charge_id"></select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Forma de pago</label>
                    <select class="form-select" id="payment_method_id" name="payment_method_id"></select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit" id="btnSaveCharge">
                        <i class="fa-solid fa-cash-register me-1"></i>
                        Registrar cobro
                    </button>
                </div>

                <div class="col-12">
                    <label class="form-label">Observación</label>
                    <textarea class="form-control text-uppercase" id="observacion" name="observacion" rows="2"></textarea>
                </div>
            </div>
        </form>
    </div>

    <div class="charge-card">
        <h5 class="charge-title mb-3">Calendario de pagos</h5>

        <div class="table-responsive">
            <table class="table table-bordered align-middle calendar-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Periodo</th>
                        <th>Vence</th>
                        <th class="text-end">Mensualidad</th>
                        <th class="text-end">Pagado</th>
                        <th class="text-end">Pendiente</th>
                        <th class="text-end">Recargo</th>
                        <th>Estado</th>
                        <th>Recibos</th>
                    </tr>
                </thead>
                <tbody id="calendarBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="emptyState" class="empty-state">
    <i class="fa-solid fa-magnifying-glass-dollar fa-2x mb-3"></i>
    <div class="fw-bold">Selecciona un cliente y un contrato</div>
    <div>El sistema calculará automáticamente mensualidades, atrasos, recargos, adelantos y liquidación.</div>
</div>

<div class="modal fade" id="modalAssociatedCharges" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius: 18px; overflow: hidden;">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-receipt me-2"></i>
                        Cobros asociados
                    </h5>
                    <div class="text-muted small">
                        Puedes imprimir el recibo individual de cada cobro.
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="chkIncludeScheduleReceipt">
                    <label class="form-check-label" for="chkIncludeScheduleReceipt">
                        Adjuntar calendario de pagos al recibo
                    </label>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Tipo cobro</th>
                                <th>Forma pago</th>
                                <th>Oficina</th>
                                <th class="text-end">Monto</th>
                                <th class="text-end">Recargo</th>
                                <th class="text-end">Total</th>
                                <th>Recibo</th>
                            </tr>
                        </thead>
                        <tbody id="associatedChargesBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script>
(() => {
    let optionsCache = {
        offices: [],
        payment_methods: []
    };

    let currentPreview = null;
    let modalAssociatedCharges = null;

    function money(value) {
        return Number(value || 0).toLocaleString('es-MX', {
            style: 'currency',
            currency: 'MXN'
        });
    }

    function plainMoney(value) {
        return Number(value || 0).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function fillSelect(selector, rows, placeholder = 'Seleccione...') {
        const el = $(selector);
        el.html(`<option value="">${placeholder}</option>`);

        (rows || []).forEach(row => {
            el.append(`<option value="${row.value}">${row.text}</option>`);
        });

        el.trigger('change.select2');
    }

    function initSelect2() {
        $('#client_id').select2({
            width: '100%',
            placeholder: 'Buscar cliente...',
            ajax: {
                url: '{{ route('cobros.clients') }}',
                dataType: 'json',
                delay: 300,
                data: params => ({
                    q: params.term || ''
                }),
                processResults: data => data
            }
        });

        $('#contract_id').select2({
            width: '100%',
            placeholder: 'Seleccione contrato...'
        });

        $('#office_receives_charge_id, #payment_method_id').select2({
            width: '100%'
        });
    }

    async function loadOptions() {
        optionsCache = {
            offices: [],
            payment_methods: []
        };

        fillSelect('#office_receives_charge_id', [], 'Primero selecciona contrato...');
        fillSelect('#payment_method_id', [], 'Primero selecciona oficina...');
    }

    async function loadContractOffices(contractId) {
        fillSelect('#office_receives_charge_id', [], 'Cargando oficinas...');
        fillSelect('#payment_method_id', [], 'Primero selecciona oficina...');

        if (!contractId) {
            fillSelect('#office_receives_charge_id', [], 'Primero selecciona contrato...');
            return;
        }

        const res = await fetch(`/cobros/contract/${contractId}/offices`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        if (!res.ok) {
            Swal.fire('Error', json.message || 'No se pudieron cargar las oficinas.', 'error');
            fillSelect('#office_receives_charge_id', [], 'Sin oficinas...');
            return;
        }

        fillSelect('#office_receives_charge_id', json.data || [], 'Oficina...');

        if ((json.data || []).length === 1) {
            $('#office_receives_charge_id')
                .val(String(json.data[0].value))
                .trigger('change');
        }
    }

    async function loadOfficePaymentMethods(contractId, officeId) {
        fillSelect('#payment_method_id', [], 'Cargando formas de pago...');

        if (!contractId || !officeId) {
            fillSelect('#payment_method_id', [], 'Primero selecciona oficina...');
            return;
        }

        const res = await fetch(`/cobros/contract/${contractId}/office/${officeId}/payment-methods`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        if (!res.ok) {
            Swal.fire('Error', json.message || 'No se pudieron cargar las formas de pago.', 'error');
            fillSelect('#payment_method_id', [], 'Sin formas de pago...');
            return;
        }

        fillSelect('#payment_method_id', json.data || [], 'Forma de pago...');

        if ((json.data || []).length === 1) {
            $('#payment_method_id')
                .val(String(json.data[0].value))
                .trigger('change');
        }
    }

    async function loadContracts(clientId) {
        $('#contract_id').html('<option value="">Seleccione contrato...</option>').trigger('change');
        hidePreview();

        if (!clientId) return;

        const res = await fetch(`/cobros/client/${clientId}/contracts`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        (json.data || []).forEach(row => {
            const disabled = row.can_charge ? '' : 'disabled';
            const badge = row.can_charge ? '' : ' - NO PERMITE COBROS';

            const migrationAttr = row.is_migration ? 'data-is-migration="1"' : '';

            $('#contract_id').append(`
                <option value="${row.id}" ${disabled} ${migrationAttr}>
                    ${row.text}${badge}
                </option>
            `);
        });

        $('#contract_id').trigger('change.select2');
    }

    async function loadPreview(contractId) {
        hidePreview();

        if (!contractId) return;

        const fecha = $('#fecha_cobro').val() || '';
        const waive = $('#waive_late_fee').is(':checked') ? 1 : 0;
        const qs = new URLSearchParams({ fecha_cobro: fecha, waive_late_fee: waive }).toString();

        const res = await fetch(`/cobros/contract/${contractId}/preview?${qs}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();
        currentPreview = json;

        if (!json.can_charge) {
            $('#blockedBox')
                .removeClass('d-none')
                .text(json.blocking_reason || 'Este contrato no permite cobros.');

            $('#emptyState').addClass('d-none');

            return;
        }

        $('#blockedBox').addClass('d-none').text('');
        $('#previewWrapper').removeClass('d-none');
        $('#emptyState').addClass('d-none');

        renderPreview(json);
    }

    function hidePreview() {
        $('#previewWrapper').addClass('d-none');
        $('#blockedBox').addClass('d-none').text('');
        $('#emptyState').removeClass('d-none');

        currentPreview = null;
    }

    function renderPreview(preview) {
        const contract = preview.contract || {};
        const required = preview.required_payment || {};

        $('#contractTitle').text(`${contract.folio || 'Contrato'} - ${contract.cliente || ''}`);
        const mesesText = (contract.tipo_pago && contract.tipo_pago.toUpperCase().includes('CRÉDITO') && contract.meses > 0) ? ` (${contract.meses} meses)` : '';
        $('#contractSubtitle').text(`${contract.lotificacion || ''} | ${contract.tipo_pago || ''}${mesesText} | Estado: ${contract.estado_nombre || ''}`);

        const isMigration = $('#contract_id option:selected').data('is-migration') === 1;
        if (isMigration) {
            $('#migrationBadge').removeClass('d-none');
            $('#waiveLateFeeContainer').removeClass('d-none');
        } else {
            $('#migrationBadge').addClass('d-none');
            $('#waiveLateFeeContainer').addClass('d-none');
        }

        $('#summaryBoxes').html(`
            ${summaryBox('Mensualidad', money(contract.mensualidad), 'info')}
            ${summaryBox('Saldo estimado', money(contract.saldo_estimado), 'warning')}
            ${summaryBox('Pagado acumulado', money(contract.pagado_acumulado), 'success')}
            ${summaryBox('Liquidación', money(required.total_liquidacion), 'danger')}
        `);

        $('#minimumTotal').text(money(required.total_minimo || 0));

        if (!$('#monto').val()) {
            $('#monto').val(required.total_minimo ? plainMoney(required.total_minimo) : '');
        }

        renderConcepts(required.conceptos || []);
        renderCalendar(preview.calendar || []);
    }

    function summaryBox(label, value, type) {
        return `
            <div class="col-md-3">
                <div class="summary-box">
                    <div class="label">${label}</div>
                    <div class="value">${value}</div>
                </div>
            </div>
        `;
    }

    function renderConcepts(concepts) {
        const box = $('#conceptList');
        box.html('');

        if (!concepts.length) {
            box.html(`
                <div class="concept-row success">
                    <div class="fw-bold">Sin atrasos detectados</div>
                    <div class="text-muted">El sistema permitirá mensualidad actual, parcial, adelantos o liquidación.</div>
                </div>
            `);
            return;
        }

        concepts.forEach(item => {
            const css = item.tipo.includes('ATRASADA') || item.tipo.includes('RECARGO') ? 'danger' : 'warning';

            box.append(`
                <div class="concept-row ${css}">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="fw-bold">${item.periodo || ''}</div>
                            <div class="text-muted small">Periodo</div>
                        </div>
                        <div class="col-md-3">
                            <div class="fw-bold">${item.tipo || ''}</div>
                            <div class="text-muted small">Concepto</div>
                        </div>
                        <div class="col-md-2 text-md-end">
                            <div class="fw-bold">${money(item.monto)}</div>
                            <div class="text-muted small">Monto</div>
                        </div>
                        <div class="col-md-4">
                            <div>${item.razon || ''}</div>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    function renderCalendar(rows) {
        const tbody = $('#calendarBody');
        tbody.html('');

        if (!rows.length) {
            tbody.html('<tr><td colspan="9" class="text-center text-muted">Sin calendario de pagos.</td></tr>');
            return;
        }

        rows.forEach(row => {
            const chargeCount = Number(row.charge_count || 0);

            tbody.append(`
                <tr>
                    <td>${row.installment_number}</td>
                    <td>${row.periodo}</td>
                    <td>${row.due_date}</td>
                    <td class="text-end">${money(row.amount)}</td>
                    <td class="text-end">${money(row.amount_paid)}</td>
                    <td class="text-end">${money(row.principal_remaining)}</td>
                    <td class="text-end">${money(row.late_fee_amount)}</td>
                    <td>
                        <span class="badge-status ${row.ui_class || 'secondary'}">
                            ${row.status}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm ${chargeCount > 0 ? 'btn-outline-primary' : 'btn-outline-secondary'} btn-view-schedule-charges"
                                type="button"
                                data-schedule-id="${row.id}">
                            <i class="fa-solid fa-receipt me-1"></i>
                            ${chargeCount > 0 ? `Ver (${chargeCount})` : 'Sin cobros'}
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    function renderAssociatedCharges(charges) {
        const tbody = $('#associatedChargesBody');
        tbody.html('');

        if (!charges || !charges.length) {
            tbody.html(`
                <tr>
                    <td colspan="10" class="text-center text-muted">
                        No hay cobros asociados.
                    </td>
                </tr>
            `);

            modalAssociatedCharges.show();
            return;
        }

        charges.forEach((charge, index) => {
            const monto = Number(charge.monto || 0);
            const recargo = Number(charge.monto_recargo || 0);
            const total = Number(charge.total_amount ?? charge.total ?? (monto + recargo));
            const receiptUrl = charge.receipt_url || `/cobros/${charge.id}/receipt`;

            tbody.append(`
                <tr>
                    <td>${index + 1}</td>
                    <td>${charge.numero_referencia || ''}</td>
                    <td>${charge.fecha_emision || ''}</td>
                    <td>
                        <span class="badge bg-dark">
                            ${charge.tipo || charge.tipo_cobro || ''}
                        </span>
                    </td>
                    <td>${charge.forma_pago || ''}</td>
                    <td>${charge.oficina_recibe || ''}</td>
                    <td class="text-end">${money(monto)}</td>
                    <td class="text-end">${money(recargo)}</td>
                    <td class="text-end fw-bold">${money(total)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-danger btn-print-receipt"
                                type="button"
                                data-url="${receiptUrl}">
                            <i class="fa-solid fa-file-pdf me-1"></i>
                            Recibo
                        </button>
                    </td>
                </tr>
            `);
        });

        modalAssociatedCharges.show();
    }

    async function openPaymentGroup(paymentGroupUuid) {
        if (!paymentGroupUuid) {
            Swal.fire('Aviso', 'No se encontró el grupo de cobros.', 'warning');
            return;
        }

        try {
            const res = await fetch(`/cobros/group/${paymentGroupUuid}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();

            if (!res.ok || !json.ok) {
                throw new Error(json.message || 'No se pudieron cargar los cobros asociados.');
            }

            renderAssociatedCharges(json.data || []);
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function openScheduleCharges(scheduleId) {
        if (!scheduleId) {
            Swal.fire('Aviso', 'No se encontró la mensualidad.', 'warning');
            return;
        }

        try {
            const res = await fetch(`/cobros/schedule/${scheduleId}/charges`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const json = await res.json();

            if (!res.ok || !json.ok) {
                throw new Error(json.message || 'No se pudieron cargar los cobros de la mensualidad.');
            }

            renderAssociatedCharges(json.data || []);
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    function openReceiptWithScheduleQuestion(url) {
        if (!url) {
            Swal.fire('Aviso', 'No se encontró la URL del recibo.', 'warning');
            return;
        }

        Swal.fire({
            icon: 'question',
            title: 'Recibo',
            text: '¿Quieres adjuntar el calendario de pagos al recibo?',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Sí, adjuntar',
            denyButtonText: 'No, solo recibo',
            cancelButtonText: 'Cancelar'
        }).then(answer => {
            let finalUrl = url;

            if (answer.isConfirmed) {
                finalUrl += finalUrl.includes('?') ? '&include_schedule=1' : '?include_schedule=1';
                window.open(finalUrl, '_blank');
            }

            if (answer.isDenied) {
                window.open(finalUrl, '_blank');
            }
        });
    }

    async function saveCharge(e) {
        e.preventDefault();

        const contractId = $('#contract_id').val();

        if (!contractId) {
            Swal.fire('Aviso', 'Selecciona un contrato.', 'warning');
            return;
        }

        const payload = {
            monto: $('#monto').val(),
            payment_method_id: $('#payment_method_id').val(),
            office_receives_charge_id: $('#office_receives_charge_id').val(),
            observacion: $('#observacion').val(),
            fecha_cobro: $('#fecha_cobro').val(),
            waive_late_fee: $('#waive_late_fee').is(':checked') ? 1 : 0
        };

        try {
            const res = await fetch(`/cobros/contract/${contractId}/charge`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();

            if (!res.ok || !json.ok) {
                throw new Error(json.message || 'No se pudo registrar el cobro.');
            }

            const chargesCount = (json.charges || []).length;

            if (chargesCount > 1) {
                Swal.fire({
                    icon: 'success',
                    title: 'Cobros registrados',
                    html: `
                        <div>${json.message}</div>
                        <div class="mt-2">
                            <strong>Cobros generados:</strong> ${chargesCount}
                        </div>
                        <div class="small text-muted mt-2">
                            Puedes imprimir el recibo individual de cada cobro.
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Ver cobros',
                    cancelButtonText: 'Cerrar'
                }).then(result => {
                    if (result.isConfirmed) {
                        if (json.payment_group_uuid) {
                            openPaymentGroup(json.payment_group_uuid);
                        } else {
                            renderAssociatedCharges(json.charges || []);
                        }
                    }
                });
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Cobro registrado',
                    html: `
                        <div>${json.message}</div>
                        <div class="mt-2">
                            <strong>Cobros generados:</strong> ${chargesCount}
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Ver recibo',
                    cancelButtonText: 'Cerrar'
                }).then(result => {
                    if (result.isConfirmed && json.receipt_url) {
                        openReceiptWithScheduleQuestion(json.receipt_url);
                    }
                });
            }

            $('#monto').val('');
            $('#observacion').val('');

            await loadPreview(contractId);
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    $('#client_id').on('change', function () {
        loadContracts(this.value);
    });

    $('#waive_late_fee').on('change', function() {
        if ($('#contract_id').val()) {
            loadPreview($('#contract_id').val());
        }
    });

    $('#contract_id').on('change', async function () {
        const contractId = this.value;

        await loadPreview(contractId);
        await loadContractOffices(contractId);
    });

    $('#office_receives_charge_id').on('change', function () {
        const contractId = $('#contract_id').val();
        const officeId = this.value;

        loadOfficePaymentMethods(contractId, officeId);
    });

    $('#btnReloadPreview').on('click', function () {
        loadPreview($('#contract_id').val());
    });

    $('#btnPrintBoleta').on('click', function () {
        const contractId = $('#contract_id').val();

        if (!contractId) {
            Swal.fire('Aviso', 'Selecciona un contrato.', 'warning');
            return;
        }

        window.open(`/contratos/${contractId}/documento`, '_blank');
    });

    $(document).on('click', '.btn-print-receipt', function () {
        let url = $(this).data('url');

        if (!url) {
            Swal.fire('Aviso', 'No se encontró la URL del recibo.', 'warning');
            return;
        }

        const includeSchedule = $('#chkIncludeScheduleReceipt').is(':checked');

        if (includeSchedule) {
            url += url.includes('?') ? '&include_schedule=1' : '?include_schedule=1';
        }

        window.open(url, '_blank');
    });

    $(document).on('click', '.btn-view-schedule-charges', function () {
        const scheduleId = $(this).data('schedule-id');
        openScheduleCharges(scheduleId);
    });

    $('#formCharge').on('submit', saveCharge);

    flatpickr("#fecha_cobro", {
        enableTime: true,
        dateFormat: "Y-m-d H:i:S",
        enableSeconds: true,
        defaultDate: new Date(),
        locale: "es",
        time_24hr: true
    });

    initSelect2();
    modalAssociatedCharges = new bootstrap.Modal(document.getElementById('modalAssociatedCharges'));
    loadOptions();
})();
</script>
@endpush