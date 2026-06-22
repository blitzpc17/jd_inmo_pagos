@extends('layouts.app')

@section('content')
<style>
    #modalContrato .modal-dialog,
    #modalDetalleContrato .modal-dialog,
    #modalContractDocumentData .modal-dialog {
        max-width: 1200px;
    }

    #modalContrato .modal-content,
    #modalDetalleContrato .modal-content,
    #modalContractDocumentData .modal-content {
        max-height: 92vh;
        border-radius: 1rem;
        overflow: hidden;
    }

    #modalContrato .modal-body,
    #modalDetalleContrato .modal-body,
    #modalContractDocumentData .modal-body {
        overflow-y: auto;
        max-height: calc(92vh - 140px);
    }

    #modalContrato .modal-footer,
    #modalContractDocumentData .modal-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        z-index: 5;
        border-top: 1px solid #dee2e6;
    }

    .resume-box {
        border: 1px solid #dbe3ea;
        border-radius: 1rem;
        padding: 1rem;
        background: #fff;
    }

    .modal .form-control[readonly],
    .modal textarea[readonly] {
        background-color: #f8f9fa;
    }

    .btn-actions {
        display: inline-flex;
        gap: .25rem;
        flex-wrap: nowrap;
    }

    @media (max-width: 768px) {
        #modalContrato .modal-dialog,
        #modalDetalleContrato .modal-dialog,
        #modalContractDocumentData .modal-dialog {
            max-width: 96%;
            margin: .75rem auto;
        }
    }
</style>

<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Contratos</h3>
            <div class="text-muted">Formalización de venta contado o crédito</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoContrato">
            <i class="fa-solid fa-plus me-1"></i> Nuevo contrato
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblContratos">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Referencia</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Lotificación</th>
                    <th>Tipo pago</th>
                    <th>Tipo propiedad</th>
                    <th>Importe</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- MODAL NUEVO CONTRATO --}}
