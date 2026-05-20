<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $folio ?? 'Contrato' }}</title>

    <style>
        @page {
            margin-top: 2cm;
            margin-bottom: 2.5cm;
            margin-left: 3cm;
            margin-right: 3cm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.6px;
            color: #111;
        }

        .pdf-header {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo-cell {
            width: 35%;
            vertical-align: middle;
            text-align: left;
        }

        .header-folio-cell {
            width: 65%;
            vertical-align: middle;
            text-align: right;
            height: 55px;
        }

        .header-logo {
            max-height: 48px;
            max-width: 160px;
        }

        .folio-box {
            display: inline-block;
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #222;
            padding: 6px 10px;
            vertical-align: middle;
        }

        .watermark-logo {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.06;
            width: 420px;
            z-index: -1000;
        }

        .contract-title {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            margin: 10px 0 14px 0;
            text-transform: uppercase;
        }

        .contract-text {
            font-size: 10.6px;
            line-height: 1.55;
            text-align: justify;
        }

        .contract-text p {
            margin: 0 0 8px 0;
        }

        .clause-title {
            font-weight: bold;
            text-transform: uppercase;
        }

        .measure-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 10px 0;
            font-size: 10.5px;
        }

        .measure-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e5e5e5;
        }

        .signature-section {
            margin-top: 1.5cm;
            text-align: center;
            page-break-inside: avoid;
        }

        .signature-row {
            width: 100%;
            margin-top: 1.6cm;
        }

        .signature-cell {
            display: inline-block;
            width: 43%;
            text-align: center;
            vertical-align: top;
            margin: 0 2%;
        }

        .signature-line-contract {
            border-top: 1px solid #000;
            padding-top: 0.9cm;
            font-weight: bold;
            font-size: 10px;
            min-height: 1.2cm;
        }

        .contract-footer {
            position: fixed;
            bottom: -1.15cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.8px;
            font-weight: bold;
            line-height: 1.25;
        }
    </style>
</head>
<body>

@php
    $documentData = $documentData ?? [];

    $lotNames = $documentData['lote_numero'] ?? $lots->pluck('identificador')->filter()->implode(', ');
    $manzanas = $documentData['manzana_numero'] ?? $lots->pluck('manzana')->filter()->unique()->implode(', ');
    $area = $documentData['area_m2'] ?? '';

    $ubicacionTerreno = $documentData['ubicacion_terreno'] ?? ($contract->lotificacion ?? '');
    $ciudadFirma = $documentData['ciudad_firma'] ?? 'TEHUACÁN PUEBLA';

    $direccionComprador = $documentData['direccion_comprador'] ?? ($contract->direccion ?? '');
    $telefonoComprador = $documentData['telefono_comprador'] ?? ($contract->telefono ?? '');

    $norteMedida = $documentData['norte_medida'] ?? '';
    $norteColindancia = $documentData['norte_colindancia'] ?? '';
    $surMedida = $documentData['sur_medida'] ?? '';
    $surColindancia = $documentData['sur_colindancia'] ?? '';
    $orienteMedida = $documentData['oriente_medida'] ?? '';
    $orienteColindancia = $documentData['oriente_colindancia'] ?? '';
    $ponienteMedida = $documentData['poniente_medida'] ?? '';
    $ponienteColindancia = $documentData['poniente_colindancia'] ?? '';

    $testigo1 = $documentData['testigo_1'] ?? '';
    $testigo2 = $documentData['testigo_2'] ?? '';

    $fecha = \Carbon\Carbon::parse($contract->fecha_emision ?? now());
    $dia = $fecha->format('d');
    $mes = mb_strtoupper($fecha->locale('es')->translatedFormat('F'));
    $anio = $fecha->format('Y');

    $importe = (float) ($contract->importe ?? 0);
    $pagoInicial = (float) ($contract->monto_pago_inicial ?? 0);
    $cuota = (float) ($contract->cuota_mensual ?? 0);
    $meses = (int) ($contract->meses ?? 0);

    $logoPath = public_path('assets/images/logo.png');

    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/logo.png');
    }

    $hasLogo = file_exists($logoPath);
@endphp

@if($hasLogo)
    <img src="{{ $logoPath }}" class="watermark-logo">
@endif

<div class="contract-footer">
    VISITANOS EN<br>
    3 ORIENTE #736 COL. RICARDO FLORES MAGON<br>
    TEHUACAN PUEBLA.<br>
    TELEFONO 238 289 0712
</div>

<div class="pdf-header">
    <table class="header-table">
        <tr>
            <td class="header-logo-cell">
                @if($hasLogo)
                    <img src="{{ $logoPath }}" class="header-logo">
                @endif
            </td>

            <td class="header-folio-cell">
                <span class="folio-box">
                    FOLIO: {{ $folio ?? $contract->numero_referencia ?? '' }}
                </span>
            </td>
        </tr>
    </table>
