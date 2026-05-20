<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document_type ?? 'Documento' }}</title>

    @php
        $branding = $branding ?? [];
        $palette = array_merge([
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

            /*
             * Compatibilidad con vistas anteriores.
             */
            'dark' => '#111827',
            'gray' => '#6B7280',
            'blue' => '#2563EB',
        ], $palette ?? []);

        $logoPath = $logoPath ?? null;

        if (empty($logoPath)) {
            $logoRelativePath = $branding['logo_path'] ?? 'assets/images/logo.png';
            $logoPath = public_path($logoRelativePath);

            if (!file_exists($logoPath)) {
                $logoPath = public_path('assets/images/logo.png');
            }

            if (!file_exists($logoPath)) {
                $logoPath = public_path('images/logo.png');
            }
        }

        $hasLogo = !empty($logoPath) && file_exists($logoPath);

        $companyName = $branding['company_name'] ?? 'JD Inmobiliaria';
        $companySubtitle = $branding['company_subtitle'] ?? 'DOCUMENTOS OFICIALES';
        $footerText = $branding['footer_text'] ?? 'Este documento fue generado por el sistema.';
        $addressLine = $branding['address_line'] ?? 'VISITANOS EN 3 ORIENTE #736 COL. RICARDO FLORES MAGON TEHUACAN PUEBLA.';
        $phoneLine = $branding['phone_line'] ?? 'TELEFONO 238 289 0712';
    @endphp

    <style>
        @page {
            margin-top: 2cm;
            margin-bottom: 2.5cm;
            margin-left: 2.2cm;
            margin-right: 2.2cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: {{ $palette['black'] }};
            font-size: 10.5px;
            line-height: 1.35;
        }

        .watermark-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1000;
        }

        .watermark-table {
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }

        .watermark-cell {
            width: 100%;
            height: 100%;
            text-align: center;
            vertical-align: middle;
        }

        .watermark-logo {
            width: 430px;
            opacity: 0.055;
        }

        .receipt-footer {
            position: fixed;
            bottom: -1.35cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.6px;
            font-weight: bold;
            line-height: 1.25;
            color: {{ $palette['black'] }};
        }

        .page-counter {
            position: fixed;
            right: 0;
            bottom: -1.05cm;
            font-size: 8px;
            color: {{ $palette['muted'] }};
            font-weight: bold;
        }

        .doc-header {
            width: 100%;
            margin-bottom: 10px;
        }

        .doc-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .doc-header-left {
            width: 35%;
            vertical-align: middle;
            text-align: left;
            height: 58px;
        }

        .doc-header-center {
            width: 35%;
            vertical-align: middle;
            text-align: center;
        }

        .doc-header-right {
            width: 30%;
            vertical-align: middle;
            text-align: right;
            height: 58px;
        }

        .logo {
            max-height: 54px;
            max-width: 165px;
        }

        .logo-fallback {
            font-size: 16px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
            line-height: 1.1;
        }

        .document-type {
            font-size: 15px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
            text-transform: uppercase;
            line-height: 1.1;
        }

        .document-subtitle {
            margin-top: 4px;
            font-size: 8.5px;
            font-weight: bold;
            color: {{ $palette['muted'] }};
            text-transform: uppercase;
        }

        .folio-box {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid {{ $palette['black'] }};
            padding: 6px 10px;
            vertical-align: middle;
            background: rgba(255,255,255,.92);
            min-width: 120px;
            text-align: center;
        }

        .header-rule {
            height: 2px;
            background: {{ $palette['primary'] }};
            margin-top: 8px;
        }

        .section-title {
            background: {{ $palette['primary'] }};
            color: #fff;
            padding: 7px 10px;
            border-radius: 7px;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .card {
            border: 1px solid {{ $palette['border'] }};
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            background: rgba(255,255,255,0.94);
        }

        .meta-table,
        .detail-table,
        .summary-table,
        .schedule-two-col-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td {
            padding: 6px 7px;
            vertical-align: top;
        }

        .label {
            color: {{ $palette['muted'] }};
            font-size: 8.5px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .value {
            font-size: 10.5px;
            font-weight: bold;
            color: {{ $palette['black'] }};
            word-break: break-word;
        }

        .summary-table td {
            width: 25%;
            vertical-align: top;
            padding-right: 7px;
        }

        .summary-table td:last-child {
            padding-right: 0;
        }

        .summary-box {
            border-radius: 9px;
            background: rgba(249,250,251,0.96);
            border: 1px solid {{ $palette['border'] }};
            border-left: 5px solid {{ $palette['info'] }};
            padding: 8px 9px;
            min-height: 46px;
        }

        .summary-box.success {
            border-left-color: {{ $palette['success'] }};
        }

        .summary-box.warning {
            border-left-color: {{ $palette['warning'] }};
        }

        .summary-box.danger {
            border-left-color: {{ $palette['danger'] }};
        }

        .summary-box .small {
            color: {{ $palette['muted'] }};
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .summary-box .big {
            margin-top: 4px;
            font-size: 13.5px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
        }

        .detail-table {
            page-break-inside: auto;
        }

        .detail-table thead {
            display: table-header-group;
        }

        .detail-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .detail-table th {
            background: {{ $palette['primary'] }};
            color: #fff;
            padding: 5px;
            font-size: 8px;
            text-align: left;
            text-transform: uppercase;
        }

        .detail-table td {
            border-bottom: 1px solid #ececec;
            padding: 5px;
            font-size: 8px;
            background: rgba(255,255,255,0.92);
        }

        .schedule-two-col-table td {
            width: 50%;
            vertical-align: top;
        }

        .schedule-two-col-table td:first-child {
            padding-right: 7px;
        }

        .schedule-two-col-table td:last-child {
            padding-left: 7px;
        }

        .status-paid {
            background: #eaf8ee !important;
            color: #0b7a35;
            font-weight: bold;
            text-align: center;
        }

        .status-pending {
            background: #fff7ed !important;
            color: #b45309;
            font-weight: bold;
            text-align: center;
        }

        .status-danger {
            background: #fef2f2 !important;
            color: {{ $palette['danger'] }};
            font-weight: bold;
            text-align: center;
        }

        .status-muted {
            background: #f3f4f6 !important;
            color: {{ $palette['muted'] }};
            font-weight: bold;
            text-align: center;
        }

        .signature-wrap {
            margin-top: 28px;
            page-break-inside: avoid;
            text-align: center;
        }

        .signature-box {
            width: 42%;
            display: inline-block;
            vertical-align: top;
            text-align: center;
            margin-right: 8%;
        }

        .signature-box:last-child {
            margin-right: 0;
        }

        .signature-line {
            margin-top: 48px;
            border-top: 1px solid {{ $palette['black'] }};
            padding-top: 10px;
            font-size: 9px;
            font-weight: bold;
        }

        .keep-together {
            page-break-inside: avoid;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mb-8 {
            margin-bottom: 8px;
        }

        .mb-12 {
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    @if($hasLogo)
        <div class="watermark-wrapper">
            <table class="watermark-table">
                <tr>
                    <td class="watermark-cell">
                        <img src="{{ $logoPath }}" class="watermark-logo">
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="page-counter">
        <script type="text/php">
            if (isset($pdf)) {
                $pdf->page_text(506, 744, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 8, array(0,0,0));
            }
        </script>
    </div>

    <div class="receipt-footer">
        {{ $addressLine }}<br>
        {{ $phoneLine }}
    </div>

    <div class="doc-header">
        <table class="doc-header-table">
            <tr>
                <td class="doc-header-left">
                    @if($hasLogo)
                        <img class="logo" src="{{ $logoPath }}">
                    @else
                        <div class="logo-fallback">{{ $companyName }}</div>
                    @endif
                </td>

                <td class="doc-header-center">
                    <div class="document-type">{{ $document_type ?? 'DOCUMENTO OFICIAL' }}</div>
                    <div class="document-subtitle">{{ $companySubtitle }}</div>
                </td>

                <td class="doc-header-right">
                    <span class="folio-box">
                        FOLIO: {{ $folio ?? 'S/F' }}
                    </span>
                </td>
            </tr>
        </table>

        <div class="header-rule"></div>
    </div>

    @yield('content')
</body>
</html>