@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Modificaciones Masivas y Solicitudes</h3>
            <div class="text-muted">
                Solicita modificaciones de datos o eliminaciones para múltiples cobros, contratos o apartados de forma simultánea. Las solicitudes requieren autorización de un usuario facultado.
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs nav-tabs-custom" id="moduleTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests-pane" type="button" role="tab"><i class="fa-solid fa-list-check me-2"></i>Solicitudes</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="new-request-tab" data-bs-toggle="tab" data-bs-target="#new-request-pane" type="button" role="tab"><i class="fa-solid fa-circle-plus me-2"></i>Nueva Solicitud</button>
    </li>
</ul>

<div class="tab-content" id="moduleTabContent">
    <!-- Pestaña de Solicitudes -->
    <div class="tab-pane fade show active" id="requests-pane" role="tabpanel" tabindex="0">
        <div class="page-card">
            <div class="table-responsive">
                <table class="table table-bordered align-middle w-100" id="tableRequests">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tipo</th>
                            <th>Cant. Items</th>
                            <th>Justificación</th>
                            <th>Solicitante</th>
                            <th>Estado</th>
                            <th>F. Solicitud</th>
                            <th>Autorizado/Rechazado Por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pestaña de Nueva Solicitud -->
    <div class="tab-pane fade" id="new-request-pane" role="tabpanel" tabindex="0">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="page-card h-100">
                    <h5 class="fw-bold mb-3">1. Tipo de Corrección</h5>
                    <div class="d-flex flex-column gap-3">
                        <div class="type-tile active" data-type="CONTRATO">
                            <i class="fa-solid fa-file-contract"></i>
                            <div class="fw-bold">Contratos</div>
                            <small class="text-muted">Ajuste de fecha de emisión o eliminación.</small>
                        </div>
                        <div class="type-tile" data-type="COBRO">
                            <i class="fa-solid fa-cash-register"></i>
                            <div class="fw-bold">Cargos</div>
                            <small class="text-muted">Fechas, montos, recargos, o eliminación de cobros.</small>
                        </div>
                        <div class="type-tile" data-type="APARTADO">
                            <i class="fa-solid fa-bookmark"></i>
                            <div class="fw-bold">Apartados</div>
                            <small class="text-muted">Importes, vigencias o eliminación de apartados.</small>
                        </div>
                        <div class="type-tile" data-type="BOLETA_PROVEEDOR">
                            <i class="fa-solid fa-file-invoice-dollar"></i>
                            <div class="fw-bold">Boletas (Proveedor)</div>
                            <small class="text-muted">Ajuste de total a pagar, plazo, fechas y lotificación.</small>
                        </div>
                        <div class="type-tile" data-type="PARTIDA_PROVEEDOR">
                            <i class="fa-solid fa-receipt"></i>
                            <div class="fw-bold">Partidas (Proveedor)</div>
                            <small class="text-muted">Ajuste de montos y fechas de abonos a proveedores.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <!-- Buscador en cascada -->
                <div class="page-card mb-3">
                    <h5 class="fw-bold mb-3">2. Seleccionar Registro</h5>
                    <div class="row g-3" id="cascade_clients">
                        <div class="col-md-6" id="client_select_wrapper">
                            <label class="form-label fw-bold">Cliente</label>
                            <select id="client_select" class="form-select"></select>
                        </div>
                        <div class="col-md-6" id="contract_select_wrapper">
                            <label class="form-label fw-bold">Contrato</label>
                            <select id="contract_select" class="form-select" disabled>
                                <option value="">Selecciona cliente primero...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row g-3 d-none" id="cascade_suppliers">
                        <div class="col-md-4" id="supplier_select_wrapper">
                            <label class="form-label fw-bold">Proveedor</label>
                            <select id="supplier_select" class="form-select"></select>
                        </div>
                        <div class="col-md-8" id="boleta_select_wrapper">
                            <label class="form-label fw-bold">Boleta / Proyecto</label>
                            <select id="boleta_select" class="form-select" disabled>
                                <option value="">Selecciona proveedor primero...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Detalles de Carga -->
                <div class="page-card mb-3 d-none" id="detailsContractCard">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Detalles del Contrato</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Referencia:</strong> <span id="lblContractRef"></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Fecha Emisión:</strong> <span id="lblContractDate"></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Lotificación:</strong> <span id="lblContractLot"></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" id="btnEditContract">
                            <i class="fa-solid fa-pen me-1"></i> Solicitar Modificación de Fecha
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="btnDeleteContract">
                            <i class="fa-solid fa-trash me-1"></i> Solicitar Eliminación de Contrato
                        </button>
                    </div>
                </div>

                <!-- Tabla de Cargos Cargados -->
                <div class="page-card mb-3 d-none" id="tableChargesCard">
                    <h5 class="fw-bold mb-3">
                        <i class="fa-solid fa-money-check-dollar me-2 text-primary"></i>Cobros Realizados en este Contrato 
                        <span class="badge bg-primary ms-2" id="chargesCountBadge">0</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary float-end" id="btnSelectAllCharges">
                            <i class="fa-solid fa-check-double me-1"></i> Seleccionar Todos
                        </button>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100" id="tableContractCharges">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha Emisión</th>
                                    <th>Monto</th>
                                    <th>Monto Recargo</th>
                                    <th>Forma Pago</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabla de Apartados Cargados -->
                <div class="page-card mb-3 d-none" id="tableReservationsCard">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-bookmark me-2 text-primary"></i>Apartados del Cliente</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100" id="tableClientReservations">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Fecha Emisión</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Importe</th>
                                    <th>Asociado a Contrato?</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- Detalles de Boleta Proveedor -->
                <div class="page-card mb-3 d-none" id="detailsBoletaCard">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Detalles de la Boleta</h5>
                    <div class="row mb-3">
                        <div class="col-md-3"><strong>Referencia:</strong> <span id="lblBoletaRef"></span></div>
                        <div class="col-md-3"><strong>Lotificación:</strong> <span id="lblBoletaLot"></span></div>
                        <div class="col-md-3"><strong>Total (Costo):</strong> <span id="lblBoletaCosto"></span></div>
                        <div class="col-md-3"><strong>Enganche:</strong> <span id="lblBoletaEnganche"></span></div>
                        <div class="col-md-3"><strong>F. Inicio:</strong> <span id="lblBoletaInicio"></span></div>
                        <div class="col-md-3"><strong>Plazo:</strong> <span id="lblBoletaPlazo"></span> meses</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" id="btnEditBoleta">
                            <i class="fa-solid fa-pen me-1"></i> Solicitar Modificación de Boleta
                        </button>
                    </div>
                </div>

                <!-- Tabla de Partidas Proveedor -->
                <div class="page-card mb-3 d-none" id="tablePartidasCard">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-receipt me-2 text-primary"></i>Abonos (Partidas) de la Boleta</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100" id="tableBoletaPartidas">
                            <thead>
                                <tr>
                                    <th># Partida</th>
                                    <th>Fecha</th>
                                    <th>Importe</th>
                                    <th>Concepto</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- Lista de Solicitudes Queue -->
                <div class="page-card">
                    <h5 class="fw-bold mb-3">3. Registros en la Solicitud <span class="badge bg-primary ms-2" id="selectedRecordsCountBadge">0</span></h5>
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered align-middle" id="tableSelectedRecords">
                            <thead>
                                <tr id="headersSelectedRecords"></tr>
                            </thead>
                            <tbody id="bodySelectedRecords">
                                <tr>
                                    <td colspan="20" class="text-center text-muted py-4">No has agregado ningún registro a la lista.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Justificación de la solicitud (Requerido)</label>
                        <textarea class="form-control text-uppercase" id="justification" rows="3" placeholder="EXPLICA DETALLADAMENTE POR QUÉ SE REALIZA ESTA SOLICITUD DE MODIFICACIÓN O ELIMINACIÓN..."></textarea>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-success" id="btnSubmitRequest">
                            <i class="fa-solid fa-paper-plane me-1"></i> Enviar Solicitud
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalle y Aprobación/Rechazo de Solicitudes -->
<div class="modal fade" id="modalRequestDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-info me-2"></i>Detalle de Solicitud</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <strong>Solicitante:</strong>
                        <div id="detailRequester" class="fs-5 text-muted"></div>
                    </div>
                    <div class="col-md-3">
                        <strong>Tipo de Registro:</strong>
                        <div id="detailType" class="fs-5 text-muted"></div>
                    </div>
                    <div class="col-md-3">
                        <strong>Fecha Solicitud:</strong>
                        <div id="detailDate" class="fs-5 text-muted"></div>
                    </div>
                    <div class="col-md-3">
                        <strong>Estado Actual:</strong>
                        <div id="detailStatus"></div>
                    </div>
                    <div class="col-md-12">
                        <strong>Justificación:</strong>
                        <div id="detailJustification" class="p-3 bg-light rounded mt-1 border text-uppercase"></div>
                    </div>
                    <div class="col-md-12 d-none" id="detailRejectionRow">
                        <strong class="text-danger">Motivo de Rechazo:</strong>
                        <div id="detailRejectionReason" class="p-3 bg-red-light rounded mt-1 border border-danger text-uppercase text-danger"></div>
                    </div>
                </div>

                <h5 class="fw-bold mb-3"><i class="fa-solid fa-code-compare me-2"></i>Comparativa de Cambios / Acción</h5>
                <div class="table-responsive">
                    <table class="table table-bordered comparison-table">
                        <thead>
                            <tr>
                                <th>Registro (Folio)</th>
                                <th>Acción</th>
                                <th>Detalle / Campo</th>
                                <th class="text-center">Valor Original</th>
                                <th class="text-center">Valor Nuevo</th>
                            </tr>
                        </thead>
                        <tbody id="detailComparisonBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" id="modalRequestFooter"></div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .nav-tabs-custom {
        border-bottom: 2px solid var(--border);
        margin-bottom: 20px;
    }
    .nav-tabs-custom .nav-link {
        border: none;
        color: var(--muted);
        font-weight: 700;
        padding: 12px 20px;
        position: relative;
        background: transparent;
        transition: color .2s ease;
    }
    .nav-tabs-custom .nav-link.active {
        color: var(--primary);
    }
    .nav-tabs-custom .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: var(--primary);
    }
    .type-tile {
        border: 2px solid var(--border);
        border-radius: 18px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.25s ease;
        background: #fff;
    }
    .type-tile:hover {
        border-color: var(--primary);
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.05);
    }
    .type-tile.active {
        border-color: var(--primary);
        background: rgba(7, 89, 140, 0.05);
        color: var(--primary);
    }
    .type-tile i {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    .comparison-table th {
        background: #eef5fb !important;
        font-size: .8rem;
        text-transform: uppercase;
    }
    .comparison-table td {
        vertical-align: middle;
    }
    .diff-removed {
        background-color: #fee2e2;
        color: #991b1b;
        text-decoration: line-through;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .diff-added {
        background-color: #dcfce7;
        color: #166534;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 700;
    }
    .badge-status-req {
        border-radius: 999px;
        padding: .35rem .65rem;
        font-size: .72rem;
        font-weight: 800;
    }
    .badge-status-req.PENDIENTE {
        background: #fef3c7;
        color: #92400e;
    }
    .badge-status-req.APROBADO {
        background: #dcfce7;
        color: #166534;
    }
    .badge-status-req.RECHAZADO {
        background: #fee2e2;
        color: #991b1b;
    }
    .bg-light-success {
        background-color: rgba(22, 163, 74, 0.08) !important;
        border-left: 4px solid #16a34a !important;
    }
    .bg-light-warning {
        background-color: rgba(217, 119, 6, 0.08) !important;
        border-left: 4px solid #d97706 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script>
(() => {
    let currentType = 'CONTRATO';
    let catalogsOptions = {};
    let selectedRecords = [];
    let tableRequests = null;
    let modalDetail = null;
    let loadedCharges = [];
    let loadedReservations = [];
    let loadedContractDetails = null;

    // Available fields configuration for inline edits
    const fieldsConfig = {
        COBRO: {
            headers: ['Folio', 'Acción', 'Fecha Emisión', 'Monto', 'Monto Recargo', 'Oficina', 'Forma Pago', 'Estado', 'Quitar'],
            fields: [
                {name: 'fecha_emision', label: 'Fecha Emisión', type: 'date'},
                {name: 'monto', label: 'Monto', type: 'number'},
                {name: 'monto_recargo', label: 'Monto Recargo', type: 'number'},
                {name: 'office_receives_charge_id', label: 'Oficina', type: 'select', optionKey: 'offices'},
                {name: 'payment_method_id', label: 'Forma Pago', type: 'select', optionKey: 'payment_methods'},
                {name: 'status_id', label: 'Estado', type: 'select', optionKey: 'charge_statuses'}
            ]
        },
        CONTRATO: {
            headers: ['Folio', 'Acción', 'Fecha Emisión', 'Quitar'],
            fields: [
                {name: 'fecha_emision', label: 'Fecha Emisión', type: 'date'}
            ]
        },
        APARTADO: {
            headers: ['Folio', 'Acción', 'Fecha Emisión', 'Fecha Vencimiento', 'Importe Apartado', 'Estado', 'Quitar'],
            fields: [
                {name: 'fecha_emision', label: 'Fecha Emisión', type: 'date'},
                {name: 'fecha_vencimiento', label: 'Fecha Vencimiento', type: 'date'},
                {name: 'importe_apartado', label: 'Importe Apartado', type: 'number'},
                {name: 'status_id', label: 'Estado', type: 'select', optionKey: 'reservation_statuses'}
            ]
        },
        BOLETA_PROVEEDOR: {
            headers: ['Folio', 'Acción', 'Proveedor', 'Lotificación', 'F. Inicio', 'Costo (Total)', 'Enganche ($)', 'Plazo (Meses)', 'Quitar'],
            fields: [
                {name: 'supplier_id', label: 'Proveedor', type: 'select', optionKey: 'suppliers'},
                {name: 'development_id', label: 'Lotificación', type: 'select', optionKey: 'developments'},
                {name: 'fecha_inicio', label: 'Fecha Inicio', type: 'date'},
                {name: 'importe', label: 'Costo (Total)', type: 'number'},
                {name: 'enganche', label: 'Enganche', type: 'number'},
                {name: 'plazo', label: 'Plazo (Meses)', type: 'integer'}
            ]
        },
        PARTIDA_PROVEEDOR: {
            headers: ['Partida', 'Acción', 'Fecha', 'Monto ($)', 'Quitar'],
            fields: [
                {name: 'fecha', label: 'Fecha', type: 'date'},
                {name: 'importe', label: 'Monto', type: 'number'}
            ]
        }
    };

    function initDataTables() {
        tableRequests = $('#tableRequests').DataTable({
            processing: true,
            ajax: '{{ route('bulk-modifications.datatable') }}',
            columns: [
                {data: 'id', render: val => 'REQ-' + String(val).padStart(5, '0')},
                {data: 'type'},
                {data: 'items_count'},
                {data: 'justification', render: val => `<span class="text-truncate d-inline-block text-uppercase" style="max-width: 250px;">${val || ''}</span>`},
                {data: 'requested_by_alias'},
                {data: 'status', render: val => `<span class="badge-status-req ${val}">${val}</span>`},
                {data: 'created_at', render: val => val ? val.substring(0, 19).replace('T', ' ') : ''},
                {
                    data: null, 
                    render: row => {
                        if (row.status === 'APROBADO') return row.authorized_by_alias || '';
                        if (row.status === 'RECHAZADO') return (row.rejected_by_alias || '') + (row.rejection_reason ? ` (Motivo: ${row.rejection_reason})` : '');
                        return '';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: row => `
                        <button class="btn btn-sm btn-outline-primary btn-view-request" data-id="${row.id}">
                            <i class="fa-solid fa-eye"></i> Revisar
                        </button>
                    `
                }
            ],
            order: [[0, 'desc']]
        });
    }

    async function loadOptions() {
        const res = await fetch('{{ route('bulk-modifications.options') }}');
        catalogsOptions = await res.json();
    }

    function initCascades() {
        // Init Client Select2
        $('#client_select').select2({
            width: '100%',
            placeholder: 'Buscar cliente...',
            minimumInputLength: 1,
            ajax: {
                url: '{{ route('bulk-modifications.clients') }}',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || ''
                }),
                processResults: data => ({
                    results: data.map(item => ({ id: item.id, text: item.text }))
                })
            }
        });

        $('#supplier_select').select2({
            width: '100%',
            placeholder: 'Buscar proveedor...',
            minimumInputLength: 1,
            ajax: {
                url: '{{ route('bulk-modifications.suppliers') }}',
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || ''
                }),
                processResults: data => ({
                    results: data.map(item => ({ id: item.id, text: item.text }))
                })
            }
        });

        // Client change -> load contracts/apartados
        $('#client_select').on('change', async function() {
            const clientId = $(this).val();
            resetCascadeOutputs();

            if (!clientId) return;

            if (currentType === 'CONTRATO' || currentType === 'COBRO') {
                // Fetch Contracts
                Swal.showLoading();
                const res = await fetch(`/modificaciones-masivas/client/${clientId}/contracts`);
                const contracts = await res.json();
                Swal.close();

                const selectContract = $('#contract_select');
                selectContract.html('<option value="">Selecciona contrato...</option>');
                contracts.forEach(c => {
                    selectContract.append(`<option value="${c.id}">${c.text}</option>`);
                });
                selectContract.prop('disabled', false);
            } else if (currentType === 'APARTADO') {
                // Fetch Reservations
                Swal.showLoading();
                const res = await fetch(`/modificaciones-masivas/client/${clientId}/reservations`);
                loadedReservations = await res.json();
                Swal.close();

                renderReservationsTable();
            }
        });

        // Contract change -> load charges/contract details
        $('#contract_select').on('change', async function() {
            const contractId = $(this).val();
            $('#detailsContractCard').addClass('d-none');
            $('#tableChargesCard').addClass('d-none');

            if (!contractId) return;

            if (currentType === 'CONTRATO') {
                // Load contract details from the selected options or details
                Swal.showLoading();
                const res = await fetch(`{{ route('bulk-modifications.record-details') }}?type=CONTRATO&id=${contractId}`);
                const data = await res.json();
                Swal.close();

                if (data.ok) {
                    loadedContractDetails = data.data;
                    const contractText = $('#contract_select option:selected').text();
                    const parts = contractText.split(' - ');
                    
                    $('#lblContractRef').text(loadedContractDetails.numero_referencia);
                    $('#lblContractDate').text(loadedContractDetails.fecha_emision);
                    $('#lblContractLot').text(parts[1] || '');

                    $('#detailsContractCard').removeClass('d-none');
                }
            } else if (currentType === 'COBRO') {
                // Load Charges
                Swal.showLoading();
                const res = await fetch(`/modificaciones-masivas/contract/${contractId}/charges`);
                loadedCharges = await res.json();
                Swal.close();

                renderChargesTable();
            }
        });

        // Supplier change -> load boletas
        $('#supplier_select').on('change', async function() {
            const supplierId = $(this).val();
            resetCascadeOutputs();

            if (!supplierId) return;

            Swal.showLoading();
            const res = await fetch(`/modificaciones-masivas/supplier/${supplierId}/boletas`);
            const boletas = await res.json();
            Swal.close();

            const boletaSelect = $('#boleta_select');
            boletaSelect.html('<option value="">Selecciona boleta...</option>');
            boletas.forEach(b => {
                boletaSelect.append(`<option value="${b.id}">${b.text}</option>`);
            });
            boletaSelect.prop('disabled', false);
        });

        // Boleta change -> load details / partidas
        $('#boleta_select').on('change', async function() {
            const boletaId = $(this).val();
            $('#detailsBoletaCard').addClass('d-none');
            $('#tablePartidasCard').addClass('d-none');

            if (!boletaId) return;

            if (currentType === 'BOLETA_PROVEEDOR') {
                Swal.showLoading();
                const res = await fetch(`{{ route('bulk-modifications.record-details') }}?type=BOLETA_PROVEEDOR&id=${boletaId}`);
                const data = await res.json();
                Swal.close();

                if (data.ok) {
                    loadedContractDetails = data.data; // Reusing variable to store details temporarily
                    $('#lblBoletaRef').text(data.data.numero_referencia);
                    $('#lblBoletaLot').text(data.data.lotificacion_nombre || 'N/A');
                    $('#lblBoletaCosto').text('$' + parseFloat(data.data.importe).toFixed(2));
                    $('#lblBoletaEnganche').text('$' + parseFloat(data.data.enganche).toFixed(2));
                    $('#lblBoletaInicio').text(data.data.fecha_inicio);
                    $('#lblBoletaPlazo').text(data.data.plazo);

                    $('#detailsBoletaCard').removeClass('d-none');
                }
            } else if (currentType === 'PARTIDA_PROVEEDOR') {
                Swal.showLoading();
                const res = await fetch(`/modificaciones-masivas/boleta/${boletaId}/partidas`);
                loadedCharges = await res.json(); // Reuse variable
                Swal.close();

                renderPartidasTable();
            }
        });
    }

    function resetCascadeOutputs() {
        $('#contract_select').html('<option value="">Selecciona cliente primero...</option>').prop('disabled', true);
        $('#detailsContractCard').addClass('d-none');
        $('#tableChargesCard').addClass('d-none');
        $('#tableReservationsCard').addClass('d-none');
        loadedCharges = [];
        loadedReservations = [];
        loadedContractDetails = null;
    }

    function renderChargesTable() {
        const tbody = $('#tableContractCharges tbody');
        tbody.html('');

        if (loadedCharges.length === 0) {
            $('#chargesCountBadge').text('0');
            tbody.html('<tr><td colspan="7" class="text-center text-muted">No hay cobros registrados en este contrato.</td></tr>');
            $('#tableChargesCard').removeClass('d-none');
            return;
        }

        $('#chargesCountBadge').text(loadedCharges.length);

        loadedCharges.forEach(charge => {
            const importeFormated = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(charge.monto);
            const surchargeFormated = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(charge.monto_recargo || 0);
            
            const isQueued = selectedRecords.some(r => r.record_id === charge.id);

            let actionsHtml = `<button type="button" class="btn btn-sm btn-outline-primary btn-add-charge-modify" data-id="${charge.id}">
                                    <i class="fa-solid fa-pen"></i> Modificar
                               </button>
                               <button type="button" class="btn btn-sm btn-outline-danger btn-add-charge-delete" data-id="${charge.id}">
                                    <i class="fa-solid fa-trash"></i> Eliminar
                               </button>`;
            
            if (isQueued) {
                actionsHtml = `<span class="badge bg-secondary">En Lista</span>`;
            }

            tbody.append(`
                <tr>
                    <td class="fw-bold">${charge.numero_referencia}</td>
                    <td>${charge.fecha_emision || '-'}</td>
                    <td>${importeFormated}</td>
                    <td>${surchargeFormated}</td>
                    <td>${charge.forma_pago || '-'}</td>
                    <td><span class="badge bg-secondary">${charge.estado}</span></td>
                    <td>${actionsHtml}</td>
                </tr>
            `);
        });

        $('#tableChargesCard').removeClass('d-none');
    }

    function renderPartidasTable() {
        const tbody = $('#tableBoletaPartidas tbody');
        tbody.html('');

        if (loadedCharges.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center text-muted">No hay partidas en esta boleta.</td></tr>');
            $('#tablePartidasCard').removeClass('d-none');
            return;
        }

        loadedCharges.forEach(partida => {
            const isQueued = selectedRecords.some(r => r.record_id === partida.id);

            let actionsHtml = `<button type="button" class="btn btn-sm btn-outline-primary btn-add-partida-modify" data-id="${partida.id}">
                                    <i class="fa-solid fa-pen"></i> Modificar
                               </button>`;
            
            if (isQueued) {
                actionsHtml = `<span class="badge bg-secondary">En Lista</span>`;
            }

            tbody.append(`
                <tr>
                    <td class="fw-bold">${partida.id}</td>
                    <td>${partida.fecha}</td>
                    <td>$${parseFloat(partida.importe).toFixed(2)}</td>
                    <td>${partida.concepto}</td>
                    <td>${actionsHtml}</td>
                </tr>
            `);
        });

        $('#tablePartidasCard').removeClass('d-none');
    }

    function renderReservationsTable() {
        const tbody = $('#tableClientReservations tbody');
        tbody.html('');

        if (loadedReservations.length === 0) {
            tbody.html('<tr><td colspan="7" class="text-center text-muted">No hay apartados registrados para este cliente.</td></tr>');
            $('#tableReservationsCard').removeClass('d-none');
            return;
        }

        loadedReservations.forEach(r => {
            const tr = $('<tr>');
            // Check association to contract to apply coloring to row
            if (r.has_contract) {
                tr.addClass('bg-light-success');
            } else {
                tr.addClass('bg-light-warning');
            }

            tr.append(`<td><strong>${r.numero_referencia}</strong></td>`);
            tr.append(`<td>${r.fecha_emision}</td>`);
            tr.append(`<td>${r.fecha_vencimiento}</td>`);
            tr.append(`<td>$${parseFloat(r.importe_apartado).toFixed(2)}</td>`);
            tr.append(`
                <td>
                    ${r.has_contract 
                        ? '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Con Contrato</span>' 
                        : '<span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Sin Contrato</span>'}
                </td>
            `);
            tr.append(`<td><span class="badge bg-secondary">${r.estado}</span></td>`);
            tr.append(`
                <td>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-res-modify" data-id="${r.id}">
                            <i class="fa-solid fa-pen me-1"></i> Modificar
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-add-res-delete" data-id="${r.id}">
                            <i class="fa-solid fa-trash me-1"></i> Eliminar
                        </button>
                    </div>
                </td>
            `);
            tbody.append(tr);
        });

        $('#tableReservationsCard').removeClass('d-none');
    }

    function renderSelectedRecordsTable() {
        const tbody = $('#bodySelectedRecords');
        tbody.html('');

        $('#selectedRecordsCountBadge').text(selectedRecords.length);

        if (selectedRecords.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="20" class="text-center text-muted py-4">No has agregado ningún registro a la lista.</td>
                </tr>
            `);
            return;
        }

        // Render rows
        selectedRecords.forEach((record, index) => {
            const tr = $('<tr>');
            
            // Col 1: Folio / Reference
            tr.append(`<td><strong>${record.numero_referencia}</strong><div class="small text-muted">${record.cliente || ''}</div></td>`);
            
            // Col 2: Action Badge
            const actionBadge = record.action === 'ELIMINAR' 
                ? '<span class="badge bg-danger">ELIMINAR</span>' 
                : '<span class="badge bg-primary">MODIFICAR</span>';
            tr.append(`<td>${actionBadge}</td>`);

            if (record.action === 'ELIMINAR') {
                // If deletion request, display info message instead of fields
                const fieldsCount = fieldsConfig[currentType].headers.length - 3;
                tr.append(`<td colspan="${fieldsCount}" class="text-danger fw-bold py-3"><i class="fa-solid fa-triangle-exclamation me-2"></i>Se solicitará la eliminación completa de este registro y todas sus dependencias.</td>`);
            } else {
                // Editable inputs
                const fields = fieldsConfig[currentType].fields;
                fields.forEach(f => {
                    const value = record.new_data[f.name] !== undefined ? record.new_data[f.name] : (record.original_data[f.name] || '');
                    let inputHtml = '';
                    
                    if (f.type === 'date') {
                        const dateVal = value ? value.substring(0, 10) : '';
                        inputHtml = `<input type="date" class="form-control form-control-sm field-input" data-index="${index}" data-field="${f.name}" value="${dateVal}">`;
                    } else if (f.type === 'number') {
                        inputHtml = `<input type="number" step="0.01" class="form-control form-control-sm field-input text-end" data-index="${index}" data-field="${f.name}" value="${value}">`;
                    } else if (f.type === 'integer') {
                        inputHtml = `<input type="number" class="form-control form-control-sm field-input text-end" data-index="${index}" data-field="${f.name}" value="${value}">`;
                    } else if (f.type === 'select') {
                        const options = catalogsOptions[f.optionKey] || [];
                        inputHtml = `<select class="form-select form-select-sm field-input" data-index="${index}" data-field="${f.name}">
                            <option value="">Seleccione...</option>
                            ${options.map(opt => `<option value="${opt.value}" ${String(opt.value) === String(value) ? 'selected' : ''}>${opt.text}</option>`).join('')}
                        </select>`;
                    }

                    tr.append($('<td>').html(inputHtml));
                });
            }

            // Quit button
            tr.append(`
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger btn-remove-selected" data-index="${index}" type="button">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </td>
            `);

            tbody.append(tr);
        });
    }

    function renderSelectionHeaders() {
        const headers = fieldsConfig[currentType].headers;
        const headRow = $('#headersSelectedRecords');
        headRow.html('');
        headers.forEach(h => headRow.append(`<th>${h}</th>`));
    }

    // --- Add helpers to Queue array ---

    function pushRecordToQueue(id, reference, clientName, originalData, action, initialNewData = {}) {
        if (selectedRecords.some(r => String(r.id) === String(id))) {
            Swal.fire('Aviso', 'Este registro ya está en tu lista de solicitud.', 'warning');
            return;
        }

        const record = {
            id: id,
            numero_referencia: reference,
            cliente: clientName,
            action: action,
            original_data: {...originalData},
            new_data: {...initialNewData}
        };

        if (action === 'MODIFICAR') {
            const fields = fieldsConfig[currentType].fields;
            fields.forEach(f => {
                let val = originalData[f.name];
                if (f.type === 'date' && val) {
                    val = val.substring(0, 10);
                }
                if (record.new_data[f.name] === undefined) {
                    record.new_data[f.name] = val;
                }
            });
        }

        selectedRecords.push(record);
        renderSelectedRecordsTable();
    }

    // Submit Request to backend
    async function submitModificationRequest() {
        if (selectedRecords.length === 0) {
            Swal.fire('Aviso', 'Agrega al menos un registro a la solicitud.', 'warning');
            return;
        }

        const justification = $('#justification').val().trim();
        if (justification.length < 5) {
            Swal.fire('Aviso', 'El campo justificación es obligatorio y debe tener al menos 5 caracteres.', 'warning');
            return;
        }

        const payload = {
            type: currentType,
            justification: justification,
            items: selectedRecords.map(r => ({
                record_id: r.id,
                action: r.action,
                new_data: r.action === 'ELIMINAR' ? {} : r.new_data
            }))
        };

        try {
            Swal.showLoading();
            const res = await fetch('{{ route('bulk-modifications.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const json = await res.json();
            Swal.close();

            if (!res.ok || !json.ok) {
                throw new Error(json.message || 'Error al enviar solicitud.');
            }

            Swal.fire('Éxito', json.message, 'success');
            
            // Clean up
            selectedRecords = [];
            $('#justification').val('');
            renderSelectedRecordsTable();
            tableRequests.ajax.reload();
            
            // Reset Selects
            $('#client_select').val(null).trigger('change');
            
            // Go back to requests list tab
            const triggerEl = document.querySelector('#requests-tab');
            bootstrap.Tab.getInstance(triggerEl).show();

        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function showRequestDetail(id) {
        try {
            Swal.showLoading();
            const res = await fetch(`/modificaciones-masivas/${id}`);
            const json = await res.json();
            Swal.close();

            if (!res.ok || !json.ok) {
                throw new Error(json.message || 'Error al cargar detalles de la solicitud.');
            }

            const request = json.request;
            const items = json.items;

            $('#detailRequester').text(request.requested_by_alias);
            $('#detailType').text(request.type);
            $('#detailDate').text(request.created_at.substring(0, 19).replace('T', ' '));
            
            let statusBadge = `<span class="badge-status-req ${request.status}">${request.status}</span>`;
            $('#detailStatus').html(statusBadge);
            $('#detailJustification').text(request.justification);

            if (request.status === 'RECHAZADO' && request.rejection_reason) {
                $('#detailRejectionRow').removeClass('d-none');
                $('#detailRejectionReason').text(request.rejection_reason);
            } else {
                $('#detailRejectionRow').addClass('d-none');
            }

            // Fill comparison table
            const tbody = $('#detailComparisonBody');
            tbody.html('');

            const fields = fieldsConfig[request.type].fields;

            items.forEach(item => {
                const action = item.action ?? 'MODIFICAR';
                
                if (action === 'ELIMINAR') {
                    tbody.append(`
                        <tr class="table-danger">
                            <td><strong>${item.reference}</strong></td>
                            <td><span class="badge bg-danger">ELIMINAR</span></td>
                            <td colspan="3" class="text-danger fw-bold py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i>Solicitud de eliminación de este registro y todas sus dependencias.</td>
                        </tr>
                    `);
                } else {
                    // MODIFICAR
                    const fieldDiffs = [];
                    fields.forEach(f => {
                        const orig = item.original_data[f.name];
                        const val = item.new_data[f.name];

                        if (String(orig) !== String(val)) {
                            fieldDiffs.push({
                                fieldLabel: f.label,
                                optionKey: f.optionKey,
                                original: orig,
                                modified: val
                            });
                        }
                    });

                    if (fieldDiffs.length === 0) {
                        tbody.append(`
                            <tr>
                                <td><strong>${item.reference}</strong></td>
                                <td><span class="badge bg-primary">MODIFICAR</span></td>
                                <td colspan="3" class="text-center text-muted small">Sin cambios en los campos.</td>
                            </tr>
                        `);
                    } else {
                        fieldDiffs.forEach((diff, dIndex) => {
                            const tr = $('<tr>');
                            
                            if (dIndex === 0) {
                                tr.append(`<td rowspan="${fieldDiffs.length}"><strong>${item.reference}</strong></td>`);
                                tr.append(`<td rowspan="${fieldDiffs.length}"><span class="badge bg-primary">MODIFICAR</span></td>`);
                            }

                            tr.append(`<td>${diff.fieldLabel}</td>`);

                            let origText = diff.original;
                            let modText = diff.modified;

                            if (diff.optionKey && catalogsOptions[diff.optionKey]) {
                                const origOpt = catalogsOptions[diff.optionKey].find(o => String(o.value) === String(diff.original));
                                const modOpt = catalogsOptions[diff.optionKey].find(o => String(o.value) === String(diff.modified));
                                if (origOpt) origText = origOpt.text;
                                if (modOpt) modText = modOpt.text;
                            }

                            tr.append(`<td class="text-center"><span class="diff-removed">${origText ?? 'N/A'}</span></td>`);
                            tr.append(`<td class="text-center"><span class="diff-added">${modText ?? 'N/A'}</span></td>`);

                            tbody.append(tr);
                        });
                    }
                }
            });

            // Footer buttons
            const footer = $('#modalRequestFooter');
            footer.html('');
            footer.append(`<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>`);

            // Only show authorizer action buttons if user is authorizer and request is PENDIENTE
            @php
                $userId = session('auth_user.id');
                $isAuthorizer = DB::table('authorizer_users')->where('user_id', $userId)->exists();
            @endphp
            @if($isAuthorizer)
            if (request.status === 'PENDIENTE') {
                footer.append(`
                    <button type="button" class="btn btn-danger btn-action-reject" data-id="${request.id}">
                        <i class="fa-solid fa-circle-xmark me-1"></i> Rechazar
                    </button>
                    <button type="button" class="btn btn-success btn-action-approve" data-id="${request.id}">
                        <i class="fa-solid fa-circle-check me-1"></i> Autorizar y Aplicar
                    </button>
                `);
            }
            @endif

            modalDetail.show();
        } catch (error) {
            Swal.fire('Error', error.message, 'error');
        }
    }

    async function approveRequest(id) {
        Swal.fire({
            title: '¿Confirmar autorización?',
            text: 'Las modificaciones y/o eliminaciones se aplicarán directamente en la base de datos de forma automática.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, autorizar',
            cancelButtonText: 'Cancelar'
        }).then(async result => {
            if (result.isConfirmed) {
                try {
                    Swal.showLoading();
                    const res = await fetch(`/modificaciones-masivas/${id}/approve`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    const json = await res.json();
                    Swal.close();

                    if (!res.ok || !json.ok) {
                        throw new Error(json.message || 'Error al aprobar solicitud.');
                    }

                    Swal.fire('Éxito', json.message, 'success');
                    modalDetail.hide();
                    tableRequests.ajax.reload();
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }
        });
    }

    async function rejectRequest(id) {
        Swal.fire({
            title: 'Rechazar solicitud',
            text: 'Ingresa la justificación o motivo del rechazo:',
            input: 'textarea',
            inputPlaceholder: 'Escribe el motivo del rechazo aquí...',
            inputAttributes: {
                'class': 'text-uppercase form-control'
            },
            showCancelButton: true,
            confirmButtonText: 'Rechazar',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value || value.trim().length < 3) {
                    return 'El motivo de rechazo es obligatorio (mínimo 3 caracteres)';
                }
            }
        }).then(async result => {
            if (result.isConfirmed) {
                try {
                    Swal.showLoading();
                    const res = await fetch(`/modificaciones-masivas/${id}/reject`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            rejection_reason: result.value.trim().toUpperCase()
                        })
                    });

                    const json = await res.json();
                    Swal.close();

                    if (!res.ok || !json.ok) {
                        throw new Error(json.message || 'Error al rechazar solicitud.');
                    }

                    Swal.fire('Éxito', json.message, 'success');
                    modalDetail.hide();
                    tableRequests.ajax.reload();
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            }
        });
    }

    // --- Listeners for UI Events ---

    // Select type tile
    // Select type tile
    $('.type-tile').on('click', function () {
        const tile = $(this);
        if (tile.hasClass('active')) return;

        $('.type-tile').removeClass('active');
        tile.addClass('active');

        currentType = tile.data('type');
        selectedRecords = [];

        // Reset inputs
        $('#client_select').val(null).trigger('change');
        $('#supplier_select').val(null).trigger('change');
        resetCascadeOutputs();
        renderSelectionHeaders();
        renderSelectedRecordsTable();

        // Toggle cascades
        if (currentType === 'BOLETA_PROVEEDOR' || currentType === 'PARTIDA_PROVEEDOR') {
            $('#cascade_clients').addClass('d-none');
            $('#cascade_suppliers').removeClass('d-none');
        } else {
            $('#cascade_suppliers').addClass('d-none');
            $('#cascade_clients').removeClass('d-none');
        }
    });

    // Remove item from selected queue
    $(document).on('click', '.btn-remove-selected', function () {
        const index = $(this).data('index');
        selectedRecords.splice(index, 1);
        renderSelectedRecordsTable();
    });

    // Edit input inline change
    $(document).on('change input', '.field-input', function () {
        const el = $(this);
        const index = el.data('index');
        const fieldName = el.data('field');
        let val = el.val();

        if (el.hasClass('text-uppercase')) {
            val = val.toUpperCase();
        }

        selectedRecords[index].new_data[fieldName] = val;
    });

    // Add contract to queue for modification
    $('#btnEditContract').on('click', function() {
        if (!loadedContractDetails) return;
        const clientText = $('#client_select option:selected').text();
        pushRecordToQueue(
            loadedContractDetails.id,
            loadedContractDetails.numero_referencia,
            clientText,
            loadedContractDetails,
            'MODIFICAR'
        );
    });

    // Add contract to queue for deletion
    $('#btnDeleteContract').on('click', function() {
        if (!loadedContractDetails) return;
        const clientText = $('#client_select option:selected').text();
        pushRecordToQueue(
            loadedContractDetails.id,
            loadedContractDetails.numero_referencia,
            clientText,
            loadedContractDetails,
            'ELIMINAR'
        );
    });

    // Add charge to queue for modification
    $(document).on('click', '.btn-add-charge-modify', function() {
        const chargeId = $(this).data('id');
        const charge = loadedCharges.find(c => String(c.id) === String(chargeId));
        if (!charge) return;
        const clientText = $('#client_select option:selected').text();
        pushRecordToQueue(charge.id, charge.numero_referencia, clientText, charge, 'MODIFICAR');
    });

        // === EVENTOS TABLA COBROS === //
        $('#btnSelectAllCharges').on('click', function() {
            if (loadedCharges.length === 0) return;
            let added = 0;
            loadedCharges.forEach(charge => {
                if (!selectedRecords.some(r => String(r.id) === String(charge.id))) {
                    pushRecordToQueue(
                        charge.id,
                        charge.numero_referencia || charge.folio,
                        loadedContractDetails ? (loadedContractDetails.cliente || '') : '',
                        charge,
                        'MODIFICAR'
                    );
                    added++;
                }
            });
            if (added > 0) {
                renderChargesTable();
                Swal.fire('Éxito', added + ' cobros agregados a la lista.', 'success');
            } else {
                Swal.fire('Aviso', 'Todos los cobros ya estaban en la lista.', 'info');
            }
        });

    // Add charge to queue for deletion
    $(document).on('click', '.btn-add-charge-delete', function() {
        const chargeId = $(this).data('id');
        const charge = loadedCharges.find(c => String(c.id) === String(chargeId));
        if (!charge) return;
        const clientText = $('#client_select option:selected').text();
        pushRecordToQueue(charge.id, charge.numero_referencia, clientText, charge, 'ELIMINAR');
    });

    // Add reservation to queue for modification
    $(document).on('click', '.btn-add-res-modify', function() {
        const resId = $(this).data('id');
        const reservation = loadedReservations.find(r => String(r.id) === String(resId));
        if (!reservation) return;
        const clientText = $('#client_select option:selected').text();
        pushRecordToQueue(reservation.id, reservation.numero_referencia, clientText, reservation, 'MODIFICAR');
    });

    // Add reservation to queue for deletion
    $(document).on('click', '.btn-add-res-delete', function() {
        const resId = $(this).data('id');
        const reservation = loadedReservations.find(r => String(r.id) === String(resId));
        if (!reservation) return;
        const clientText = $('#client_select option:selected').text();
        pushRecordToQueue(reservation.id, reservation.numero_referencia, clientText, reservation, 'ELIMINAR');
    });

    // Add boleta to queue for modification
    $('#btnEditBoleta').on('click', function() {
        if (!loadedContractDetails) return;
        const suppText = $('#supplier_select option:selected').text();
        pushRecordToQueue(
            loadedContractDetails.id,
            loadedContractDetails.numero_referencia,
            suppText,
            loadedContractDetails,
            'MODIFICAR'
        );
    });

    // Add partida to queue for modification
    $(document).on('click', '.btn-add-partida-modify', function() {
        const pId = $(this).data('id');
        const p = loadedCharges.find(c => String(c.id) === String(pId));
        if (!p) return;
        const suppText = $('#supplier_select option:selected').text();
        pushRecordToQueue(p.id, 'Partida ' + p.id, suppText, p, 'MODIFICAR');
    });

    // Show request detail modal
    $(document).on('click', '.btn-view-request', function () {
        const id = $(this).data('id');
        showRequestDetail(id);
    });

    // Submit request
    $('#btnSubmitRequest').on('click', submitModificationRequest);

    // Modal action approve
    $(document).on('click', '.btn-action-approve', function () {
        approveRequest($(this).data('id'));
    });

    // Modal action reject
    $(document).on('click', '.btn-action-reject', function () {
        rejectRequest($(this).data('id'));
    });

    // Run setups
    (async () => {
        initDataTables();
        await loadOptions();
        initCascades();
        renderSelectionHeaders();
        modalDetail = new bootstrap.Modal(document.getElementById('modalRequestDetail'));
    })();
})();
</script>
@endpush
