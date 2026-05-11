<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PdfReceiptService
{
    protected array $palette = [
        'primary' => '#D9042B',
        'gray'    => '#676767',
        'blue'    => '#0511F2',
        'danger'  => '#F20505',
        'dark'    => '#0D0D0D',
    ];

    public function stream(string $view, array $data, string $filename = 'recibo.pdf')
    {
        $payload = array_merge($data, [
            'branding' => $this->branding(),
            'palette'  => $this->palette,
        ]);

        return Pdf::loadView($view, $payload)
            ->setPaper('letter', 'portrait')
            ->stream($filename);
    }

    public function download(string $view, array $data, string $filename = 'recibo.pdf')
    {
        $payload = array_merge($data, [
            'branding' => $this->branding(),
            'palette'  => $this->palette,
        ]);

        return Pdf::loadView($view, $payload)
            ->setPaper('letter', 'portrait')
            ->download($filename);
    }

    public function branding(): array
    {
        $default = [
            'company_name' => config('app.name', 'Sistema'),
            'company_subtitle' => 'Recibos y comprobantes oficiales',
            'logo_path' => public_path('images/logo.png'),
            'footer_text' => 'Documento generado automáticamente por el sistema.',
            'address_line' => 'VISITANOS EN 3 ORIENTE #736 VOL. RICARDO FLORES MAGON TEHUACAN PUEBLA.',
            'phone_line' => 'TELEFONO 238 289 0712',
        ];

        try {
            $row = DB::table('global_variables')
                ->where('nombre', 'BRANDING_PDF')
                ->first();

            if (!$row || empty($row->valor)) {
                return $default;
            }

            $json = is_array($row->valor) ? $row->valor : json_decode($row->valor, true);
            if (!is_array($json)) {
                return $default;
            }

            /*dd([
                'company_name' => $json['company_name'] ?? $default['company_name'],
                'company_subtitle' => $json['company_subtitle'] ?? $default['company_subtitle'],
                'logo_path' => !empty($json['logo_path']) ? public_path($json['logo_path']) : $default['logo_path'],
                'footer_text' => $json['footer_text'] ?? $default['footer_text'],
                'address_line' => $json['address_line'] ?? $default['address_line'],
                'phone_line' => $json['phone_line'] ?? $default['phone_line'],
            ]);*/
            return [
                'company_name' => $json['company_name'] ?? $default['company_name'],
                'company_subtitle' => $json['company_subtitle'] ?? $default['company_subtitle'],
                'logo_path' => !empty($json['logo_path']) ? public_path($json['logo_path']) : $default['logo_path'],
                'footer_text' => $json['footer_text'] ?? $default['footer_text'],
                'address_line' => $json['address_line'] ?? $default['address_line'],
                'phone_line' => $json['phone_line'] ?? $default['phone_line'],
            ];
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function chargePaymentStats(?int $contractId): array
    {
        if (!$contractId) {
            return [
                'total_payments' => 0,
                'paid_payments' => 0,
                'pending_payments' => 0,
            ];
        }

        $total = (int) DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->count();

        $paid = (int) DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->where('status', 'PAGADO')
            ->count();

        return [
            'total_payments' => $total,
            'paid_payments' => $paid,
            'pending_payments' => max(0, $total - $paid),
        ];
    }

    public function chargeScheduleGrid(?int $contractId)
    {
        if (!$contractId) {
            return collect();
        }

        return DB::table('payment_schedules')
            ->where('contract_id', $contractId)
            ->orderBy('installment_number')
            ->get([
                'installment_number',
                'due_date',
                'amount',
                'amount_paid',
                'late_fee_amount',
                'status',
            ]);
    }

    public function creditorPaymentStats(object $voucher): array
    {
        $mensualidad = (float) ($voucher->mensualidad ?? 0);
        $meses = (int) ($voucher->meses ?? 0);
        $pagado = (float) ($voucher->total_pagado ?? 0);

        $paidCount = 0;
        if ($mensualidad > 0) {
            $paidCount = (int) floor($pagado / $mensualidad);
        }

        $paidCount = min($meses, max(0, $paidCount));

        return [
            'total_payments' => $meses,
            'paid_payments' => $paidCount,
            'pending_payments' => max(0, $meses - $paidCount),
        ];
    }

    public function creditorScheduleGrid(object $voucher): array
    {
        $rows = [];
        $mensualidad = (float) ($voucher->mensualidad ?? 0);
        $meses = (int) ($voucher->meses ?? 0);
        $pagado = (float) ($voucher->total_pagado ?? 0);
        $fecha = \Carbon\Carbon::parse($voucher->fecha_registro);

        for ($i = 1; $i <= $meses; $i++) {
            $coveredAmount = $pagado - (($i - 1) * $mensualidad);
            $coveredAmount = max(0, min($mensualidad, $coveredAmount));

            $status = $coveredAmount >= $mensualidad ? 'PAGADO' : 'PENDIENTE';

            $rows[] = (object) [
                'installment_number' => $i,
                'due_date' => $fecha->copy()->addMonths($i - 1)->format('Y-m-d'),
                'amount' => $mensualidad,
                'amount_paid' => $coveredAmount,
                'status' => $status,
            ];
        }

        return $rows;
    }
   

    public function splitGridInTwoColumns($rows): array
    {
        $rows = collect($rows)->values();
        $half = (int) ceil($rows->count() / 2);

        return [
            'left' => $rows->slice(0, $half)->values(),
            'right' => $rows->slice($half)->values(),
        ];
    }

    public function shouldBreakCalendar($rows, int $threshold = 18): bool
    {
        return collect($rows)->count() > $threshold;
    }
}