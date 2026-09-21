@extends('pdf.layouts.receipt')

@section('content')
<div class="keep-together">
    <div class="section-title">Datos de la boleta</div>

    <div class="card">
        <table class="meta-table">
            <tr>
                <td style="width: 33.333%;">
                    <div class="label">Folio boleta</div>
                    <div class="value">{{ $voucher->numero_referencia }}</div>
                </td>
                <td style="width: 33.333%;">
                    <div class="label">Acreedor</div>
                    <div class="value">{{ mb_strtoupper($voucher->acreedor) }}</div>
                </td>
                <td style="width: 33.333%;">
                    <div class="label">Fecha Emisión</div>
                    <div class="value">{{ $voucher->fecha ?? $voucher->created_at }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Estado</div>
                    <div class="value">{{ mb_strtoupper($voucher->estado_pago ?? 'N/A') }}</div>
                </td>
                <td colspan="2">
                    <div class="label">Observaciones</div>
                    <div class="value">{{ $voucher->observacion ?? 'N/A' }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="keep-together">
    <div class="section-title">Resumen Financiero</div>

    <table class="summary-table mb-12" style="width: 100%; border-spacing: 5px;">
        <tr>
            <td style="width: 25%;">
                <div class="summary-box">
                    <div class="small">Total Boleta</div>
                    <div class="big">${{ number_format($voucher->importe ?? 0, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-box">
                    <div class="small">Enganche</div>
                    <div class="big">${{ number_format($voucher->enganche ?? 0, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-box success">
                    <div class="small">Capital Pagado</div>
                    <div class="big">${{ number_format($voucher->total_pagado ?? 0, 2) }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="summary-box warning">
                    <div class="small">Capital Restante</div>
                    <div class="big">${{ number_format($voucher->saldo_pendiente ?? 0, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>
    
    <table class="summary-table mb-12" style="width: 100%; border-spacing: 5px;">
        <tr>
            <td style="width: 33.33%;">
                <div class="summary-box">
                    <div class="small">Interés Generado</div>
                    <div class="big">${{ number_format($progress['interes_acumulado'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td style="width: 33.33%;">
                <div class="summary-box success">
                    <div class="small">Interés Pagado</div>
                    <div class="big">${{ number_format($progress['interes_pagado'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td style="width: 33.33%;">
                <div class="summary-box warning">
                    <div class="small">Interés Pendiente</div>
                    <div class="big">${{ number_format($progress['interes_pendiente'] ?? 0, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Historial de Operaciones</div>

<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 25px;">#</th>
            <th>Tipo</th>
            <th>Fecha</th>
            <th>Concepto / Obs.</th>
            <th class="text-right">Monto</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $row->tipo }}</strong></td>
                <td>{{ $row->fecha }}</td>
                <td>{{ $row->concepto }}</td>
                <td class="text-right">${{ number_format($row->monto, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">Sin operaciones registradas</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="signature-wrap">
    <div class="signature-box">
        <div class="signature-line">
            ENTREGÓ
        </div>
    </div>
    <div class="signature-box">
        <div class="signature-line">
            RECIBIÓ
            <br>{{ mb_strtoupper($voucher->acreedor) }}
        </div>
    </div>
</div>
@endsection
