<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class DevelopmentCollectionReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function collection()
    {
        return new Collection($this->rows);
    }

    public function headings(): array
    {
        return [
            'LOTIFICACION',
            'CONTRATOS',
            'ENGANCHES',
            'COBRADO',
            'RESTO POR COBRAR',
            'INGRESO MENSUAL',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:F1');
                $sheet->setCellValue('A1', 'REPORTE DE COBRANZA POR LOTIFICACION');

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                // Encabezados en fila 3
                $sheet->getStyle('A3:F3')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3:F3')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A3:F3')->getAlignment()->setVertical('center');

                // Colores
                $sheet->getStyle('A3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF676767');
                $sheet->getStyle('A3')->getFont()->getColor()->setARGB('FFFFFFFF');

                $sheet->getStyle('B3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF0511F2');
                $sheet->getStyle('B3')->getFont()->getColor()->setARGB('FFFFFFFF');

                $sheet->getStyle('C3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFD54F');
                $sheet->getStyle('C3')->getFont()->getColor()->setARGB('FF0D0D0D');

                $sheet->getStyle('D3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9042B');
                $sheet->getStyle('D3')->getFont()->getColor()->setARGB('FFFFFFFF');

                $sheet->getStyle('E3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF20505');
                $sheet->getStyle('E3')->getFont()->getColor()->setARGB('FFFFFFFF');

                $sheet->getStyle('F3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF0D0D0D');
                $sheet->getStyle('F3')->getFont()->getColor()->setARGB('FFFFFFFF');

                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A3:F{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle('thin');

                $sheet->getStyle("B4:F{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal('right');

                // Formato moneda
                $sheet->getStyle("B4:F{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                // Fondos suaves
                if ($highestRow >= 4) {
                    $sheet->getStyle("B4:B{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFE8F0FF');

                    $sheet->getStyle("C4:C{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFF7CC');

                    $sheet->getStyle("D4:D{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFE5EA');

                    $sheet->getStyle("E4:E{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFE5E5');

                    $sheet->getStyle("F4:F{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF3F3F3');
                }

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(3)->setRowHeight(22);
            },
        ];
    }
}