<div class="modal fade" id="modalContrato" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formContrato">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Nuevo contrato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha de emisión</label>
                            <input type="date" class="form-control" id="fecha_emision" name="fecha_emision" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Cliente</label>
                            <select class="form-select select2-contrato" id="client_id" name="client_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Apartado origen (opcional)</label>
                            <select class="form-select select2-contrato" id="reservation_id" name="reservation_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Lotificación</label>
                            <select class="form-select select2-contrato" id="development_id" name="development_id"></select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Lotes</label>
                            <select class="form-select select2-contrato" id="lot_ids" name="lot_ids[]" multiple></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tipo de propiedad</label>
                            <select class="form-select select2-contrato" id="contract_property_type" name="contract_property_type"></select>
                            <div class="form-text">Define si la plantilla será EJIDO o PROPIEDAD.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tipo de pago</label>
                            <select class="form-select select2-contrato" id="contract_payment_type_id" name="contract_payment_type_id"></select>
                            <div class="form-text">Define si la plantilla será CONTADO o CRÉDITO.</div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check bg-light border rounded p-3 mb-2">
                                <input class="form-check-input ms-1" type="checkbox" id="is_migration" name="is_migration" value="1">
                                <label class="form-check-label fw-bold ms-2" for="is_migration">
                                    <i class="fa-solid fa-clock-rotate-left text-primary me-1"></i>
                                    Es contrato de migración (histórico)
                                </label>
                                <div class="form-text ms-2 mt-1">
                                    Permite asignar fechas antiguas sin que el sistema cancele el contrato por morosidad, y habilita la opción de exentar recargos al registrar los cobros atrasados. La bandera se retirará automáticamente cuando el contrato se ponga al corriente.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Oficina</label>
                            <select class="form-select select2-contrato" id="office_id" name="office_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Vendedor</label>
                            <select class="form-select select2-contrato" id="seller_id" name="seller_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Comisión vendedor</label>
                            <input type="number" step="0.01" class="form-control" id="commission_amount" name="commission_amount">
                        </div>

                        <div class="col-md-3 contract-credit-only">
                            <label class="form-label">Meses</label>
                            <input type="number" min="0" class="form-control" id="meses" name="meses">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Importe total</label>
                            <input type="number" step="0.01" class="form-control" id="importe" name="importe" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Pago inicial</label>
                            <input type="number" step="0.01" class="form-control" id="monto_pago_inicial" name="monto_pago_inicial">
                        </div>

                        <div class="col-md-3 contract-credit-only">
                            <label class="form-label">Saldo financiado</label>
                            <input type="number" step="0.01" class="form-control" id="saldo_financiado" name="saldo_financiado" readonly>
                        </div>

                        <div class="col-md-3 contract-credit-only">
                            <label class="form-label">Día pago</label>
                            <input type="number" min="1" max="31" class="form-control" id="dia_pago" name="dia_pago">
                        </div>

                        <div class="col-md-3 contract-credit-only">
                            <label class="form-label">Cuota mensual</label>
                            <input type="number" step="0.01" class="form-control" id="cuota_mensual" name="cuota_mensual" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Forma pago diferencia</label>
                            <select class="form-select select2-contrato" id="difference_payment_method_id" name="difference_payment_method_id"></select>
                            <div class="form-text">Solo se usa si el pago inicial supera los pagos previos del apartado.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control text-uppercase" id="observaciones" name="observaciones" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="resume-box">
                        <h6 class="fw-bold mb-3">Resumen</h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="r_cliente" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Lotificación</label>
                                <input type="text" class="form-control" id="r_lotificacion" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Apartado</label>
                                <input type="text" class="form-control" id="r_apartado" readonly>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Pagos previos total</label>
                                <input type="text" class="form-control" id="r_pagos_previos" readonly>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Identificador</th>
                                        <th>Manzana</th>
                                        <th>Contado</th>
                                        <th>Crédito</th>
                                    </tr>
                                </thead>
                                <tbody id="contractLotsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-between">
                    <div class="small text-muted">
                        Si el contrato nace de un apartado y el pago inicial supera los pagos previos, se generará un complemento automático por la diferencia.
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" type="submit" id="btnGuardarContrato">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL DETALLE CONTRATO --}}
<div class="modal fade" id="modalDetalleContrato" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de contrato</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info d-none" id="detalle_edit_alert">
                    Este contrato está en estado VIGENTE, puedes modificar el tipo de propiedad y las observaciones.
                </div>

                <div class="alert alert-warning d-none" id="detalle_readonly_alert">
                    Este contrato ya no permite modificaciones. La información se muestra solo para consulta.
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Referencia</label>
                        <input type="text" class="form-control" id="dc_referencia" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Fecha</label>
                        <input type="text" class="form-control" id="dc_fecha" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" id="dc_cliente" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Lotificación</label>
                        <input type="text" class="form-control" id="dc_lotificacion" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo pago</label>
                        <input type="text" class="form-control" id="dc_tipo" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo propiedad</label>
                        <select class="form-select" id="dc_contract_property_type" disabled></select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <input type="text" class="form-control" id="dc_estado" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Oficina</label>
                        <input type="text" class="form-control" id="dc_oficina" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Vendedor</label>
                        <input type="text" class="form-control" id="dc_vendedor" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Comisión</label>
                        <input type="text" class="form-control" id="dc_comision" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Meses</label>
                        <input type="text" class="form-control" id="dc_meses" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Importe</label>
                        <input type="text" class="form-control" id="dc_importe" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Pago inicial</label>
                        <input type="text" class="form-control" id="dc_pago_inicial" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Saldo financiado</label>
                        <input type="text" class="form-control" id="dc_saldo" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Día pago</label>
                        <input type="text" class="form-control" id="dc_dia_pago" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cuota mensual</label>
                        <input type="text" class="form-control" id="dc_cuota" readonly>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control text-uppercase" id="dc_observaciones" rows="3" readonly></textarea>
                    </div>
                </div>

                <div class="resume-box">
                    <h6 class="fw-bold mb-3">Lotes del contrato</h6>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Identificador</th>
                                    <th>Manzana</th>
                                    <th>Precio venta</th>
                                    <th>Descuento</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="detalleContratoLotesBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary d-none" type="button" id="btnGuardarDetalleContrato">
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Guardar modificación
                </button>

                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DATOS COMPLEMENTARIOS CONTRATO --}}
