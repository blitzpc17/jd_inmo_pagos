<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document_type ?? 'Documento' }}</title>
    <style>
        @page { margin: 145px 36px 90px 36px; }

        body{
            font-family: DejaVu Sans, sans-serif;
            color: {{ $palette['dark'] }};
            font-size: 11px;
            position: relative;
        }

        .watermark{
            position: fixed;
            top: 240px;
            left: 50px;
            width: 520px;
            text-align: center;
            opacity: 0.07;
            z-index: -1000;
        }

        .watermark img{
            width: 100%;
            height: auto;
        }

        header{
            position: fixed;
            top: -128px;
            left: 0;
            right: 0;
            height: 118px;
            border-bottom: 4px solid {{ $palette['primary'] }};
        }

        footer{
            position: fixed;
            bottom: -68px;
            left: 0;
            right: 0;
            height: 52px;
            border-top: 2px solid {{ $palette['gray'] }};
            font-size: 10px;
            color: {{ $palette['gray'] }};
        }

        .header-left{
            float: left;
            width: 73%;
        }

        .header-right{
            float: right;
            width: 24%;
            text-align: right;
        }

        .logo{
            width: 250px;
            height: 62px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .brand-title{
            margin: 0;
            font-size: 23px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
            line-height: 1.1;
        }

        .document-type{
            margin-top: 4px;
            font-size: 15px;
            font-weight: bold;
            color: {{ $palette['dark'] }};
            line-height: 1.2;
        }

        .brand-contact{
            font-size: 10px;
            color: {{ $palette['dark'] }};
            line-height: 1.35;
            margin-top: 4px;
        }

        .folio-label{
            font-size: 10px;
            text-transform: uppercase;
            color: {{ $palette['gray'] }};
            font-weight: bold;
            margin-bottom: 4px;
        }

        .folio-number{
            font-size: 17px;
            font-weight: bold;
            color: {{ $palette['danger'] }};
            border: 2px solid {{ $palette['danger'] }};
            border-radius: 10px;
            padding: 10px 12px;
            line-height: 1.25;
            display: inline-block;
            min-width: 150px;
            text-align: center;
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
            margin-bottom: 14px;
            background: rgba(255,255,255,0.92);
        }

        .meta-table, .detail-table, .summary-table{
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

        .detail-table th{
            background: {{ $palette['dark'] }};
            color: #fff;
            padding: 7px;
            font-size: 10px;
            text-align: left;
        }

        .detail-table td{
            border-bottom: 1px solid #e9e9e9;
            padding: 7px;
            font-size: 10px;
            background: rgba(255,255,255,0.90);
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
            background: rgba(250,250,250,0.96);
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
            margin-top: 26px;
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
            margin-top: 42px;
            border-top: 1px solid {{ $palette['dark'] }};
            padding-top: 6px;
            font-size: 10px;
            font-weight: bold;
        }

        .text-right{ text-align: right; }
        .text-center{ text-align: center; }
        .mb-18{ margin-bottom: 18px; }
    </style>
</head>
<body>
    @if(!empty($branding['logo_path']) && file_exists($branding['logo_path']))
        <div class="watermark">
            <img src="{{ $branding['logo_path'] }}">
        </div>
    @endif

    <header>
        <div class="header-left">
            @if(!empty($branding['logo_path']) && file_exists($branding['logo_path']))
                <img class="logo" src="{{ $branding['logo_path'] }}">
            @endif

            <h1 class="brand-title">{{ $branding['company_name'] }}</h1>
            <div class="document-type">{{ $document_type ?? 'DOCUMENTO OFICIAL' }}</div>

            <div class="brand-contact">VISITANOS EN 3 ORIENTE #736 VOL. RICARDO FLORES MAGON TEHUACAN PUEBLA.</div>
            <div class="brand-contact">TELEFONO 238 289 0712</div>
        </div>

        <div class="header-right">
            <div class="folio-label">Folio del documento</div>
            <div class="folio-number">{{ $folio ?? 'S/F' }}</div>
        </div>
    </header>

    <footer>
        <div style="padding-top:10px;">
            <div>{{ $branding['footer_text'] }}</div>
            <div>
                <script type="text/php">
                    if (isset($pdf)) {
                        $pdf->page_text(475, 18, "Hoja {PAGE_NUM} de {PAGE_COUNT}", null, 9);
                    }
                </script>
            </div>
        </div>
    </footer>

    @yield('content')
</body>
</html>