<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Recibo' }}</title>
    <style>
        @page { margin: 140px 36px 90px 36px; }

        body{
            font-family: DejaVu Sans, sans-serif;
            color: {{ $palette['dark'] }};
            font-size: 11px;
        }

        header{
            position: fixed;
            top: -122px;
            left: 0;
            right: 0;
            height: 112px;
            border-bottom: 4px solid {{ $palette['primary'] }};
        }

        footer{
            position: fixed;
            bottom: -70px;
            left: 0;
            right: 0;
            height: 55px;
            border-top: 2px solid {{ $palette['gray'] }};
            font-size: 10px;
            color: {{ $palette['gray'] }};
        }

        .header-left{
            float: left;
            width: 74%;
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
        }

        .brand-subtitle{
            font-size: 11px;
            color: {{ $palette['gray'] }};
            margin: 3px 0 6px 0;
        }

        .brand-contact{
            font-size: 10px;
            color: {{ $palette['dark'] }};
            line-height: 1.35;
        }

        .folio-box{
            display: inline-block;
            background: {{ $palette['blue'] }};
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
        }

        .folio-number{
            font-size: 18px;
            font-weight: bold;
            color: {{ $palette['danger'] }};
            border: 2px solid {{ $palette['danger'] }};
            border-radius: 10px;
            padding: 8px 10px;
        }

        .section-title{
            background: linear-gradient(90deg, {{ $palette['primary'] }}, {{ $palette['blue'] }});
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .card{
            border: 1px solid #dcdcdc;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 14px;
        }

        .meta-table, .detail-table{
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
        }

        .summary-box{
            width: 31.5%;
            display: inline-block;
            vertical-align: top;
            margin-right: 2%;
            padding: 10px;
            border-radius: 10px;
            background: #fafafa;
            border-left: 5px solid {{ $palette['blue'] }};
            box-sizing: border-box;
        }

        .summary-box:last-child{
            margin-right: 0;
        }

        .summary-box .big{
            font-size: 17px;
            font-weight: bold;
            color: {{ $palette['primary'] }};
        }

        .status-paid{
            background: #eaf8ee;
            color: #0b7a35;
            font-weight: bold;
            text-align: center;
        }

        .status-pending{
            background: #fff1f1;
            color: {{ $palette['danger'] }};
            font-weight: bold;
            text-align: center;
        }

        .signature-wrap{
            margin-top: 28px;
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
            margin-top: 45px;
            border-top: 1px solid {{ $palette['dark'] }};
            padding-top: 6px;
            font-size: 10px;
        }

        .text-right{ text-align: right; }
        .text-center{ text-align: center; }
        .mb-18{ margin-bottom: 18px; }
    </style>
</head>
<body>
    <header>
        <div class="header-left">
            @if(!empty($branding['logo_path']) && file_exists($branding['logo_path']))
                <img class="logo" src="{{ $branding['logo_path'] }}">
            @endif

            <h1 class="brand-title">{{ $branding['company_name'] }}</h1>
            <div class="brand-subtitle">{{ $branding['company_subtitle'] }}</div>
            <div class="brand-contact">{{ $branding['address_line'] }}</div>
            <div class="brand-contact">{{ $branding['phone_line'] }}</div>
        </div>

        <div class="header-right">
            <div class="folio-box">{{ $title ?? 'RECIBO OFICIAL' }}</div>
            @if(!empty($folio ?? null))
                <div class="folio-number">{{ $folio }}</div>
            @endif
        </div>
    </header>

    <footer>
        <div style="padding-top:10px;">
            <div>{{ $branding['footer_text'] }}</div>
            <div>
                Página
                <script type="text/php">
                    if (isset($pdf)) {
                        $pdf->page_text(520, 18, "{PAGE_NUM}/{PAGE_COUNT}", null, 9);
                    }
                </script>
            </div>
        </div>
    </footer>

    @yield('content')
</body>
</html>