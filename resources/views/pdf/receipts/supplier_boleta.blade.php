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
                <td>
                    <div class="label">Motivo</div>
                    <div class="value">{{ $voucher->motivo ?? 'N/A' }}</div>
                </td>
                <td>
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


</div>

@if(isset($partners) && count($partners) > 0)
    <div class="keep-together">
        <div class="section-title">Desglose por Socio</div>
        <table class="detail-table mb-12">
            <thead>
                <tr>
                    <th rowspan="2">Socio</th>
                    <th rowspan="2">%</th>
                    <th class="text-right">Capital Asig.</th>
                    <th class="text-right">Abonado (Cap)</th>
                    <th class="text-right">Pendiente (Cap)</th>
                    <th class="text-center" rowspan="2">Pagos Saldo</th>
                    <th class="text-center" rowspan="2">Pagos Interés</th>
                </tr>
                <tr>
                    <th class="text-right">Int. Generado</th>
                    <th class="text-right">Int. Pagado</th>
                    <th class="text-right">Int. Pendiente</th>
                </tr>
            </thead>
            <tbody>
                @foreach($partners as $p)
                <tr>
                    <td rowspan="2">{{ mb_strtoupper($p->nombre) }} {!! $p->es_titular ? '<br><strong>(TITULAR)</strong>' : '' !!}</td>
                    <td rowspan="2">{{ number_format($p->porcentaje, 2) }}%</td>
                    <td class="text-right">${{ number_format($p->progress['capital_total'] ?? 0, 2) }}</td>
                    <td class="text-right text-success fw-bold">${{ number_format($p->progress['ha_pagado'] ?? 0, 2) }}</td>
                    <td class="text-right text-danger fw-bold">${{ number_format($p->progress['saldo_pendiente'] ?? 0, 2) }}</td>
                    <td class="text-center" rowspan="2">{{ $p->progress['conteo_pagos_saldo'] ?? 0 }}</td>
                    <td class="text-center" rowspan="2">{{ $p->progress['conteo_pagos_interes'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td class="text-right">${{ number_format($p->progress['interes_acumulado'] ?? 0, 2) }}</td>
                    <td class="text-right">${{ number_format($p->progress['interes_pagado'] ?? 0, 2) }}</td>
                    <td class="text-right text-danger fw-bold">${{ number_format($p->progress['interes_pendiente'] ?? 0, 2) }}</td>
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
                            <td>{{ isset($sc['due_date']) ? \Carbon\Carbon::parse($sc['due_date'])->format('d-m-Y') : '' }}</td>
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

@if(isset($partners) && count($partners) > 0)
    @foreach($partners as $p)
        @php
            $partnerItems = collect($items)->filter(function($row) use ($p) {
                return $row->supplier_voucher_partner_id == $p->id;
            })->values();
        @endphp
        
        <div class="keep-together" style="margin-top: 15px;">
            <div class="section-title" style="background-color: #2c3e50; color: #fff; padding: 5px; font-size: 12px; margin-bottom: 0;">
                Partidas (Abonos) - {{ mb_strtoupper($p->nombre) }}
            </div>

            <div style="font-size: 11px; margin-bottom: 8px; margin-top: 4px; text-align: right; font-weight: bold; color: #555;">
                Total Pagos a Saldo: {{ $p->progress['conteo_pagos_saldo'] ?? 0 }} &nbsp;&nbsp;|&nbsp;&nbsp; 
                Total Pagos a Interés: {{ $p->progress['conteo_pagos_interes'] ?? 0 }}
            </div>

            <table class="detail-table">
                <thead>
                    <tr>
                        <th style="width: 25px;">#</th>
                        <th>Folio Abono</th>
                        <th>Fecha Recibido</th>
                        <th>Forma Pago</th>
                        <th class="text-right">Capital</th>
                        <th class="text-right">Interés</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partnerItems as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $row->id }}</td>
                            <td>{{ isset($row->fecha_recibido) ? \Carbon\Carbon::parse($row->fecha_recibido)->format('d-m-Y') : '' }}</td>
                            <td>{{ mb_strtoupper($row->forma_pago ?? 'N/A') }}</td>
                            <td class="text-right">${{ number_format($row->cantidad, 2) }}</td>
                            <td class="text-right">${{ number_format($row->interes_pagado ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">Sin abonos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="signature-wrap" style="margin-top: 40px; margin-bottom: 20px;">
                <div class="signature-box">
                    <div class="signature-line">
                        ENTREGÓ
                    </div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">
                        RECIBIÓ
                        <br>{{ mb_strtoupper($p->nombre) }}
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
