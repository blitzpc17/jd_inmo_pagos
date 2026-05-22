@extends('pdf.layouts.receipt')

@section('content')
@php
    $stats = is_array($stats ?? null) ? $stats : [];

    $totalPayments = data_get($stats, 'total_payments', data_get($stats, 'installments_total', 0));
    $paidPayments = data_get($stats, 'paid_payments', data_get($stats, 'installments_paid', 0));
    $pendingPayments = data_get($stats, 'pending_payments', data_get($stats, 'installments_pending', 0));

    $contractTotal = (float) data_get($stats, 'contract_total', 0);
    $paidTotal = (float) data_get($stats, 'paid_total', 0);
    $balance = (float) data_get($stats, 'balance', 0);
    $lateFeeTotal = (float) data_get($stats, 'late_fee_total', 0);
    $realCollectedTotal = (float) data_get($stats, 'real_collected_total', $paidTotal + $lateFeeTotal);
    $progressPercent = (float) data_get($stats, 'progress_percent', 0);

    $initialPayment = (float) data_get($stats, 'initial_payment', 0);
    $financedBalance = (float) data_get($stats, 'financed_balance', 0);
    $monthlyAmount = (float) data_get($stats, 'monthly_amount', 0);
    $paymentType = data_get($stats, 'payment_type', '');

    $includeSchedule = !empty($includeSchedule);

    $scheduleGrid = is_array($scheduleGrid ?? null) ? $scheduleGrid : [];

    $scheduleColumns = is_array($scheduleColumns ?? null)
        ? $scheduleColumns
        : [
            'left' => [],
            'right' => [],
        ];

    if (!array_key_exists('left', $scheduleColumns)) {
        $scheduleColumns['left'] = [];
    }

    if (!array_key_exists('right', $scheduleColumns)) {
        $scheduleColumns['right'] = [];
    }

    if (
        $includeSchedule
        && empty($scheduleColumns['left'])
        && empty($scheduleColumns['right'])
        && !empty($scheduleGrid)
    ) {
        $half = (int) ceil(count($scheduleGrid) / 2);

        $scheduleColumns = [
            'left' => array_slice($scheduleGrid, 0, $half),
            'right' => array_slice($scheduleGrid, $half),
        ];
    }

    $chargeAmount = (float) ($charge->monto ?? 0);
    $chargeLateFee = (float) ($charge->monto_recargo ?? 0);
    $chargeTotal = $chargeAmount + $chargeLateFee;

    $chargeReference = $charge->numero_referencia ?? $charge->folio ?? 'S/F';
    $chargeDate = $charge->fecha_emision ?? $charge->fecha ?? '';
    $chargeCreatedAt = $charge->created_at ?? null;
    $chargeType = $charge->tipo_cobro ?? $charge->charge_type ?? $charge->tipo ?? 'N/A';
    $paymentMethod = $charge->forma_pago ?? $charge->payment_method ?? $charge->metodo_pago ?? 'N/A';
    $chargeObservation = $charge->observacion ?? $charge->observaciones ?? '';

    $clientName = $charge->cliente
        ?? $charge->cliente_nombre
        ?? $charge->client_name
        ?? '';

    $userName = $charge->usuario
        ?? $charge->usuario_genero
        ?? $charge->usuario_registro
        ?? 'USUARIO SISTEMA';

    $officeName = $charge->oficina_recibe ?? 'N/A';

    $contractRef = $contract->numero_referencia ?? $contract->folio ?? '';
    $contractStatus = $contract->estado ?? $contract->estado_nombre ?? '';
    $contractPaymentType = $contract->tipo_pago ?? $paymentType ?? '';
    $contractAmount = (float) ($contract->importe ?? $contractTotal);
    $contractFinancedBalance = (float) ($contract->saldo_financiado ?? $financedBalance);
    $contractMonthlyAmount = (float) ($contract->cuota_mensual ?? $monthlyAmount);

    $emissionDateTime = $emittedAt ?? now();

    $createdDateTime = null;
    try {
        $createdDateTime = !empty($chargeCreatedAt)
            ? \Carbon\Carbon::parse($chargeCreatedAt)
            : null;
    } catch (\Throwable $e) {
        $createdDateTime = null;
    }

    $conceptText = trim($chargeType);

    if (!empty($chargeObservation)) {
        $conceptText .= ' - ' . trim($chargeObservation);
    }

    $statusCss = function ($statusClass, $statusLabel = '') {
        $statusClass = mb_strtolower(trim((string) $statusClass));
        $statusLabel = mb_strtoupper(trim((string) $statusLabel));

        if ($statusClass === 'success' || in_array($statusLabel, ['PAGADO', 'PAGADA', 'LIQUIDADO', 'LIQUIDADA', 'CUBIERTO', 'CUBIERTA', 'ADELANTADO'], true)) {
            return 'status-paid';
        }

        if ($statusClass === 'danger' || in_array($statusLabel, ['VENCIDO', 'VENCIDA', 'ATRASADO', 'ATRASADO PARCIAL'], true)) {
            return 'status-danger';
        }

        if ($statusClass === 'muted' || in_array($statusLabel, ['CANCELADO', 'CANCELADA'], true)) {
            return 'status-muted';
        }

        return 'status-pending';
    };
