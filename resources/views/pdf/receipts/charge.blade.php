@extends('pdf.layouts.receipt')

@section('content')
<div class="section-title">Datos del cobro</div>

<div class="card">
    <table class="meta-table">
        <tr>
            <td>
                <div class="label">Referencia cobro</div>
                <div class="value">{{ $charge->numero_referencia }}</div>
            </td>
            <td>
                <div class="label">Fecha</div>
                <div class="value">{{ $charge->fecha_emision }}</div>
            </td>
            <td>
                <div class="label">Cliente</div>
                <div class="value">{{ $charge->cliente }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Tipo cobro</div>
                <div class="value">{{ $charge->tipo_cobro }}</div>
            </td>
            <td>
                <div class="label">Forma pago</div>
                <div class="value">{{ $charge->forma_pago }}</div>
            </td>
            <td>
                <div class="label">Monto recibido</div>
                <div class="value">${{ number_format($charge->monto, 2) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Recargo</div>
                <div class="value">${{ number_format($charge->monto_recargo ?? 0, 2) }}</div>
            </td>
            <td colspan="2">
                <div class="label">Observación</div>
                <div class="value">{{ $charge->observacion ?: 'N/A' }}</div>
            </td>
        </tr>
    </table>
</div>

@if(!empty($contract))
<div class="section-title">Boleta de pago del contrato</div>

<table class="summary-table mb-18">
    <tr>
        <td>
            <div class="summary-box">
                <div class="small">Total pagos</div>
                <div class="big">{{ $stats['total_payments'] }}</div>
            </div>
        </td>
        <td>
            <div class="summary-box">
                <div class="small">Pagados</div>
                <div class="big">{{ $stats['paid_payments'] }}</div>
            </div>
        </td>
        <td>
            <div class="summary-box">
                <div class="small">Pendientes</div>
                <div class="big">{{ $stats['pending_payments'] }}</div>
            </div>
        </td>
    </tr>
</table>

<div class="card">
    <table class="meta-table">
        <tr>
            <td>
                <div class="label">Contrato</div>
                <div class="value">{{ $contract->numero_referencia }}</div>
            </td>
            <td>
                <div class="label">Estado</div>
                <div class="value">{{ $contract->estado }}</div>
            </td>
            <td>
                <div class="label">Saldo financiado</div>
                <div class="value">${{ number_format($contract->saldo_financiado ?? 0, 2) }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Calendario de pago</div>

<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 52px;">#</th>
            <th style="width: 95px;">Vence</th>
            <th style="width: 90px;">Monto</th>
            <th style="width: 90px;">Pagado</th>
            <th style="width: 90px;">Recargo</th>
            <th style="width: 95px;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($scheduleGrid as $row)
        <tr>
            <td>{{ $row->installment_number }}</td>
            <td>{{ $row->due_date }}</td>
            <td class="text-right">${{ number_format($row->amount, 2) }}</td>
            <td class="text-right">${{ number_format($row->amount_paid, 2) }}</td>
            <td class="text-right">${{ number_format($row->late_fee_amount ?? 0, 2) }}</td>
            <td class="{{ strtoupper($row->status) === 'PAGADO' ? 'status-paid' : 'status-pending' }}">
                {{ $row->status }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="signature-wrap">
    <div class="signature-box">
        <div class="signature-line">RECIBIÓ</div>
    </div>
    <div class="signature-box">
        <div class="signature-line">ENTREGÓ</div>
    </div>
</div>
@endsection