<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DevelopmentSummaryReportExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithEvents,
    ShouldAutoSize
{
    protected Collection $rows;
    protected ?string $startDate;
    protected ?string $endDate;

    private const COLOR_RED_DARK = 'D9042B';
    private const COLOR_RED = 'F20505';
    private const COLOR_WHITE = 'FFFFFF';
    private const COLOR_BLACK = '0D0D0D';
    private const COLOR_GRAY = '676767';

    private const COLOR_LIBRE_BG = 'FFFFFF';
    private const COLOR_LIBRE_TEXT = '0D0D0D';

    private const COLOR_APARTADO_BG = 'FFF3CD';
    private const COLOR_APARTADO_TEXT = '664D03';

    private const COLOR_OCUPADO_BG = 'F8D7DA';
    private const COLOR_OCUPADO_TEXT = '842029';

    public function __construct(array $rows, ?string $startDate = null, ?string $endDate = null)
    {
        $this->rows = collect($rows);
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            ['RESUMEN GENERAL DE LOTIFICACIONES'],
            ['Rango:', ($this->startDate ?: 'N/A') . ' al ' . ($this->endDate ?: 'N/A')],
            [],
            [
                '#',
                'Lotificación',
                'Total lotes',
                'Disponibles',
                'Apartados',
                'Vendidos',
            ],
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $row = (array) $row;

        return [
            $index,
            $row['lotificacion'] ?? '',
            (int) ($row['total'] ?? 0),
            (int) ($row['disponibles'] ?? 0),
            (int) ($row['apartados'] ?? 0),
            (int) ($row['vendidos'] ?? 0),
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
                    'color' => ['rgb' => self::COLOR_GRAY],
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $rowCount = $this->rows->count();

                $headerRow = 4;
                $firstDataRow = 5;
                $lastDataRow = $rowCount > 0
                    ? $firstDataRow + $rowCount - 1
                    : $firstDataRow;

                $totalRow = $lastDataRow + 1;

                // Título
                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('B2:F2');

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension($headerRow)->setRowHeight(24);

                // Anchos
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(38);
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(16);
                $sheet->getColumnDimension('F')->setWidth(16);

                // Encabezado de columnas
                $sheet->getStyle("A{$headerRow}:F{$headerRow}")->applyFromArray([
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
                    $sheet->getStyle("A{$firstDataRow}:F{$lastDataRow}")->applyFromArray([
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'D9D9D9'],
                            ],
                        ],
                    ]);

                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("C{$firstDataRow}:F{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Total lotes neutro
                    $sheet->getStyle("C{$firstDataRow}:C{$lastDataRow}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => self::COLOR_BLACK],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F7F7F7'],
                        ],
                    ]);

                    // Disponibles / Libres
                    $this->applyColumnStatusStyle(
                        $sheet,
                        "D{$firstDataRow}:D{$lastDataRow}",
                        self::COLOR_LIBRE_BG,
                        self::COLOR_LIBRE_TEXT
                    );

                    // Apartados
                    $this->applyColumnStatusStyle(
                        $sheet,
                        "E{$firstDataRow}:E{$lastDataRow}",
                        self::COLOR_APARTADO_BG,
                        self::COLOR_APARTADO_TEXT
                    );

                    // Vendidos / Ocupados
                    $this->applyColumnStatusStyle(
                        $sheet,
                        "F{$firstDataRow}:F{$lastDataRow}",
                        self::COLOR_OCUPADO_BG,
                        self::COLOR_OCUPADO_TEXT
                    );
                }

                // Totales
                $sheet->setCellValue("A{$totalRow}", '');
                $sheet->setCellValue("B{$totalRow}", 'TOTALES');
                $sheet->setCellValue("C{$totalRow}", $this->rows->sum('total'));
                $sheet->setCellValue("D{$totalRow}", $this->rows->sum('disponibles'));
                $sheet->setCellValue("E{$totalRow}", $this->rows->sum('apartados'));
                $sheet->setCellValue("F{$totalRow}", $this->rows->sum('vendidos'));

                $sheet->getStyle("A{$totalRow}:F{$totalRow}")->applyFromArray([
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
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => self::COLOR_RED],
                        ],
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => self::COLOR_RED],
                        ],
                    ],
                ]);

                $sheet->getStyle("B{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("C{$totalRow}:F{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Autofiltro y congelar encabezado
                $sheet->setAutoFilter("A{$headerRow}:F{$headerRow}");
                $sheet->freezePane('A5');

                // Marco del encabezado superior
                $sheet->getStyle('A1:F2')->applyFromArray([
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => self::COLOR_RED_DARK],
                        ],
                    ],
                ]);

                $sheet->getStyle('A:G')
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }

    private function applyColumnStatusStyle(
        Worksheet $sheet,
        string $range,
        string $bgColor,
        string $textColor
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $textColor],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $bgColor],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }
}