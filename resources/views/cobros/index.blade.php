@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2">
        <div>
            <h3 class="fw-bold mb-1">Cobros</h3>
            <div class="text-muted">Cobranza de contratos, mensualidades, adelantos y recargos</div>
        </div>

        <button class="btn btn-primary" id="btnNuevoCobro">
            <i class="fa-solid fa-plus me-1"></i> Nuevo cobro
        </button>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle w-100" id="tblCobros">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Referencia</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Origen</th>
                    <th>Tipo cobro</th>
                    <th>Forma pago</th>
                    <th>Monto</th>
                    <th>Recargo</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="modalCobro" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="formCobro">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo cobro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cliente</label>
                            <select class="form-select select2-cobro" id="client_id" name="client_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Contrato</label>
                            <select class="form-select select2-cobro" id="contract_id" name="contract_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tipo cobro</label>
                            <select class="form-select select2-cobro" id="charge_type_id" name="charge_type_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Forma pago</label>
                            <select class="form-select select2-cobro" id="payment_method_id" name="payment_method_id"></select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Monto</label>
                            <input type="number" step="0.01" class="form-control" id="monto" name="monto">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Observación</label>
                            <textarea class="form-control" id="observacion" name="observacion" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="page-card">
                        <h6 class="fw-bold mb-3">Resumen contrato</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Referencia</label>
                                <input type="text" class="form-control" id="s_ref" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="s_cliente" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo</label>
                                <input type="text" class="form-control" id="s_tipo" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Pagado total</label>
                                <input type="text" class="form-control" id="s_pagado" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Importe</label>
                                <input type="text" class="form-control" id="s_importe" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Inicial</label>
                                <input type="text" class="form-control" id="s_inicial" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Saldo financiado</label>
                                <input type="text" class="form-control" id="s_saldo" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Cuota mensual</label>
                                <input type="text" class="form-control" id="s_cuota" readonly>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Vence</th>
                                        <th>Monto</th>
                                        <th>Pagado</th>
                                        <th>Recargo</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="scheduleBody"></tbody>
                            </table>
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
@endsection

@push('scripts')
<script>
(() => {
    const modal = new bootstrap.Modal(document.getElementById('modalCobro'));
    const form = document.getElementById('formCobro');
    let table = null;
    let optionsCache = null;

    function initSelect2() {
        $('.select2-cobro').select2({
            theme: 'bootstrap4',
            width: '100%',
            dropdownParent: $('#modalCobro')
        });
    }

    async function loadOptions() {
        if (optionsCache) return optionsCache;

        const res = await fetch('/cobros/options');
        optionsCache = await res.json();

        fillSelect('client_id', optionsCache.clients);
        fillSelect('contract_id', optionsCache.contracts);
        fillSelect('charge_type_id', optionsCache.charge_types);
        fillSelect('payment_method_id', optionsCache.payment_methods);

        return optionsCache;
    }

    function fillSelect(id, items) {
        const el = document.getElementById(id);
        el.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
        $(el).trigger('change');
    }

    function resetSummary() {
        ['s_ref','s_cliente','s_tipo','s_pagado','s_importe','s_inicial','s_saldo','s_cuota'].forEach(id => {
            document.getElementById(id).value = '';
        });
        document.getElementById('scheduleBody').innerHTML = '';
    }

    function resetForm() {
        form.reset();
        $('.select2-cobro').val(null).trigger('change');
        resetSummary();
    }

    function initTable() {
        table = $('#tblCobros').DataTable({
            ajax: { url: '/cobros/datatable', dataSrc: 'data' },
            columns: [
                { data: null, render: (_, __, ___, meta) => meta.row + 1 },
                { data: 'numero_referencia' },
                { data: 'fecha_emision' },
                { data: 'cliente' },
                { data: 'referencia_origen' },
                { data: 'tipo_cobro' },
                { data: 'forma_pago' },
                { data: 'monto' },
                { data: 'monto_recargo' }
            ],
            pageLength: 10,
            order: [],
            language: { url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' }
        });
    }

    async function loadContractSummary(contractId) {
        resetSummary();
        if (!contractId) return;

        const res = await fetch(`/cobros/contract/${contractId}/summary`);
        const json = await res.json();
        if (!res.ok) return;

        const d = json.data;
        document.getElementById('s_ref').value = d.numero_referencia || '';
        document.getElementById('s_cliente').value = d.cliente || '';
        document.getElementById('s_tipo').value = d.tipo_pago || '';
        document.getElementById('s_pagado').value = d.pagado_total || 0;
        document.getElementById('s_importe').value = d.importe || 0;
        document.getElementById('s_inicial').value = d.monto_pago_inicial || 0;
        document.getElementById('s_saldo').value = d.saldo_financiado || 0;
        document.getElementById('s_cuota').value = d.cuota_mensual || 0;

        const tbody = document.getElementById('scheduleBody');
        tbody.innerHTML = '';
        (d.schedules || []).forEach(row => {
            tbody.innerHTML += `
                <tr>
                    <td>${row.installment_number}</td>
                    <td>${row.due_date}</td>
                    <td>${row.amount}</td>
                    <td>${row.amount_paid}</td>
                    <td>${row.late_fee_amount}</td>
                    <td>${row.status}</td>
                </tr>
            `;
        });
    }

    async function openNew() {
        await loadOptions();
        resetForm();
        modal.show();
    }

    async function saveItem(e) {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch('/cobros', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
            });
        }
    }

    document.getElementById('btnNuevoCobro').addEventListener('click', openNew);
    form.addEventListener('submit', saveItem);

    $('#contract_id').on('change', function () {
        loadContractSummary(this.value);
    });

    initSelect2();
    initTable();
})();
</script>
@endpush