<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document_type ?? 'Documento' }}</title>
    <style>
        @page { margin: 28px 36px 58px 36px; }

        body{
            font-family: DejaVu Sans, sans-serif;
            color: {{ $palette['dark'] }};
            font-size: 11px;
        }

        .page-counter{
            position: fixed;
            top: 10px;
            right: 0;
            font-size: 10px;
            color: {{ $palette['gray'] }};
            font-weight: bold;
        }

        .watermark{
            position: fixed;
            top: 240px;
            left: 90px;
            width: 420px;
            text-align: center;
            opacity: 0.06;
        }

        .watermark img{
            width: 100%;
            height: auto;
        }

        footer{
            position: fixed;
            bottom: -26px;
            left: 0;
            right: 0;
            height: 34px;
            border-top: 1px solid {{ $palette['gray'] }};
            color: {{ $palette['gray'] }};
            font-size: 9px;
            padding-top: 6px;
        }

        .doc-header{
            width: 100%;
            margin-bottom: 4px; /*tenia 14*/
        }

        .doc-header-table{
            width: 100%;
            border-collapse: collapse;
        }

        .doc-header-left{
            width: 72%;
            vertical-align: top;
        }

        .doc-header-right{
            width: 28%;
            vertical-align: top;
            text-align: center;            
        }

        .logo{
            width: 190px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 4px;
        }

        .logo-fallback{
            font-size: 26px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .company-name{
            margin: 0;
            font-size: 21px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
            line-height: 1.1;
        }

        .document-type{
            margin-top: 3px;
            font-size: 15px;
            font-weight: bold;
            color: {{ $palette['dark'] }};
            line-height: 1.2;
        }

        .brand-contact{
            font-size: 9px;
            color: {{ $palette['dark'] }};
            line-height: 1.3;
            margin-top: 3px;
        }

        .folio-label{
            font-size: 9px;
            text-transform: uppercase;
            color: {{ $palette['gray'] }};
            font-weight: bold;
            margin-bottom: 4px;
        }

        .folio-number{
            font-size: 17px;
            font-weight: bold;
            color: {{ $palette['gray'] }};
            border: 2px solid {{ $palette['gray'] }};
            border-radius: 10px;
            padding: 9px 12px;
            line-height: 1.25;
            display: inline-block;
            min-width: 150px;
            text-align: center;
            background: rgba(255,255,255,0.82);
        }

        .header-rule{
            margin-top: 8px;
            height: 3px;
            background: {{ $palette['primary'] }};
            border-radius: 2px;
        }

        .section-title{
            background: linear-gradient(90deg, {{ $palette['primary'] }}, {{ $palette['blue'] }});
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .card{
            border: 1px solid #dcdcdc;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 4px; /*tenia 14px */
            background: rgba(255,255,255,0.94);
        }

        .meta-table,
        .detail-table,
        .summary-table{
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td{
            padding: 6px 8px;
            vertical-align: top;
        }

        .label{
            color: {{ $palette['gray'] }};
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .value{
            font-size: 11px;
            font-weight: bold;
            color: {{ $palette['dark'] }};
        }

        .summary-table td{
            width: 33.3333%;
            vertical-align: top;
            padding-right: 10px;
        }

        .summary-table td:last-child{
            padding-right: 0;
        }

        .summary-box{
            border-radius: 10px;
            background: rgba(250,250,250,0.97);
            border-left: 5px solid {{ $palette['blue'] }};
            padding: 10px 12px;
            min-height: 56px;
        }

        .summary-box .small{
            color: {{ $palette['gray'] }};
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .summary-box .big{
            margin-top: 4px;
            font-size: 17px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
        }

        .detail-table{
            page-break-inside: auto;
        }

        .detail-table thead{
            display: table-header-group;
        }

        .detail-table tr{
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .detail-table th{
            background: {{ $palette['dark'] }};
            color: #fff;
            padding: 6px;
            font-size: 9px;
            text-align: left;
        }

        .detail-table td{
            border-bottom: 1px solid #ececec;
            padding: 6px;
            font-size: 9px;
            background: rgba(255,255,255,0.92);
        }

        .status-paid{
            background: #eaf8ee !important;
            color: #0b7a35;
            font-weight: bold;
            text-align: center;
        }

        .status-pending{
            background: #fff1f1 !important;
            color: {{ $palette['danger'] }};
            font-weight: bold;
            text-align: center;
        }

        .signature-wrap{
            margin-top: 22px;
            page-break-inside: avoid;
        }

        .signature-box{
            width: 42%;
            display: inline-block;
            vertical-align: top;
            text-align: center;
            margin-right: 8%;
        }

        .signature-box:last-child{
            margin-right: 0;
        }

        .signature-line{
            margin-top: 40px;
            border-top: 1px solid {{ $palette['dark'] }};
            padding-top: 6px;
            font-size: 10px;
            font-weight: bold;
        }

        .keep-together{
            page-break-inside: avoid;
        }

        .text-right{ text-align: right; }
        .mb-18{ margin-bottom: 18px; }
    </style>
</head>
<body>
    @if(!empty($branding['logo_path']) && file_exists($branding['logo_path']))
        <div class="watermark">
            <img src="{{ $branding['logo_path'] }}">
        </div>
    @endif

    <div class="page-counter">
        <script type="text/php">
            if (isset($pdf)) {
                $pdf->page_text(490, 14, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 9, array(103/255,103/255,103/255));
            }
        </script>
    </div>

    <div class="doc-header">
        <table class="doc-header-table">
            <tr>
                <td class="doc-header-left">
                    @if(!empty($branding['logo_path']) && file_exists($branding['logo_path']))
                        <img class="logo" src="{{ $branding['logo_path'] }}">
                    @else
                        <div class="logo-fallback">{{ $branding['company_name'] ?? 'JD Inmobiliaria' }}</div>
                    @endif

                    <div class="document-type">{{ $document_type ?? 'DOCUMENTO OFICIAL' }}</div>
                    <div class="brand-contact">VISITANOS EN 3 ORIENTE #736 VOL. RICARDO FLORES MAGON TEHUACAN PUEBLA.</div>
                    <div class="brand-contact">TELEFONO 238 289 0712</div>
                </td>

                <td class="doc-header-right">
                    <div class="folio-label">Folio del documento</div>
                    <div class="folio-number">{{ $folio ?? 'S/F' }}</div>
                </td>
            </tr>
        </table>

        <div class="header-rule"></div>
    </div>

    @yield('content')

    <footer>
        {{ $branding['footer_text'] }}
    </footer>
</body>
</html>