<?php

namespace App\Exports;

use App\Http\Controllers\DevelopmentCollectionReportController;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class DevelopmentCollectionReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithEvents,
    WithColumnFormatting,
    ShouldAutoSize
{
    protected string $startDate;
    protected string $endDate;
    protected ?int $partnerId;
    protected Collection $rows;

    private const COLOR_RED_DARK = 'D9042B';
    private const COLOR_RED = 'F20505';
    private const COLOR_WHITE = 'FFFFFF';
    private const COLOR_BLACK = '0D0D0D';
    private const COLOR_GRAY = '676767';

    public function __construct(string $startDate, string $endDate, ?int $partnerId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->partnerId = $partnerId;
        $this->rows = DevelopmentCollectionReportController::getReportRows($startDate, $endDate, $partnerId);
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            ['REPORTE DE COBRANZA POR LOTIFICACIÓN'],
            ['Rango:', $this->startDate . ' al ' . $this->endDate],
            [],
            [
                '#',
                'Lotificación',
                'Socios',
                'Contratos',
                'Enganches',
                'Cobrado',
                'Resto por cobrar',
                'Ingreso mensual',
            ],
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $row->lotificacion,
            $row->socios,
            (float) $row->contratos,
            (float) $row->enganches,
            (float) $row->cobrado,
            (float) $row->resto_por_cobrar,
            (float) $row->ingreso_mensual,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 16,
                    'color' => ['rgb' => self::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_RED_DARK],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
            2 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => self::COLOR_BLACK],
                ],
            ],
            4 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => self::COLOR_WHITE],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => self::COLOR_BLACK],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '"$"#,##0.00',
            'E' => '"$"#,##0.00',
            'F' => '"$"#,##0.00',
            'G' => '"$"#,##0.00',
            'H' => '"$"#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $rowCount = $this->rows->count();
                $headerRow = 4;
                $firstDataRow = 5;
                $lastDataRow = $rowCount > 0 ? $firstDataRow + $rowCount - 1 : $firstDataRow;
                $totalRow = $lastDataRow + 1;

                // Título
                $sheet->mergeCells('A1:H1');
                $sheet->getRowDimension(1)->setRowHeight(28);

                // Rango
                $sheet->mergeCells('B2:H2');

                // Anchos sugeridos
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(35);
                $sheet->getColumnDimension('C')->setWidth(50);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(18);
                $sheet->getColumnDimension('F')->setWidth(18);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(20);

                // Encabezados
                $sheet->getRowDimension($headerRow)->setRowHeight(24);

                $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => self::COLOR_WHITE],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::COLOR_BLACK],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => self::COLOR_GRAY],
                        ],
                    ],
                ]);

                // Datos
                if ($rowCount > 0) {
                    $sheet->getStyle("A{$firstDataRow}:H{$lastDataRow}")->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("D{$firstDataRow}:H{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                // Totales
                $sheet->mergeCells("A{$totalRow}:B{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTALES');
                $sheet->setCellValue("C{$totalRow}", $this->rows->sum('contratos'));
                $sheet->setCellValue("D{$totalRow}", $this->rows->sum('enganches'));
                $sheet->setCellValue("E{$totalRow}", $this->rows->sum('cobrado'));
                $sheet->setCellValue("F{$totalRow}", $this->rows->sum('resto_por_cobrar'));
                $sheet->setCellValue("G{$totalRow}", $this->rows->sum('ingreso_mensual'));

                $sheet->getStyle("A{$totalRow}:H{$totalRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => self::COLOR_WHITE],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::COLOR_RED_DARK],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("C{$totalRow}:H{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"$"#,##0.00');

                $sheet->getStyle("C{$totalRow}:H{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // Formato moneda en datos
                if ($rowCount > 0) {
                    $sheet->getStyle("D{$firstDataRow}:H{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode('"$"#,##0.00');
                }

                // Autofiltro
                $sheet->setAutoFilter("A{$headerRow}:H{$headerRow}");

                // Congelar encabezados
                $sheet->freezePane('A5');

                // Bordes generales superiores
                $sheet->getStyle("A{$headerRow}:H{$totalRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DEE2E6'],
                        ],
                    ],
                ]);

                // Wrap text para Socios (columna C)
                $sheet->getStyle("C{$firstDataRow}:C{$totalRow}")->getAlignment()->setWrapText(true);

                // Color del texto del rango
                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => [
                        'color' => ['rgb' => self::COLOR_GRAY],
                    ],
                ]);

                // Alineación general
                $sheet->getStyle('A:G')
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}