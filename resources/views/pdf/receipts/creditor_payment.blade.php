@extends('pdf.layouts.receipt')

@section('content')
<div class="section-title">Datos del abono acreedor</div>

<div class="card">
    <table class="meta-table">
        <tr>
            <td>
                <div class="label">Folio boleta</div>
                <div class="value">{{ $voucher->numero_referencia }}</div>
            </td>
            <td>
                <div class="label">Acreedor</div>
                <div class="value">{{ $voucher->acreedor }}</div>
            </td>
            <td>
                <div class="label">Fecha recibido</div>
                <div class="value">{{ $item->fecha_recibido }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Forma pago</div>
                <div class="value">{{ $item->forma_pago }}</div>
            </td>
            <td>
                <div class="label">Cantidad</div>
                <div class="value">${{ number_format($item->cantidad, 2) }}</div>
            </td>
            <td>
                <div class="label">Usuario registro</div>
                <div class="value">{{ $item->usuario_registro ?: 'N/A' }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Boleta de pago acreedor</div>

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
                <div class="label">Total boleta</div>
                <div class="value">${{ number_format($voucher->total, 2) }}</div>
            </td>
            <td>
                <div class="label">Mensualidad</div>
                <div class="value">${{ number_format($voucher->mensualidad, 2) }}</div>
            </td>
            <td>
                <div class="label">Pagado</div>
                <div class="value">${{ number_format($voucher->total_pagado, 2) }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Debe</div>
                <div class="value">${{ number_format($voucher->saldo_pendiente, 2) }}</div>
            </td>
            <td>
                <div class="label">Meses</div>
                <div class="value">{{ $voucher->meses }}</div>
            </td>
            <td>
                <div class="label">Estado pago</div>
                <div class="value">{{ $voucher->estado_pago }}</div>
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
            <th style="width: 110px;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($scheduleGrid as $row)
        <tr>
            <td>{{ $row->installment_number }}</td>
            <td>{{ $row->due_date }}</td>
            <td class="text-right">${{ number_format($row->amount, 2) }}</td>
            <td class="text-right">${{ number_format($row->amount_paid, 2) }}</td>
            <td class="{{ strtoupper($row->status) === 'PAGADO' ? 'status-paid' : 'status-pending' }}">
                {{ $row->status }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="signature-wrap">
    <div class="signature-box">
        <div class="signature-line">RECIBIÓ</div>
    </div>
    <div class="signature-box">
        <div class="signature-line">ENTREGÓ</div>
    </div>
</div>
@endsection