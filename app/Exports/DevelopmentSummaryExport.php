<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class DevelopmentSummaryExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
            'VENDIDOS',
            'APARTADOS',
            'DISPONIBLES',
            'TOTAL',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Insertar título arriba
                $sheet->insertNewRowBefore(1, 2);
                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', 'RESUMEN GENERAL DE LOTIFICACIONES');

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A1')->getAlignment()->setVertical('center');

                // Encabezados reales quedan en fila 3
                $sheet->getStyle('A3:E3')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A3:E3')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A3:E3')->getAlignment()->setVertical('center');

                // LOTIFICACION
                $sheet->getStyle('A3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF676767');
                $sheet->getStyle('A3')->getFont()->getColor()->setARGB('FFFFFFFF');

                // VENDIDOS -> rojo
                $sheet->getStyle('B3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF20505');
                $sheet->getStyle('B3')->getFont()->getColor()->setARGB('FFFFFFFF');

                // APARTADOS -> amarillo
                $sheet->getStyle('C3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFD54F');
                $sheet->getStyle('C3')->getFont()->getColor()->setARGB('FF0D0D0D');

                // DISPONIBLES -> blanco
                $sheet->getStyle('D3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFFFFF');
                $sheet->getStyle('D3')->getFont()->getColor()->setARGB('FF0D0D0D');

                // TOTAL -> oscuro neutro
                $sheet->getStyle('E3')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF0D0D0D');
                $sheet->getStyle('E3')->getFont()->getColor()->setARGB('FFFFFFFF');

                $highestRow = $sheet->getHighestRow();

                // Bordes generales
                $sheet->getStyle("A3:E{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle('thin');

                // Centrar numéricos
                $sheet->getStyle("B4:E{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal('center');

                // Colores suaves en celdas de datos
                if ($highestRow >= 4) {
                    // Vendidos
                    $sheet->getStyle("B4:B{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFE5E5');

                    // Apartados
                    $sheet->getStyle("C4:C{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFF7CC');

                    // Disponibles
                    $sheet->getStyle("D4:D{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFFFFFFF');

                    // Total
                    $sheet->getStyle("E4:E{$highestRow}")
                        ->getFill()->setFillType('solid')->getStartColor()->setARGB('FFF3F3F3');

                    // Texto negrita números
                    $sheet->getStyle("B4:E{$highestRow}")->getFont()->setBold(true);
                }

                // Alturas
                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(3)->setRowHeight(22);
            },
        ];
    }
}