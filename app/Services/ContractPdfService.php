<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ContractPdfService
{
    public function stream(string $view, array $data = [], string $filename = 'contrato.pdf')
    {
        $data['branding'] = $data['branding'] ?? $this->branding();

        $pdf = Pdf::loadView($view, $data)
            ->setPaper('letter', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $this->addPageNumbers($dompdf);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    protected function addPageNumbers($dompdf): void
    {
        $canvas = $dompdf->getCanvas();

        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
            $text = "Página {$pageNumber} de {$pageCount}";
            $font = $fontMetrics->getFont('Helvetica', 'normal');
            $fontSize = 8;

            $textWidth = $fontMetrics->getTextWidth($text, $font, $fontSize);

            /*
             * Alineado a la derecha respetando margen derecho.
             * 3 cm aprox = 85 pt.
             */
            $x = $canvas->get_width() - $textWidth - 85;

            /*
             * Dentro del área visible de impresión.
             */
            $y = $canvas->get_height() - 38;

            $canvas->text($x, $y, $text, $font, $fontSize, [0, 0, 0]);
        });
    }

    protected function branding(): array
    {
        $default = [
            'company_name' => 'JD Inmobiliaria',
            'company_subtitle' => 'DOCUMENTOS OFICIALES',
            'logo_path' => 'assets/images/logo.png',
            'footer_text' => 'Este documento fue generado por el sistema.',
            'address_line' => 'VISITANOS EN 3 ORIENTE #736 COL. RICARDO FLORES MAGON TEHUACAN PUEBLA.',
            'phone_line' => 'TELEFONO 238 289 0712',
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
}