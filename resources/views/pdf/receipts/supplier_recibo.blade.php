@extends('pdf.layouts.receipt')

@section('content')
<div class="keep-together">
    <div class="section-title">Datos del recibo (Abono)</div>

    <div class="card">
        <table class="meta-table">
            <tr>
                <td style="width: 33.333%;">
                    <div class="label">Folio recibo</div>
                    <div class="value">{{ $folio }}</div>
                </td>
                <td style="width: 33.333%;">
                    <div class="label">Proveedor</div>
                    <div class="value">{{ mb_strtoupper($voucher->proveedor) }}</div>
                </td>
                <td style="width: 33.333%;">
                    <div class="label">Fecha Recibido</div>
                    <div class="value">{{ $item->fecha_recibido ?? 'N/A' }}</div>
                </td>
            </tr>
            <tr>
                <td style="width: 33.333%;">
                    <div class="label">Socio</div>
                    <div class="value">{{ mb_strtoupper($item->socio_nombre ?? 'N/A') }}</div>
                </td>
                <td style="width: 33.333%;">
                    <div class="label">Forma de Pago</div>
                    <div class="value">{{ mb_strtoupper($item->forma_pago ?? 'N/A') }}</div>
                </td>
                <td style="width: 33.333%;">
                    <div class="label">Usuario Registro</div>
                    <div class="value">{{ mb_strtoupper($item->usuario_registro ?? 'N/A') }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="keep-together">
    <div class="section-title">Importe del recibo</div>

    <table class="summary-table mb-12">
        <tr>
            <td>
                <div class="summary-box success">
                    <div class="small">Abono (Capital)</div>
                    <div class="big">${{ number_format($item->cantidad, 2) }}</div>
                </div>
            </td>
            @if(($item->interes_pagado ?? 0) > 0)
            <td>
                <div class="summary-box" style="background-color: #f8d7da; border-color: #f5c6cb;">
                    <div class="small" style="color: #721c24;">Interés Pagado</div>
                    <div class="big" style="color: #721c24;">${{ number_format($item->interes_pagado, 2) }}</div>
                </div>
            </td>
            @endif
        </tr>
    </table>
</div>

<div class="keep-together">
    <div class="section-title">Boleta del Proyecto</div>

    <div class="card">
        <table class="meta-table">
            <tr>
                <td style="width: 25%;">
                    <div class="label">Total Boleta</div>
                    <div class="value">${{ number_format($voucher->total ?? 0, 2) }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Enganche</div>
                    <div class="value">${{ number_format($voucher->enganche ?? 0, 2) }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Folio Boleta</div>
                    <div class="value">{{ $voucher->numero_referencia }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Abonos Generados</div>
                    <div class="value">{{ $stats['total_payments'] ?? 0 }}</div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="signature-wrap">
    <div class="signature-box">
        <div class="signature-line">
            ENTREGÓ
        </div>
    </div>
    <div class="signature-box">
        <div class="signature-line">
            RECIBIÓ
            <br>{{ mb_strtoupper($voucher->proveedor) }}
        </div>
    </div>
</div>
@endsection
