<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class MonthlyCollectionReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected array $rows;
    protected string $monthName;
    protected int $year;

    public function __construct(array $rows, string $monthName, int $year)
    {
        $this->rows = $rows;
        $this->monthName = $monthName;
        $this->year = $year;
    }

    public function collection()
    {
        return new Collection($this->rows);
    }

    public function headings(): array
    {
        return [
            'OFICINA',
            'LOTIFICACION',
            'LOTE',
            'SOCIOS',
            'NOMBRE DEL CLIENTE',
            'NUM',
            'MENSUALIDAD',
            'REAL PAGADO ' . $this->monthName,
            'APARTADOS/ENGANCHES',
            'COBRO DE RECARGO',
            'FOLIO',
            'OBSERVACION',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:L1');
                $sheet->setCellValue('A1', 'REPORTE MENSUAL DE COBROS - ' . $this->monthName . ' ' . $this->year);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

                $headerRange = 'A3:L3';

                $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
                $sheet->getStyle($headerRange)->getAlignment()->setVertical('center');
                $sheet->getStyle($headerRange)->getFill()->setFillType('solid')->getStartColor()->setARGB('FF0D0D0D');
                $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

                $sheet->getStyle('G3:H3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF0511F2');
                $sheet->getStyle('I3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFD54F');
                $sheet->getStyle('I3')->getFont()->getColor()->setARGB('FF0D0D0D');
                $sheet->getStyle('J3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD9042B');

                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A3:L{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle('thin');

                $sheet->getStyle("G4:J{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('$#,##0.00');

                $sheet->getStyle("G4:J{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal('right');

                $sheet->getStyle("A4:F{$highestRow}")
                    ->getAlignment()
                    ->setVertical('top');

                $sheet->getStyle("K4:L{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical('top');

                // Anchos específicos
                $sheet->getColumnDimension('D')->setWidth(40);
                $sheet->getStyle("D4:D{$highestRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(3)->setRowHeight(28);

                $sheet->freezePane('A4');
                $sheet->setAutoFilter("A3:L{$highestRow}");
            },
        ];
    }
}