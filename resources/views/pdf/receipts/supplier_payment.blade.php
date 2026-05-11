@extends('pdf.layouts.receipt')

@section('content')
<div class="keep-together">
    <div class="section-title">Datos del pago a proveedor</div>

    <div class="card">
        <table class="meta-table">
            <tr>
                <td>
                    <div class="label">Referencia</div>
                    <div class="value">{{ $payment->numero_referencia }}</div>
                </td>
                <td>
                    <div class="label">Fecha</div>
                    <div class="value">{{ $payment->fecha }}</div>
                </td>
                <td>
                    <div class="label">Proveedor</div>
                    <div class="value">{{ $payment->proveedor }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Forma pago</div>
                    <div class="value">{{ $payment->forma_pago }}</div>
                </td>
                <td>
                    <div class="label">Estado</div>
                    <div class="value">{{ $payment->estado }}</div>
                </td>
                <td>
                    <div class="label">Importe total</div>
                    <div class="value">${{ number_format($payment->importe, 2) }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <div class="label">Observación</div>
                    <div class="value">{{ $payment->observacion ?: 'N/A' }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="section-title">Conceptos</div>

<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 60px;">#</th>
            <th>Concepto</th>
            <th style="width: 120px;" class="text-right">Importe</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->concepto }}</td>
            <td class="text-right">${{ number_format($item->importe, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="signature-wrap">
    <div class="signature-box">
        <div class="signature-line">AUTORIZÓ</div>
    </div>
    <div class="signature-box">
        <div class="signature-line">RECIBIÓ</div>
    </div>
</div>
@endsection