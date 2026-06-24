<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PdfReceiptService
{
    public function stream(string $view, array $data = [], string $filename = 'documento.pdf')
    {
        $data = $this->withDefaults($data);

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('letter', 'portrait');

        return $pdf->stream($filename);
    }

    public function download(string $view, array $data = [], string $filename = 'documento.pdf')
    {
        $data = $this->withDefaults($data);

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('letter', 'portrait');

        return $pdf->download($filename);
    }

    protected function withDefaults(array $data): array
    {
        $branding = $data['branding'] ?? $this->branding();
        $logoPath = $this->resolveLogoPath($branding['logo_path'] ?? null);

        return array_merge([
            'branding' => $branding,
            'palette' => $this->palette(),
            'logoPath' => $logoPath,
            'hasLogo' => !empty($logoPath) && file_exists($logoPath),
            'generatedAt' => now(),
        ], $data);
    }

    public function branding(): array
    {
        $default = [
            'company_name' => 'JD Inmobiliaria',
            'company_subtitle' => 'DOCUMENTOS OFICIALES',
            'logo_path' => 'assets/images/logo.png',
            'footer_text' => 'Este documento fue generado por el sistema.',
            'address_line' => '',
            'phone_line' => '',
        ];

        try {
            $value = DB::table('global_variables')
                ->where('nombre', 'BRANDING_PDF')
                ->value('valor');

            if (!$value) {
                return $default;
            }

            $json = is_array($value) ? $value : json_decode($value, true);

            if (!is_array($json)) {
                return $default;
            }

            return array_merge($default, $json);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function palette(): array
    {
        return [
            'primary' => '#111827',
            'secondary' => '#374151',
            'muted' => '#6B7280',
            'border' => '#D1D5DB',
            'light' => '#F9FAFB',
            'soft' => '#F3F4F6',
            'danger' => '#DC2626',
            'success' => '#16A34A',
            'warning' => '#F59E0B',
            'info' => '#2563EB',
            'white' => '#FFFFFF',
            'black' => '#000000',

            'dark' => '#111827',
            'gray' => '#6B7280',
            'blue' => '#2563EB',
        ];
    }

    public function resolveLogoPath(?string $logoRelativePath = null): ?string
    {
        $candidates = [];

        if ($logoRelativePath) {
            $candidates[] = public_path($logoRelativePath);
            $candidates[] = public_path(ltrim($logoRelativePath, '/'));
        }

        $candidates[] = public_path('assets/images/logo.png');
        $candidates[] = public_path('images/logo.png');

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function chargePaymentStats(int $contractId): array
    {
        $contract = DB::table('contracts as c')
            ->leftJoin('contract_payment_types as cpt', 'cpt.id', '=', 'c.contract_payment_type_id')
            ->where('c.id', $contractId)
            ->select([
                'c.id',
                'c.importe',
                'c.monto_pago_inicial',
                'c.saldo_financiado',
                'c.cuota_mensual',
                'c.meses',
                'cpt.nombre as tipo_pago',
            ])
            ->first();

        if (!$contract) {
            return $this->emptyStats();
        }

        $chargesQuery = DB::table('charges as ch')
            ->join('statuses as s', 's.id', '=', 'ch.status_id')
            ->where('ch.contract_id', $contractId)
            ->whereNull('ch.fecha_baja')
            ->where('s.clave', '!=', 'CANCELADO');

        $paidPrincipalTotal = (float) (clone $chargesQuery)->sum('ch.monto');
        $lateFeeTotal = (float) (clone $chargesQuery)->sum('ch.monto_recargo');
        $realCollectedTotal = $paidPrincipalTotal + $lateFeeTotal;

        $contractTotal = (float) ($contract->importe ?? 0);
        $initialPayment = (float) ($contract->monto_pago_inicial ?? 0);
        $financedBalance = (float) ($contract->saldo_financiado ?? 0);
        $monthlyAmount = (float) ($contract->cuota_mensual ?? 0);
        $months = (int) ($contract->meses ?? 0);

        $scheduleBase = DB::table('payment_schedules')
            ->where('contract_id', $contractId);

        $installmentsTotal = (int) (clone $scheduleBase)->count();

        /*
         * Ajuste solicitado:
         * Se cuentan como pagadas únicamente las mensualidades con status PAGADO o ADELANTADO.
         * PARCIAL, ATRASADO_PARCIAL y PENDIENTE no cuentan como pagadas.
         */
        $installmentsPaid = (int) (clone $scheduleBase)
            ->whereIn(DB::raw('UPPER(status)'), [
                'PAGADO',
                'ADELANTADO',
            ])
            ->count();

        $installmentsPending = max(0, $installmentsTotal - $installmentsPaid);

        $scheduleAmountTotal = (float) (clone $scheduleBase)->sum('amount');
        $schedulePaidTotal = (float) (clone $scheduleBase)->sum('amount_paid');
        $scheduleLateFeeTotal = (float) (clone $scheduleBase)->sum('late_fee_amount');

        $lots = DB::table('contract_lots as cl')
            ->join('lots as l', 'l.id', '=', 'cl.lot_id')
            ->where('cl.contract_id', $contractId)
            ->whereNull('l.fecha_baja')
            ->select('l.identificador', 'l.manzana')
            ->get()
            ->map(function ($lot) {
                return trim(($lot->manzana ? "Mza {$lot->manzana} " : "") . "Lote {$lot->identificador}");
            })
            ->implode(', ');

        return [
            'contract_id' => $contractId,
            'contract_total' => round($contractTotal, 2),
            'initial_payment' => round($initialPayment, 2),
            'financed_balance' => round($financedBalance, 2),
            'monthly_amount' => round($monthlyAmount, 2),
            'months' => $months,
            'payment_type' => (string) ($contract->tipo_pago ?? ''),

            'paid_total' => round($paidPrincipalTotal, 2),
            'paid_total_without_late_fee' => round($paidPrincipalTotal, 2),
            'late_fee_total' => round($lateFeeTotal, 2),
            'real_collected_total' => round($realCollectedTotal, 2),

            'balance' => round(max(0, $contractTotal - ($paidPrincipalTotal + $initialPayment)), 2),
            'progress_percent' => $contractTotal > 0
                ? round(min(100, (($paidPrincipalTotal + $initialPayment) / $contractTotal) * 100), 2)
                : 0,
                
            'lots_associated' => $lots,

            'installments_total' => $installmentsTotal,
            'installments_paid' => $installmentsPaid,
            'installments_pending' => $installmentsPending,

            'schedule_amount_total' => round($scheduleAmountTotal, 2),
            'schedule_paid_total' => round($schedulePaidTotal, 2),
            'schedule_late_fee_total' => round($scheduleLateFeeTotal, 2),
        ];
    }

    protected function emptyStats(): array
    {
        return [
            'contract_id' => null,
            'contract_total' => 0,
            'initial_payment' => 0,
            'financed_balance' => 0,
            'monthly_amount' => 0,
            'months' => 0,
            'payment_type' => '',
            'paid_total' => 0,
            'paid_total_without_late_fee' => 0,
            'late_fee_total' => 0,
            'real_collected_total' => 0,
            'balance' => 0,
            'progress_percent' => 0,
            'lots_associated' => '',
            'installments_total' => 0,
            'installments_paid' => 0,
            'installments_pending' => 0,
            'schedule_amount_total' => 0,
            'schedule_paid_total' => 0,
            'schedule_late_fee_total' => 0,
        ];
    }

    public function chargeScheduleGrid(int $contractId): array
    {
        $rows = DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->orderBy('due_date')
            ->get([
                'installment_number',
                'due_date',
                'amount',
                'amount_paid',
                'late_fee_amount',
                'late_fee_applied',
                'status',
            ]);

        return $rows->map(function ($row) {
            $amount = (float) ($row->amount ?? 0);
            $paid = (float) ($row->amount_paid ?? 0);
            $lateFee = (float) ($row->late_fee_amount ?? 0);
            $balance = max(0, ($amount + $lateFee) - $paid);

            return [
                'installment_number' => (int) ($row->installment_number ?? 0),
                'due_date' => $this->formatDate($row->due_date ?? null),
                'amount' => round($amount, 2),
                'amount_paid' => round($paid, 2),
                'late_fee_amount' => round($lateFee, 2),
                'late_fee_applied' => (bool) ($row->late_fee_applied ?? false),
                'balance' => round($balance, 2),
                'status' => (string) ($row->status ?? ''),
                'status_label' => $this->scheduleStatusLabel((string) ($row->status ?? '')),
                'status_class' => $this->scheduleStatusClass((string) ($row->status ?? '')),
            ];
        })->values()->all();
    }

    public function splitGridInTwoColumns(array $grid): array
    {
        $total = count($grid);

        if ($total === 0) {
            return [
                'left' => [],
                'right' => [],
            ];
        }

        $half = (int) ceil($total / 2);

        return [
            'left' => array_slice($grid, 0, $half),
            'right' => array_slice($grid, $half),
        ];
    }

    public function formatMoney(float|int|string|null $value): string
    {
        return '$' . number_format((float) ($value ?? 0), 2);
    }

    public function formatDate(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        try {
            return Carbon::parse($date)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }

    protected function scheduleStatusLabel(string $status): string
    {
        $status = mb_strtoupper(trim($status));

        return match ($status) {
            'PAGADO', 'PAGADA', 'LIQUIDADO', 'LIQUIDADA', 'CUBIERTO', 'CUBIERTA' => 'PAGADO',
            'ADELANTADO' => 'ADELANTADO',
            'VENCIDO', 'VENCIDA', 'ATRASADO' => 'ATRASADO',
            'ATRASADO_PARCIAL' => 'ATRASADO PARCIAL',
            'PARCIAL' => 'PARCIAL',
            'CANCELADO', 'CANCELADA' => 'CANCELADO',
            default => $status ?: 'PENDIENTE',
        };
    }

    protected function scheduleStatusClass(string $status): string
    {
        $status = mb_strtoupper(trim($status));

        return match ($status) {
            'PAGADO', 'PAGADA', 'LIQUIDADO', 'LIQUIDADA', 'CUBIERTO', 'CUBIERTA', 'ADELANTADO' => 'success',
            'VENCIDO', 'VENCIDA', 'ATRASADO', 'ATRASADO_PARCIAL' => 'danger',
            'PARCIAL' => 'warning',
            'CANCELADO', 'CANCELADA' => 'muted',
            default => 'pending',
        };
    }
}