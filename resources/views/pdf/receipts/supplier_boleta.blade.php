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
                    <div class="label">Proveedor</div>
                    <div class="value">{{ mb_strtoupper($voucher->proveedor) }}</div>
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

    <table class="summary-table mb-12">
        <tr>
            <td>
                <div class="summary-box">
                    <div class="small">Total Boleta</div>
                    <div class="big">${{ number_format($voucher->total ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-box success">
                    <div class="small">Total Abonos</div>
                    <div class="big">${{ number_format($totalAbonos ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-box warning">
                    <div class="small">Resto</div>
                    <div class="big">${{ number_format(($voucher->total ?? 0) - ($totalAbonos ?? 0), 2) }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">Partidas (Abonos)</div>

<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 25px;">#</th>
            <th>Folio Abono</th>
            <th>Fecha Recibido</th>
            <th>Forma Pago</th>
            <th class="text-right">Monto</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->id }}</td>
                <td>{{ $row->fecha_recibido ?? '' }}</td>
                <td>{{ mb_strtoupper($row->forma_pago ?? 'N/A') }}</td>
                <td class="text-right">${{ number_format($row->cantidad, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center">Sin abonos registrados</td>
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
            <br>{{ mb_strtoupper($voucher->proveedor) }}
        </div>
    </div>
</div>
@endsection