<div class="modal fade" id="modalContractDocumentData" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form class="modal-content" id="formContractDocumentData">
            @csrf

            <input type="hidden" id="doc_contract_id">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-clipboard-list me-2"></i>
                    Complemento del contrato
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    Estos datos se guardan solamente en el contrato y se usan para generar la plantilla PDF.
                    No modifican el catálogo de lotes.
                </div>

                <div class="alert alert-warning d-none" id="doc_readonly_alert">
                    Este contrato ya no permite modificaciones. La información se muestra solo para consulta.
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Ciudad de firma</label>
                        <input type="text" class="form-control text-uppercase" name="ciudad_firma" id="doc_ciudad_firma">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Ubicación del terreno</label>
                        <input type="text" class="form-control text-uppercase" name="ubicacion_terreno" id="doc_ubicacion_terreno">
                    </div>

                    <div class="col-12">
                        <hr>
                        <h6 class="fw-bold mb-0">Vendedor para contrato PDF</h6>
                        <div class="text-muted small">
                            Este nombre se imprime en el PDF del contrato y es independiente del vendedor operativo/comisión.
                        </div>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Vendedor en contrato</label>

                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="doc_vendedor_personalizado" name="vendedor_personalizado" value="1">
                            <label class="form-check-label" for="doc_vendedor_personalizado">
                                Usar otro nombre de vendedor en el PDF
                            </label>
                        </div>

                        <input type="text"
                               class="form-control text-uppercase bg-light"
                               name="vendedor_contrato"
                               id="doc_vendedor_contrato"
                               value="DANY FRANK PABLO FLORES"
                               readonly>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label">Dirección del comprador</label>

                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="doc_usar_direccion_sistema" name="usar_direccion_sistema" value="1">
                            <label class="form-check-label" for="doc_usar_direccion_sistema">
                                Usar la dirección del sistema
                            </label>
                        </div>

                        <input type="text"
                               class="form-control text-uppercase"
                               name="direccion_comprador"
                               id="doc_direccion_comprador">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Teléfono del comprador</label>

                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="doc_usar_telefono_sistema" name="usar_telefono_sistema" value="1">
                            <label class="form-check-label" for="doc_usar_telefono_sistema">
                                Usar el teléfono del sistema
                            </label>
                        </div>

                        <input type="text"
                               class="form-control text-uppercase"
                               name="telefono_comprador"
                               id="doc_telefono_comprador">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Número de lote en contrato</label>
                        <input type="text"
                               class="form-control text-uppercase bg-light"
                               name="lote_numero"
                               id="doc_lote_numero"
                               readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Número de manzana</label>
                        <input type="text"
                               class="form-control text-uppercase bg-light"
                               name="manzana_numero"
                               id="doc_manzana_numero"
                               readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Área m²</label>
                        <input type="text" class="form-control text-uppercase" name="area_m2" id="doc_area_m2">
                    </div>

                    <div class="col-12">
                        <hr>
                        <h6 class="fw-bold mb-0">Medidas y colindancias</h6>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Norte mide</label>
                        <input type="text" class="form-control text-uppercase" name="norte_medida" id="doc_norte_medida">
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Norte colinda con</label>
                        <input type="text" class="form-control text-uppercase" name="norte_colindancia" id="doc_norte_colindancia">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Sur mide</label>
                        <input type="text" class="form-control text-uppercase" name="sur_medida" id="doc_sur_medida">
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Sur colinda con</label>
                        <input type="text" class="form-control text-uppercase" name="sur_colindancia" id="doc_sur_colindancia">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Oriente mide</label>
                        <input type="text" class="form-control text-uppercase" name="oriente_medida" id="doc_oriente_medida">
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Oriente colinda con</label>
                        <input type="text" class="form-control text-uppercase" name="oriente_colindancia" id="doc_oriente_colindancia">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Poniente mide</label>
                        <input type="text" class="form-control text-uppercase" name="poniente_medida" id="doc_poniente_medida">
                    </div>

                    <div class="col-md-9">
                        <label class="form-label">Poniente colinda con</label>
                        <input type="text" class="form-control text-uppercase" name="poniente_colindancia" id="doc_poniente_colindancia">
                    </div>

                    <div class="col-12">
                        <hr>
                        <h6 class="fw-bold mb-0">Firmas</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Testigo 1</label>
                        <input type="text" class="form-control text-uppercase" name="testigo_1" id="doc_testigo_1">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Testigo 2</label>
                        <input type="text" class="form-control text-uppercase" name="testigo_2" id="doc_testigo_2">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observaciones del contrato</label>
                        <textarea class="form-control text-uppercase bg-light"
                                  name="observaciones_documento"
                                  id="doc_observaciones_documento"
                                  rows="3"
                                  readonly></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk me-1"></i>
                    Guardar complemento
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalContrato'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleContrato'));
    const modalDocumentData = new bootstrap.Modal(document.getElementById('modalContractDocumentData'));
    const form = document.getElementById('formContrato');

    let table = null;
    let optionsCache = null;
    let reservationContext = null;
    let directLotsCache = [];
    let suppressClientReload = false;
    let suppressReservationReload = false;

    let documentSystemData = {
        direccion_comprador: '',
        telefono_comprador: ''
    };

    let selectedDetailContractId = null;
    let detailCanEdit = false;

    function initSelect2() {
        $('.select2-contrato').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalContrato')
        });
    }

    function fillSelect(id, items, multiple = false) {
        const el = document.getElementById(id);
        if (!el) return;

        el.innerHTML = multiple ? '' : '<option value="">Seleccione...</option>';

        (items || []).forEach(item => {
            let extra = '';

            if (item.precio_contado !== undefined) {
                extra = `
                    data-contado="${item.precio_contado}"
                    data-credito="${item.precio_credito}"
                    data-manzana="${item.manzana ?? ''}"
                `;
            }

            el.innerHTML += `<option value="${item.value}" ${extra}>${item.text}</option>`;
        });

        $(el).trigger('change.select2');
    }

    function resetSummary() {
        $('#r_cliente').val('');
        $('#r_lotificacion').val('');
        $('#r_apartado').val('');
        $('#r_pagos_previos').val('');
        $('#contractLotsBody').html('');
    }

    function resetForm() {
        form.reset();

        $('.select2-contrato').val(null).trigger('change');

        reservationContext = null;
        directLotsCache = [];
        suppressClientReload = false;
        suppressReservationReload = false;

        fillSelect('reservation_id', []);
        fillSelect('development_id', []);
        fillSelect('lot_ids', [], true);
        fillSelect('office_id', []);
        fillSelect('difference_payment_method_id', []);

        resetSummary();

        document.getElementById('monto_pago_inicial').dataset.userEdited = '';

        toggleContractFields();
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch('/contratos/options', {
            headers: {
                'Accept': 'application/json'
            }
        });

        optionsCache = await res.json();

        fillSelect('client_id', optionsCache.clients);
        fillSelect('reservation_id', []);
        fillSelect('development_id', []);
        fillSelect('lot_ids', [], true);
        fillSelect('contract_property_type', optionsCache.contract_property_types || []);
        fillSelect('contract_payment_type_id', optionsCache.contract_payment_types);
        fillSelect('office_id', []);
        fillSelect('seller_id', optionsCache.sellers);
        fillSelect('difference_payment_method_id', []);

        return optionsCache;
    }

    async function loadClientData(clientId, preserveReservation = false) {
        fillSelect('development_id', []);
        fillSelect('lot_ids', [], true);
        fillSelect('office_id', []);
        fillSelect('difference_payment_method_id', []);

        directLotsCache = [];
        reservationContext = null;

        if (!clientId) {
            fillSelect('reservation_id', []);
            resetSummary();
            recalculateContract();
            return;
        }

        const currentReservation = preserveReservation ? $('#reservation_id').val() : null;

        const [reservationsRes, developmentsRes] = await Promise.all([
            fetch(`/contratos/client/${clientId}/reservations`, { headers: { 'Accept': 'application/json' } }),
            fetch(`/contratos/client/${clientId}/developments`, { headers: { 'Accept': 'application/json' } })
        ]);

        const reservations = await reservationsRes.json();
        const developments = await developmentsRes.json();

        fillSelect('reservation_id', reservations);
        fillSelect('development_id', developments);

        if (currentReservation && reservations.some(x => String(x.value) === String(currentReservation))) {
            suppressReservationReload = true;
            $('#reservation_id').val(currentReservation).trigger('change');
        }

        $('#r_cliente').val($('#client_id option:selected').text() || '');
    }

    async function loadDevelopmentOffices(developmentId) {
        fillSelect('office_id', []);
        fillSelect('difference_payment_method_id', []);

        if (!developmentId) return;

        const res = await fetch(`/contratos/development/${developmentId}/offices`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const offices = await res.json();
        fillSelect('office_id', offices);
    }

    async function loadOfficePaymentMethods(officeId) {
        fillSelect('difference_payment_method_id', []);

        if (!officeId) return;

        const res = await fetch(`/contratos/office/${officeId}/payment-methods`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const methods = await res.json();
        fillSelect('difference_payment_method_id', methods);
    }

    async function loadReservationData(reservationId) {
        reservationContext = null;

        fillSelect('lot_ids', [], true);
        $('#contractLotsBody').html('');

        if (!reservationId) {
            $('#r_apartado').val('');
            $('#r_lotificacion').val($('#development_id option:selected').text() || '');
            $('#r_pagos_previos').val('');
            recalculateContract();
            return;
        }

        const res = await fetch(`/contratos/reservation/${reservationId}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        if (!res.ok) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: json.message || 'No se pudo cargar el apartado'
            });
            return;
        }

        reservationContext = json.data;

        suppressClientReload = true;
        $('#client_id').val(String(json.data.client_id)).trigger('change');

        suppressReservationReload = true;
        $('#development_id').val(String(json.data.development_id)).trigger('change');

        await loadDevelopmentOffices(json.data.development_id);

        $('#r_cliente').val(json.data.client_name || '');
        $('#r_lotificacion').val(json.data.development_name || '');
        $('#r_apartado').val(json.data.numero_referencia || '');
        $('#r_pagos_previos').val(json.data.pagos_previos_total || 0);

        fillSelect('lot_ids', (json.data.lots || []).map(lot => ({
            value: lot.id,
            text: lot.identificador,
            precio_contado: lot.precio_contado,
            precio_credito: lot.precio_credito,
            manzana: lot.manzana
        })), true);

        $('#lot_ids').val((json.data.lots || []).map(x => String(x.id))).trigger('change');

        renderLotsTable(json.data.lots || []);
        recalculateContract();
    }

    async function loadDevelopmentLots(developmentId) {
        if (!developmentId || $('#reservation_id').val()) {
            return;
        }

        const res = await fetch(`/contratos/development/${developmentId}/lots`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const lots = await res.json();

        directLotsCache = lots || [];

        fillSelect('lot_ids', directLotsCache, true);

        $('#lot_ids').val(null).trigger('change');
        $('#r_lotificacion').val($('#development_id option:selected').text() || '');
        $('#r_apartado').val('');
        $('#r_pagos_previos').val('');

        renderLotsTable([]);
        recalculateContract();
    }

    async function loadSellerData(sellerId) {
        if (!sellerId) {
            $('#commission_amount').val('');
            return;
        }

        const res = await fetch(`/contratos/seller/${sellerId}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        if (res.ok) {
            $('#commission_amount').val(json.data.monto_comision ?? 0);
        }
    }

    function getSelectedLots() {
        const selectedIds = ($('#lot_ids').val() || []).map(x => parseInt(x, 10));

        if (reservationContext) {
            return (reservationContext.lots || []).filter(lot => selectedIds.includes(parseInt(lot.id, 10)));
        }

        return (directLotsCache || [])
            .filter(lot => selectedIds.includes(parseInt(lot.value, 10)))
            .map(lot => ({
                id: lot.value,
                identificador: lot.text,
                manzana: lot.manzana,
                precio_contado: lot.precio_contado,
                precio_credito: lot.precio_credito
            }));
    }

    function renderLotsTable(lots) {
        const tbody = document.getElementById('contractLotsBody');
        tbody.innerHTML = '';

        lots.forEach((lot, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${lot.identificador ?? ''}</td>
                    <td>${lot.manzana ?? ''}</td>
                    <td>${lot.precio_contado ?? ''}</td>
                    <td>${lot.precio_credito ?? ''}</td>
                </tr>
            `;
        });
    }

    function isContadoSelected() {
        const text = ($('#contract_payment_type_id option:selected').text() || '').toUpperCase();

        return text.includes('CONTADO');
    }

    function isCreditoSelected() {
        const text = ($('#contract_payment_type_id option:selected').text() || '').toUpperCase();

        return text.includes('CRÉDITO') || text.includes('CREDITO');
    }

    function toggleContractFields() {
        const contado = isContadoSelected();
        const credito = isCreditoSelected();

        if (contado) {
            $('.contract-credit-only').hide();

            $('#meses').val(0);
            $('#dia_pago').val('');
            $('#cuota_mensual').val('0.00');
            $('#saldo_financiado').val('0.00');
        } else if (credito) {
            $('.contract-credit-only').show();
        } else {
            $('.contract-credit-only').show();
        }
    }

    function recalculateContract() {
        const lots = getSelectedLots();
        const isCredit = isCreditoSelected();
        const isContado = isContadoSelected();

        let total = 0;

        lots.forEach(lot => {
            total += parseFloat(isCredit ? (lot.precio_credito || 0) : (lot.precio_contado || 0));
        });

        $('#importe').val(total.toFixed(2));

        renderLotsTable(lots);

        const pagosPrevios = reservationContext ? parseFloat(reservationContext.pagos_previos_total || 0) : 0;

        if (reservationContext && !document.getElementById('monto_pago_inicial').dataset.userEdited) {
            $('#monto_pago_inicial').val(pagosPrevios.toFixed(2));
        }

        const pagoInicial = parseFloat($('#monto_pago_inicial').val() || 0);
        const meses = parseInt($('#meses').val() || 0, 10);

        if (isContado) {
            $('#saldo_financiado').val('0.00');
            $('#cuota_mensual').val('0.00');
            return;
        }

        const saldo = Math.max(0, total - pagoInicial);

        $('#saldo_financiado').val(saldo.toFixed(2));

        if (isCredit && meses > 0) {
            $('#cuota_mensual').val((saldo / meses).toFixed(2));
        } else {
            $('#cuota_mensual').val('0.00');
        }
    }

    function initTable() {
        table = $('#tblContratos').DataTable({
            ajax: {
                url: '/contratos/datatable',
                dataSrc: 'data'
            },
            columns: [
                {
                    data: null,
                    render: (_, __, ___, meta) => meta.row + 1
                },
                { data: 'numero_referencia' },
                { data: 'fecha_emision' },
                { data: 'cliente' },
                { data: 'lotificacion' },
                { data: 'tipo_pago' },
                { data: 'tipo_propiedad' },
                {
                    data: 'importe',
                    className: 'text-end',
                    render: function (data) {
                        return Number(data || 0).toLocaleString('es-MX', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                },
                {
                    data: 'estado_badge',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'acciones',
                    orderable: false,
                    searchable: false
                }
            ],
            pageLength: 10,
            order: [],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json'
            }
        });
    }

    async function openNew() {
        await loadOptions();

        resetForm();

        modal.show();
    }

    function fillDetailPropertyTypes(selectedValue = null) {
        const select = $('#dc_contract_property_type');

        select.html('<option value="">Seleccione...</option>');

        (optionsCache?.contract_property_types || []).forEach(item => {
            select.append(`<option value="${item.value}">${item.text}</option>`);
        });

        select.val(selectedValue || '');
    }

    async function viewItem(id) {
        await loadOptions();

        const res = await fetch(`/contratos/${id}`, {
            headers: {
                'Accept': 'application/json'
            }
        });

        const json = await res.json();

        if (!res.ok) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: json.message || 'No se pudo cargar el contrato.'
            });
            return;
        }

        selectedDetailContractId = id;
        detailCanEdit = !!json.can_edit;

        $('#dc_referencia').val(json.data.numero_referencia || '');
        $('#dc_fecha').val(json.data.fecha_emision || '');
        $('#dc_cliente').val(json.data.cliente || '');
        $('#dc_lotificacion').val(json.data.lotificacion || '');
        $('#dc_tipo').val(json.data.tipo_pago || '');

        fillDetailPropertyTypes(json.data.contract_property_type || '');

        $('#dc_estado').val(json.data.estado || '');
        $('#dc_oficina').val(json.data.oficina || '');
        $('#dc_vendedor').val(json.data.vendedor || '');
        $('#dc_comision').val(json.data.comision || '');
        $('#dc_meses').val(json.data.meses || '');
        $('#dc_importe').val(json.data.importe || '');
        $('#dc_pago_inicial').val(json.data.monto_pago_inicial || '');
        $('#dc_saldo').val(json.data.saldo_financiado || '');
        $('#dc_dia_pago').val(json.data.dia_pago || '');
        $('#dc_cuota').val(json.data.cuota_mensual || '');
        $('#dc_observaciones').val(json.data.observaciones || '');

        $('#dc_contract_property_type').prop('disabled', !detailCanEdit);
        $('#dc_observaciones').prop('readonly', !detailCanEdit);

        if (detailCanEdit) {
            $('#detalle_edit_alert').removeClass('d-none');
            $('#detalle_readonly_alert').addClass('d-none');
            $('#btnGuardarDetalleContrato').removeClass('d-none');
        } else {
            $('#detalle_edit_alert').addClass('d-none');
            $('#detalle_readonly_alert').removeClass('d-none');
            $('#btnGuardarDetalleContrato').addClass('d-none');
        }

        const tbody = document.getElementById('detalleContratoLotesBody');
        tbody.innerHTML = '';

        (json.data.lots || []).forEach((lot, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${lot.identificador ?? ''}</td>
                    <td>${lot.manzana ?? ''}</td>
                    <td>${lot.sale_price ?? ''}</td>
                    <td>${lot.discount ?? ''}</td>
                    <td>${lot.subtotal ?? ''}</td>
                </tr>
            `;
        });

        modalDetalle.show();
    }

    async function saveDetailContract() {
        if (!selectedDetailContractId) {
            return;
        }

        if (!detailCanEdit) {
            Swal.fire({
                icon: 'warning',
                title: 'No permitido',
                text: 'Solo se pueden modificar contratos en estado VIGENTE.'
            });
            return;
        }

        try {
            const res = await fetch(`/contratos/${selectedDetailContractId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    contract_property_type: $('#dc_contract_property_type').val(),
                    observaciones: $('#dc_observaciones').val()
                })
            });

            const json = await res.json();

            if (!res.ok) {
                throw new Error(json.message || 'No se pudo modificar el contrato.');
            }

            Swal.fire({
                icon: 'success',
                title: 'Actualizado',
                text: json.message || 'Contrato actualizado correctamente.',
                timer: 1600,
                showConfirmButton: false
            });

            modalDetalle.hide();
            table.ajax.reload(null, false);
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo modificar el contrato.'
            });
        }
    }

    async function saveItem(e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch('/contratos', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const json = await res.json();

            if (!res.ok) {
                throw new Error(json.message || 'No se pudo guardar');
            }

            modal.hide();

            table.ajax.reload(null, false);

            Swal.fire({
                icon: 'success',
                title: 'Correcto',
                text: json.message,
                timer: 1700,
                showConfirmButton: false
            });
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        }
    }

    function fillDocumentDataForm(data, system = {}) {
        documentSystemData = {
            direccion_comprador: system.direccion_comprador || '',
            telefono_comprador: system.telefono_comprador || ''
        };

        $('#doc_ciudad_firma').val(data.ciudad_firma || '');
        $('#doc_ubicacion_terreno').val(data.ubicacion_terreno || '');

        const defaultPdfSeller = 'DANY FRANK PABLO FLORES';
        const customPdfSeller = Number(data.vendedor_personalizado || 0) === 1;

        $('#doc_vendedor_personalizado').prop('checked', customPdfSeller);
        $('#doc_vendedor_contrato')
            .val(customPdfSeller ? (data.vendedor_contrato || '') : defaultPdfSeller)
            .prop('readonly', !customPdfSeller)
            .toggleClass('bg-light', !customPdfSeller);

        const hasSystemAddress = !!documentSystemData.direccion_comprador;
        const hasSystemPhone = !!documentSystemData.telefono_comprador;

        const useSystemAddress = Number(data.usar_direccion_sistema || 0) === 1 && hasSystemAddress;
        const useSystemPhone = Number(data.usar_telefono_sistema || 0) === 1 && hasSystemPhone;

        $('#doc_usar_direccion_sistema')
            .prop('checked', useSystemAddress)
            .prop('disabled', !hasSystemAddress);

        $('#doc_usar_telefono_sistema')
            .prop('checked', useSystemPhone)
            .prop('disabled', !hasSystemPhone);

        $('#doc_direccion_comprador')
            .val(useSystemAddress ? documentSystemData.direccion_comprador : (data.direccion_comprador || ''))
            .prop('readonly', useSystemAddress)
            .toggleClass('bg-light', useSystemAddress);

        $('#doc_telefono_comprador')
            .val(useSystemPhone ? documentSystemData.telefono_comprador : (data.telefono_comprador || ''))
            .prop('readonly', useSystemPhone)
            .toggleClass('bg-light', useSystemPhone);

        $('#doc_lote_numero').val(data.lote_numero || '');
        $('#doc_manzana_numero').val(data.manzana_numero || '');
        $('#doc_area_m2').val(data.area_m2 || '');

        $('#doc_norte_medida').val(data.norte_medida || '');
        $('#doc_norte_colindancia').val(data.norte_colindancia || '');

        $('#doc_sur_medida').val(data.sur_medida || '');
        $('#doc_sur_colindancia').val(data.sur_colindancia || '');

        $('#doc_oriente_medida').val(data.oriente_medida || '');
        $('#doc_oriente_colindancia').val(data.oriente_colindancia || '');

        $('#doc_poniente_medida').val(data.poniente_medida || '');
        $('#doc_poniente_colindancia').val(data.poniente_colindancia || '');

        $('#doc_testigo_1').val(data.testigo_1 || '');
        $('#doc_testigo_2').val(data.testigo_2 || '');

        $('#doc_observaciones_documento').val(data.observaciones_documento || '');
    }

    function setDocumentDataReadonly(readonly, message = null) {
        const form = $('#formContractDocumentData');

        form.find('input, textarea, select').prop('readonly', readonly);
        form.find('input[type="checkbox"]').prop('disabled', readonly);

        $('#doc_contract_id').prop('readonly', false);

        $('#doc_lote_numero').prop('readonly', true).addClass('bg-light');
        $('#doc_manzana_numero').prop('readonly', true).addClass('bg-light');
        $('#doc_observaciones_documento').prop('readonly', true).addClass('bg-light');

        if (!readonly) {
            const hasSystemAddress = !!documentSystemData.direccion_comprador;
            const hasSystemPhone = !!documentSystemData.telefono_comprador;

            $('#doc_usar_direccion_sistema').prop('disabled', !hasSystemAddress);
            $('#doc_usar_telefono_sistema').prop('disabled', !hasSystemPhone);

            $('#doc_direccion_comprador')
                .prop('readonly', $('#doc_usar_direccion_sistema').is(':checked'))
                .toggleClass('bg-light', $('#doc_usar_direccion_sistema').is(':checked'));

            $('#doc_telefono_comprador')
                .prop('readonly', $('#doc_usar_telefono_sistema').is(':checked'))
                .toggleClass('bg-light', $('#doc_usar_telefono_sistema').is(':checked'));

            $('#doc_vendedor_contrato')
                .prop('readonly', !$('#doc_vendedor_personalizado').is(':checked'))
                .toggleClass('bg-light', !$('#doc_vendedor_personalizado').is(':checked'));
        }

        if (readonly) {
            $('#doc_readonly_alert')
                .removeClass('d-none')
                .text(message || 'Este contrato ya no permite modificaciones. La información se muestra solo para consulta.');

            $('#formContractDocumentData button[type="submit"]').addClass('d-none');
        } else {
            $('#doc_readonly_alert').addClass('d-none');
            $('#formContractDocumentData button[type="submit"]').removeClass('d-none');
        }
    }

    async function openDocumentData(id) {
        $('#doc_contract_id').val(id);
        $('#formContractDocumentData')[0].reset();

        try {
            const response = await $.get(`/contratos/${id}/document-data`);

            if (!response.success) {
                return Swal.fire('Aviso', 'No se pudieron obtener los datos del contrato.', 'warning');
            }

            fillDocumentDataForm(response.data || {}, response.system || {});
            setDocumentDataReadonly(!response.can_edit, response.message);

            modalDocumentData.show();
        } catch (error) {
            Swal.fire('Error', 'No se pudieron cargar los datos complementarios.', 'error');
        }
    }

    async function saveDocumentData(e) {
        e.preventDefault();

        const id = $('#doc_contract_id').val();
        const formData = $(this).serialize();

        try {
            const response = await $.ajax({
                url: `/contratos/${id}/document-data`,
                method: 'POST',
                data: formData
            });

            if (!response.success) {
                return Swal.fire('Aviso', response.message || 'No se pudo guardar.', 'warning');
            }

            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: response.message || 'Datos guardados correctamente.',
                timer: 1600,
                showConfirmButton: false
            });

            modalDocumentData.hide();

            table.ajax.reload(null, false);
        } catch (error) {
            const message = error.responseJSON?.message || 'No se pudieron guardar los datos complementarios.';

            Swal.fire('Error', message, 'error');
        }
    }

    async function openContractPdf(id) {
        try {
            const check = await fetch(`/contratos/${id}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const json = await check.json();

            if (!check.ok) {
                throw new Error(json.message || 'No se pudo validar el contrato.');
            }

            if (!json.data.contract_property_type) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tipo de contrato requerido',
                    text: 'Para generar el contrato primero debes modificar el contrato y elegir el tipo: EJIDO o PROPIEDAD.'
                });
                return;
            }

            window.open(`/contratos/${id}/documento`, '_blank');
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'No se pudo generar el contrato.'
            });
        }
    }

    document.getElementById('btnNuevoContrato').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);
    document.getElementById('formContractDocumentData').addEventListener('submit', saveDocumentData);

    $('#btnGuardarDetalleContrato').on('click', saveDetailContract);

    $('#client_id').on('change', async function () {
        if (suppressClientReload) {
            suppressClientReload = false;
            return;
        }

        await loadClientData(this.value, false);
    });

    $('#reservation_id').on('change', async function () {
        if (suppressReservationReload) {
            suppressReservationReload = false;
            return;
        }

        if (this.value) {
            await loadReservationData(this.value);
        } else {
            reservationContext = null;

            $('#r_apartado').val('');
            $('#r_pagos_previos').val('');

            fillSelect('lot_ids', [], true);

            const devId = $('#development_id').val();

            if (devId) {
                await loadDevelopmentOffices(devId);
                await loadDevelopmentLots(devId);
            } else {
                $('#contractLotsBody').html('');
                recalculateContract();
            }
        }
    });

    $('#development_id').on('change', async function () {
        await loadDevelopmentOffices(this.value);

        if ($('#reservation_id').val()) {
            return;
        }

        await loadDevelopmentLots(this.value);
    });

    $('#office_id').on('change', function () {
        loadOfficePaymentMethods(this.value);
    });

    $('#seller_id').on('change', function () {
        loadSellerData(this.value);
    });

    $('#lot_ids, #contract_payment_type_id').on('change', function () {
        toggleContractFields();
        recalculateContract();
    });

    $('#monto_pago_inicial').on('input', function () {
        this.dataset.userEdited = '1';
        recalculateContract();
    });

    $('#meses').on('input', function () {
        recalculateContract();
    });

    $('#tblContratos').on('click', '.btn-view', function () {
        viewItem(this.dataset.id);
    });

    $('#tblContratos').on('click', '.btn-document-data', function () {
        openDocumentData(this.dataset.id);
    });

    $('#tblContratos').on('click', '.btn-contract-pdf', function () {
        openContractPdf(this.dataset.id);
    });

    $('#doc_usar_direccion_sistema').on('change', function () {
        const checked = $(this).is(':checked');

        if (checked) {
            $('#doc_direccion_comprador')
                .val(documentSystemData.direccion_comprador || '')
                .prop('readonly', true)
                .addClass('bg-light');
        } else {
            $('#doc_direccion_comprador')
                .prop('readonly', false)
                .removeClass('bg-light');
        }
    });

    $('#doc_usar_telefono_sistema').on('change', function () {
        const checked = $(this).is(':checked');

        if (checked) {
            $('#doc_telefono_comprador')
                .val(documentSystemData.telefono_comprador || '')
                .prop('readonly', true)
                .addClass('bg-light');
        } else {
            $('#doc_telefono_comprador')
                .prop('readonly', false)
                .removeClass('bg-light');
        }
    });


    $('#doc_vendedor_personalizado').on('change', function () {
        const useCustomSeller = $(this).is(':checked');
        const defaultPdfSeller = 'DANY FRANK PABLO FLORES';

        $('#doc_vendedor_contrato')
            .val(useCustomSeller ? '' : defaultPdfSeller)
            .prop('readonly', !useCustomSeller)
            .toggleClass('bg-light', !useCustomSeller)
            .trigger('focus');
    });

    initSelect2();
    initTable();
})();
</script>
@endpush