@endphp

<style>
    .schedule-page-break {
        page-break-before: always;
    }
</style>

<div class="keep-together">
    <div class="section-title">Datos del recibo</div>

    <div class="card">
        <table class="meta-table">
            <tr>
                <td style="width: 33.333%;">
                    <div class="label">Folio recibo</div>
                    <div class="value">{{ $chargeReference }}</div>
                </td>

                <td style="width: 33.333%;">
                    <div class="label">Fecha del cobro</div>
                    <div class="value">{{ $chargeDate ?: 'N/A' }}</div>
                </td>

                <td style="width: 33.333%;">
                    <div class="label">Hora emisión</div>
                    <div class="value">{{ $emissionDateTime->format('d/m/Y H:i:s') }}</div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="label">Cliente</div>
                    <div class="value">{{ $clientName ?: 'N/A' }}</div>
                </td>

                <td>
                    <div class="label">Contrato</div>
                    <div class="value">{{ $contractRef ?: 'N/A' }}</div>
                </td>

                <td>
                    <div class="label">Tipo de cobro</div>
                    <div class="value">{{ $chargeType ?: 'N/A' }}</div>
                </td>
            </tr>

            <tr>
                <td>
                    <div class="label">Forma de pago</div>
                    <div class="value">{{ $paymentMethod ?: 'N/A' }}</div>
                </td>

                <td>
                    <div class="label">Oficina</div>
                    <div class="value">{{ $officeName ?: 'N/A' }}</div>
                </td>

                <td>
                    <div class="label">Recibió</div>
                    <div class="value">{{ $userName ?: 'USUARIO SISTEMA' }}</div>
                </td>
            </tr>

            @if($createdDateTime)
                <tr>
                    <td>
                        <div class="label">Registrado</div>
                        <div class="value">{{ $createdDateTime->format('d/m/Y H:i:s') }}</div>
                    </td>

                    <td colspan="2">
                        <div class="label">Movimiento generado por</div>
                        <div class="value">{{ $userName ?: 'USUARIO SISTEMA' }}</div>
                    </td>
                </tr>
            @endif

            <tr>
                <td colspan="3">
                    <div class="label">Concepto</div>
                    <div class="value">{{ $conceptText ?: 'COBRO' }}</div>
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
                    <div class="small">Monto aplicado</div>
                    <div class="big">${{ number_format($chargeAmount, 2) }}</div>
                </div>
            </td>

            <td>
                <div class="summary-box danger">
                    <div class="small">Recargo</div>
                    <div class="big">${{ number_format($chargeLateFee, 2) }}</div>
                </div>
            </td>

            <td>
                <div class="summary-box">
                    <div class="small">Total recibo</div>
                    <div class="big">${{ number_format($chargeTotal, 2) }}</div>
                </div>
            </td>

            <td>
                <div class="summary-box">
                    <div class="small">Tipo contrato</div>
                    <div class="big" style="font-size: 8.2px;">{{ $contractPaymentType ?: 'N/A' }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

@if(!empty($contract))
    <div class="keep-together">
        <div class="section-title">Resumen del contrato</div>

        <table class="summary-table mb-12">
            <tr>
                <td>
                    <div class="summary-box">
                        <div class="small">Total contrato</div>
                        <div class="big">${{ number_format($contractTotal, 2) }}</div>
                    </div>
                </td>

                <td>
                    <div class="summary-box success">
                        <div class="small">Pagado capital</div>
                        <div class="big">${{ number_format($paidTotal, 2) }}</div>
                    </div>
                </td>

                <td>
                    <div class="summary-box warning">
                        <div class="small">Saldo</div>
                        <div class="big">${{ number_format($balance, 2) }}</div>
                    </div>
                </td>

                <td>
                    <div class="summary-box danger">
                        <div class="small">Recargos acum.</div>
                        <div class="big">${{ number_format($lateFeeTotal, 2) }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="card">
            <table class="meta-table">
                <tr>
                    <td style="width: 33.333%;">
                        <div class="label">Estado contrato</div>
                        <div class="value">{{ $contractStatus ?: 'N/A' }}</div>
                    </td>

                    <td style="width: 33.333%;">
                        <div class="label">Mensualidad</div>
                        <div class="value">${{ number_format($contractMonthlyAmount, 2) }}</div>
                    </td>

                    <td style="width: 33.333%;">
                        <div class="label">Avance</div>
                        <div class="value">{{ number_format($progressPercent, 2) }}%</div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <div class="label">Pagos totales</div>
                        <div class="value">{{ $totalPayments }}</div>
                    </td>

                    <td>
                        <div class="label">Pagados</div>
                        <div class="value">{{ $paidPayments }}</div>
                    </td>

                    <td>
                        <div class="label">Pendientes</div>
                        <div class="value">{{ $pendingPayments }}</div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <div class="label">Pago inicial</div>
                        <div class="value">${{ number_format($initialPayment, 2) }}</div>
                    </td>

                    <td>
                        <div class="label">Saldo financiado</div>
                        <div class="value">${{ number_format($contractFinancedBalance, 2) }}</div>
                    </td>

                    <td>
                        <div class="label">Total recibido real</div>
                        <div class="value">${{ number_format($realCollectedTotal, 2) }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if($includeSchedule && !empty($scheduleGrid))
        <div class="schedule-page-break"></div>

        <div class="section-title">Calendario de pagos adjunto</div>

        <table class="schedule-two-col-table">
            <tr>
                <td>
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th style="width: 25px;">#</th>
                                <th>Vence</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Pagado</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse(($scheduleColumns['left'] ?? []) as $row)
                                @php
                                    $installmentNumber = data_get($row, 'installment_number', '');
                                    $dueDate = data_get($row, 'due_date', '');
                                    $amount = (float) data_get($row, 'amount', 0);
                                    $amountPaid = (float) data_get($row, 'amount_paid', 0);
                                    $status = data_get($row, 'status_label', data_get($row, 'status', 'PENDIENTE'));
                                    $rowStatusClass = data_get($row, 'status_class', '');
                                    $cssClass = $statusCss($rowStatusClass, $status);
                                @endphp

                                <tr>
                                    <td>{{ $installmentNumber }}</td>
                                    <td>{{ $dueDate }}</td>
                                    <td class="text-right">${{ number_format($amount, 2) }}</td>
                                    <td class="text-right">${{ number_format($amountPaid, 2) }}</td>
                                    <td class="{{ $cssClass }}">{{ $status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Sin mensualidades</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>

                <td>
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th style="width: 25px;">#</th>
                                <th>Vence</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Pagado</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse(($scheduleColumns['right'] ?? []) as $row)
                                @php
                                    $installmentNumber = data_get($row, 'installment_number', '');
                                    $dueDate = data_get($row, 'due_date', '');
                                    $amount = (float) data_get($row, 'amount', 0);
                                    $amountPaid = (float) data_get($row, 'amount_paid', 0);
                                    $status = data_get($row, 'status_label', data_get($row, 'status', 'PENDIENTE'));
                                    $rowStatusClass = data_get($row, 'status_class', '');
                                    $cssClass = $statusCss($rowStatusClass, $status);
                                @endphp

                                <tr>
                                    <td>{{ $installmentNumber }}</td>
                                    <td>{{ $dueDate }}</td>
                                    <td class="text-right">${{ number_format($amount, 2) }}</td>
                                    <td class="text-right">${{ number_format($amountPaid, 2) }}</td>
                                    <td class="{{ $cssClass }}">{{ $status }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Sin mensualidades</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @endif
@endif

<div class="signature-wrap">
    <div class="signature-box">
        <div class="signature-line">
            RECIBIÓ
            @if(!empty($userName))
                <br>{{ mb_strtoupper($userName) }}
            @endif
        </div>
    </div>

    <div class="signature-box">
        <div class="signature-line">
            ENTREGÓ
            @if(!empty($clientName))
                <br>{{ mb_strtoupper($clientName) }}
            @endif
        </div>
    </div>
</div>
@endsection