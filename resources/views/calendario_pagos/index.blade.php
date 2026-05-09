@extends('layouts.app')

@section('content')
<div class="page-card mb-3">
    <h3 class="fw-bold mb-1">Calendario de pagos</h3>
    <div class="text-muted">Consulta mensualidades, vencimientos, pagos y recargos por contrato</div>
</div>

<div class="page-card mb-3">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Contrato</label>
            <select class="form-select" id="contract_id"></select>
        </div>
    </div>
</div>

<div class="page-card mb-3">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Referencia</label><input type="text" class="form-control" id="c_ref" readonly></div>
        <div class="col-md-4"><label class="form-label">Cliente</label><input type="text" class="form-control" id="c_cliente" readonly></div>
        <div class="col-md-4"><label class="form-label">Importe</label><input type="text" class="form-control" id="c_importe" readonly></div>
        <div class="col-md-4"><label class="form-label">Inicial</label><input type="text" class="form-control" id="c_inicial" readonly></div>
        <div class="col-md-4"><label class="form-label">Saldo</label><input type="text" class="form-control" id="c_saldo" readonly></div>
        <div class="col-md-4"><label class="form-label">Cuota</label><input type="text" class="form-control" id="c_cuota" readonly></div>
    </div>
</div>

<div class="page-card">
    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vence</th>
                    <th>Monto</th>
                    <th>Pagado</th>
                    <th>Recargo</th>
                    <th>Recargo aplicado</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="scheduleRows"></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    async function loadOptions() {
        const res = await fetch('/calendario-pagos/options');
        const json = await res.json();

        const el = document.getElementById('contract_id');
        el.innerHTML = '<option value="">Seleccione...</option>';
        (json.contracts || []).forEach(item => {
            el.innerHTML += `<option value="${item.value}">${item.text}</option>`;
        });
    }

    async function loadContract(contractId) {
        if (!contractId) return;

        const res = await fetch(`/calendario-pagos/contract/${contractId}`);
        const json = await res.json();
        if (!res.ok) return;

        const c = json.contract;
        document.getElementById('c_ref').value = c.numero_referencia || '';
        document.getElementById('c_cliente').value = c.cliente || '';
        document.getElementById('c_importe').value = c.importe || '';
        document.getElementById('c_inicial').value = c.inicial || '';
        document.getElementById('c_saldo').value = c.saldo || '';
        document.getElementById('c_cuota').value = c.cuota || '';

        const tbody = document.getElementById('scheduleRows');
        tbody.innerHTML = '';

        (json.rows || []).forEach(row => {
            tbody.innerHTML += `
                <tr>
                    <td>${row.installment_number}</td>
                    <td>${row.due_date}</td>
                    <td>${row.amount}</td>
                    <td>${row.amount_paid}</td>
                    <td>${row.late_fee_amount}</td>
                    <td>${row.late_fee_applied ? 'Sí' : 'No'}</td>
                    <td>${row.status}</td>
                </tr>
            `;
        });
    }

    document.getElementById('contract_id').addEventListener('change', function() {
        loadContract(this.value);
    });

    loadOptions();
})();
</script>
@endpush