</div>

<div class="contract-title">
    {{ $document_type }}
</div>

<div class="contract-text">
    <p>
        CONTRATO DE COMPRA-VENTA QUE SE CELEBRA POR UNA PARTE COMO VENDEDOR EL
        C. {{ mb_strtoupper($sellerName ?: 'DANY FRANK PABLO FLORES') }}
        Y POR LA OTRA PARTE COMO COMPRADOR EL C.
        {{ mb_strtoupper($clientName) }},
        CON DIRECCIÓN EN {{ mb_strtoupper($direccionComprador ?: '____________________________') }},
        CON NÚMERO TELEFÓNICO {{ $telefonoComprador ?: '________________' }}.
        QUE SE CELEBRA EN LA CIUDAD DE {{ mb_strtoupper($ciudadFirma) }}, CONTRATO QUE SE SUJETA
        AL TENOR DE LAS SIGUIENTES DECLARACIONES Y CLÁUSULAS.
    </p>

    <p class="clause-title">DECLARACIONES:</p>

    <p>
        <span class="clause-title">PRIMERA.</span>
        El vendedor declara ser legítimo poseedor del terreno ubicado en
        {{ mb_strtoupper($ubicacionTerreno ?: '____________________________') }}.
    </p>

    <p>
        <span class="clause-title">SEGUNDA.</span>
        El comprador declara por su propio derecho tener plena capacidad jurídica y económica
        para celebrar tanto este contrato de compra-venta, como contrato definitivo objetivo presente.
    </p>

    <p>
        Hechas las declaraciones anteriores al efecto se otorgan las siguientes:
    </p>

    <p class="clause-title">CLÁUSULAS:</p>

    <p>
        <span class="clause-title">PRIMERA.</span>
        Ambas partes se obligan a perfeccionar la presente operación mediante la documentación
        debida ante la autoridad correspondiente, después de la liquidación total del mismo.
    </p>

    <p>
        <span class="clause-title">SEGUNDA.</span>
        El vendedor se obliga a enajenar y el comprador se obliga a adquirir para sí o para un tercero
        el lote No. {{ $lotNames ?: '__________' }} ubicado en la manzana No. {{ $manzanas ?: '__________' }}
        con las siguientes medidas y colindancias:
    </p>

    <table class="measure-table">
        <tr>
            <td>Al norte mide {{ $norteMedida ?: '__________' }} m</td>
            <td>colinda con {{ $norteColindancia ?: '______________________________' }}</td>
        </tr>
        <tr>
            <td>Al sur mide {{ $surMedida ?: '__________' }} m</td>
            <td>colinda con {{ $surColindancia ?: '______________________________' }}</td>
        </tr>
        <tr>
            <td>Al oriente mide {{ $orienteMedida ?: '__________' }} m</td>
            <td>colinda con {{ $orienteColindancia ?: '______________________________' }}</td>
        </tr>
        <tr>
            <td>Al poniente mide {{ $ponienteMedida ?: '__________' }} m</td>
            <td>colinda con {{ $ponienteColindancia ?: '______________________________' }}</td>
        </tr>
    </table>

    <p>
        El terreno tiene un área de {{ $area ?: '__________' }} m2.
    </p>

    @if($isCredit)
        <p>
            <span class="clause-title">TERCERA.</span>
            El precio total fijado para la celebración de la mencionada operación es la cantidad de
            ${{ number_format($importe, 2) }} PESOS 00/100 M.N.
            Que de común acuerdo han convenido las partes contratantes, y que, en el momento de la firma
            de este contrato, la parte compradora deposita como enganche la cantidad de
            ${{ number_format($pagoInicial, 2) }} PESOS 00/100 M.N.
            La parte restante se liquidará en {{ $meses }} mensualidades de
            ${{ number_format($cuota, 2) }} PESOS 00/100 M.N.,
            hasta complementar el precio total ya mencionado.
        </p>

        <p>
            <span class="clause-title">CUARTA.</span>
            Al momento de la firma del presente contrato, la parte vendedora como legítimo propietario
            del inmueble, se obliga a entregarlo desocupado y donde el comprador no podrá realizar
            construcción de albañilería o de materiales hasta que tenga cubierto el 50% del valor del lote.
        </p>
    @else
        <p>
            <span class="clause-title">TERCERA.</span>
            El precio fijado para la celebración de la mencionada operación es la cantidad de
            ${{ number_format($importe, 2) }} PESOS 00/100 M.N.
            Queda en común acuerdo que, en el momento de la firma de este contrato, la parte compradora
            deposita en efectivo la cantidad de ${{ number_format($pagoInicial, 2) }} PESOS 00/100 M.N.
            CON LO CUAL SE DA POR PAGADO COMPLETAMENTE.
        </p>

        <p>
            <span class="clause-title">CUARTA.</span>
            Al momento de la firma del presente contrato, la parte vendedora como legítimo propietario
            del inmueble, se obliga a desocupar el terreno objeto de este y sin limitación del dominio.
        </p>
    @endif

    @if($contract->contract_property_type === 'E')
        @if($isCredit)
            <p>
                <span class="clause-title">QUINTA.</span>
                Se compromete el vendedor a acudir ante el comisariado ejidal correspondiente para
                otorgarle la firma de su constancia de posesión del lote antes referido una vez liquidado
                este contrato privado de compra y venta a crédito.
            </p>
        @else
            <p>
                <span class="clause-title">QUINTA.</span>
                Se compromete el vendedor a acudir ante el comisariado ejidal correspondiente para
                otorgarle la firma de su constancia de posesión, dicho documento será pagado por el comprador
                en su totalidad.
            </p>
        @endif
    @else
        <p>
            <span class="clause-title">QUINTA.</span>
            Consecuentemente con lo dispuesto en este contrato, EL COMPRADOR acuerda que, para la firma
            de las escrituras del presente predio, realizará la solicitud de otorgamiento de escrituras
            y/o juicio de usucapión ante un Juez Civil, por lo que los gastos notariales, ISABI, ISR,
            avalúos, segregaciones y todo lo que se requiera con respecto a la escritura, así como pagos
            por impuestos, servicios, derechos y cooperaciones a la colonia, serán por cuenta del COMPRADOR.
        </p>
    @endif

    @if($isCredit)
        <p>
            <span class="clause-title">SEXTA.</span>
            El lote de terreno que se entrega es un lote rústico sin servicios, donde se observa la proximidad
            de los mismos en la colonia adjunta, por lo cual será la comunidad de colonos la que se organizará
            para la solicitud de los mismos a la autoridad correspondiente, sin responsabilidad del vendedor.
        </p>

        <p>
            <span class="clause-title">SÉPTIMA.</span>
            Para la interpretación y cumplimiento de este contrato, las partes manifiestan su conformidad
            y sometimiento a los tribunales de la jurisdicción del Distrito Judicial de Tehuacán Puebla,
            renunciando expresamente al fuero de su domicilio presente o futuro.
        </p>

        <p>
            <span class="clause-title">OCTAVA.</span>
            En caso de que el comprador se atrase en sus pagos 30 días naturales, seguidos o alternados,
            se le cobrará el 10% de recargos monetarios sobre el saldo mensual acordado en este contrato.
        </p>

        <p>
            <span class="clause-title">NOVENA.</span>
            En caso de no respetar el plazo acordado en la tercera cláusula de este contrato para la
            liquidación total, el precio del bien incrementará un 10% sobre el costo total del mismo por
            cada año adicional.
        </p>

        <p>
            <span class="clause-title">DÉCIMA.</span>
            En caso de que el comprador tenga 3 meses sin un solo pago realizado, automáticamente perderá
            los derechos sobre el lote y el monto total de sus pagos, sin responsabilidad del vendedor.
        </p>

        <p>
            <span class="clause-title">UNDÉCIMA.</span>
            En caso de que el comprador decida cancelar por cualquier motivo el presente contrato, perderá
            el monto total de sus pagos hasta la fecha de cancelación sin responsabilidad para el vendedor.
        </p>
    @else
        <p>
            <span class="clause-title">SEXTA.</span>
            Para la interpretación y cumplimiento de este contrato, las partes manifiestan su conformidad
            y sometimiento a los tribunales de la jurisdicción del Distrito Judicial de Tehuacán Puebla,
            renunciando expresamente al fuero de su domicilio presente o futuro.
        </p>
    @endif

    <p>
        El presente contrato se firma en la ciudad de {{ mb_strtoupper($ciudadFirma) }}, el día {{ $dia }}
        del mes de {{ $mes }} del año {{ $anio }}. EN ORIGINAL Y COPIA.
    </p>
</div>

<div class="signature-section">
    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line-contract">
                VENDEDOR
                <br>
                {{ mb_strtoupper($sellerName ?: 'DANY FRANK PABLO FLORES') }}
            </div>
        </div>

        <div class="signature-cell">
            <div class="signature-line-contract">
                COMPRADOR
                <br>
                {{ mb_strtoupper($clientName ?: '') }}
            </div>
        </div>
    </div>

    <div class="signature-row">
        <div class="signature-cell">
            <div class="signature-line-contract">
                TESTIGO 1
                @if(!empty($testigo1))
                    <br>{{ mb_strtoupper($testigo1) }}
                @endif
            </div>
        </div>

        <div class="signature-cell">
            <div class="signature-line-contract">
                TESTIGO 2
                @if(!empty($testigo2))
                    <br>{{ mb_strtoupper($testigo2) }}
                @endif
            </div>
        </div>
    </div>
</div>

</body>
</html>