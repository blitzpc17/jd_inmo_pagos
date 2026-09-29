<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplierReportExport implements FromArray, WithHeadings
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Proveedor',
            'Motivo / Descripción',
            'Lotificación',
            'Capital (Total)',
            'Enganche',
            'Abono a Capital',
            'Resto a Capital',
            'Meses',
            'Mensualidad'
        ];
    }
}
