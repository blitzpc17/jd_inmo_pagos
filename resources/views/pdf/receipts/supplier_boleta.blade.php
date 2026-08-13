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
                    <div class="small">Costo Total</div>
                    <div class="big">${{ number_format($voucher->total ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-box">
                    <div class="small">Enganche</div>
                    <div class="big">${{ number_format($voucher->enganche ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-box success">
                    <div class="small">Abonado (Capital)</div>
                    <div class="big">${{ number_format($totalAbonos ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-box warning">
                    <div class="small">Resto (Capital)</div>
                    <div class="big">${{ number_format(max(0, ($voucher->total ?? 0) - ($voucher->enganche ?? 0) - ($totalAbonos ?? 0)), 2) }}</div>
                </div>
            </td>
        </tr>
    </table>
    
    @if(($totalInteres ?? 0) > 0)
    <table class="summary-table mb-12" style="width: 25%;">
        <tr>
            <td>
                <div class="summary-box" style="background-color: #f8d7da; border-color: #f5c6cb;">
                    <div class="small" style="color: #721c24;">Interés Pagado</div>
                    <div class="big" style="color: #721c24;">${{ number_format($totalInteres, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>
    @endif

    <table class="summary-table mb-12" style="width: 50%;">
        <tr>
            <td>
                <div class="summary-box" style="background-color: #e2e3e5; border-color: #d6d8db;">
                    <div class="small" style="color: #383d41;">Conteo Pagos a Saldo</div>
                    <div class="big" style="color: #383d41;">{{ $stats['capital_payments'] ?? 0 }}</div>
                </div>
            </td>
            <td>
                <div class="summary-box" style="background-color: #f8d7da; border-color: #f5c6cb;">
                    <div class="small" style="color: #721c24;">Conteo Pagos a Interés</div>
                    <div class="big" style="color: #721c24;">{{ $stats['interest_payments'] ?? 0 }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

@if(isset($partners) && count($partners) > 0)
    <div class="keep-together">
        <div class="section-title">Desglose por Socio</div>
        <table class="detail-table mb-12">
            <thead>
                <tr>
                    <th>Socio</th>
                    <th>%</th>
                    <th class="text-right">Capital Asignado</th>
                    <th class="text-right">Abonado (Capital)</th>
                    <th class="text-right">Pendiente (Capital)</th>
                    <th class="text-right">Interés Generado</th>
                    <th class="text-right">Interés Pagado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($partners as $p)
                <tr>
                    <td>{{ mb_strtoupper($p->nombre) }} {!! $p->es_titular ? '<strong>(TITULAR)</strong>' : '' !!}</td>
                    <td>{{ number_format($p->porcentaje, 2) }}%</td>
                    <td class="text-right">${{ number_format($p->progress['capital_total'] ?? 0, 2) }}</td>
                    <td class="text-right text-success fw-bold">${{ number_format($p->progress['ha_pagado'] ?? 0, 2) }}</td>
                    <td class="text-right text-danger fw-bold">${{ number_format($p->progress['saldo_pendiente'] ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($p->progress['interes_acumulado'] ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($p->progress['interes_pagado'] ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="keep-together" style="margin-top: 15px;">
        <div class="section-title">Calendario de Pagos por Socio</div>
        @foreach($partners as $p)
            <div style="font-weight: bold; font-size: 11px; margin-bottom: 5px; margin-top: 10px;">
                {{ mb_strtoupper($p->nombre) }} ({{ number_format($p->porcentaje, 2) }}%)
            </div>
            <table class="detail-table mb-12" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 25px;">#</th>
                        <th>F. Programada</th>
                        <th class="text-right">Monto</th>
                        <th class="text-right">Abonado</th>
                        <th class="text-right">Pendiente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($p->schedules as $sc)
                        <tr>
                            <td>{{ $sc['installment_number'] }}</td>
                            <td>{{ $sc['due_date'] }}</td>
                            <td class="text-right">${{ number_format($sc['amount'], 2) }}</td>
                            <td class="text-right text-success">${{ number_format($sc['amount_paid'], 2) }}</td>
                            <td class="text-right text-danger">${{ number_format(max(0, $sc['amount'] - $sc['amount_paid']), 2) }}</td>
                            <td>
                                @if($sc['status'] === 'PAID') PAGADO
                                @elseif($sc['status'] === 'PARTIAL') PARCIAL
                                @else PENDIENTE @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>
@endif

<div class="section-title">Partidas (Abonos)</div>

<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 25px;">#</th>
            <th>Folio Abono</th>
            <th>Fecha Recibido</th>
            <th>Socio</th>
            <th>Forma Pago</th>
            <th class="text-right">Capital</th>
            <th class="text-right">Interés</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->id }}</td>
                <td>{{ $row->fecha_recibido ?? '' }}</td>
                <td>{{ mb_strtoupper($row->socio_nombre ?? 'N/A') }}</td>
                <td>{{ mb_strtoupper($row->forma_pago ?? 'N/A') }}</td>
                <td class="text-right">${{ number_format($row->cantidad, 2) }}</td>
                <td class="text-right">${{ number_format($row->interes_pagado ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center">Sin abonos registrados</td>
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
