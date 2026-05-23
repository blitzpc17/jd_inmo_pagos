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

    .center-title {
        text-align: center;
        font-weight: bold;
        margin: 10px 0;
    }

    .clause-title {
        font-weight: bold;
        text-transform: uppercase;
    }

    /*
     * Medidas y colindancias:
     * - Más centrado visualmente.
     * - Más compacto.
     * - Mantiene columnas alineadas.
     */
    .measure-table-wrapper {
        width: 100%;
        text-align: center;
        margin: 8px 0 10px 0;
    }

    .measure-table {
        width: 60%;
        margin: 0 auto;
        border-collapse: collapse;
        font-size: 10.5px;
    }

    .measure-table td {
        padding: 4px 5px;
        border-bottom: 0px solid #e5e5e5;
        vertical-align: top;
    }

    .measure-table .measure-side {
        width: 60%;
        text-align: left;
        white-space: nowrap;
    }

    .measure-table .measure-boundary {
        width: 40%;
        text-align: left;
    }

    .measure-value,
    .boundary-value {
        font-weight: bold;
    }

    /*
     * Firmas:
     * - Más espacio para firmar.
     * - Sin desperdiciar demasiada hoja.
     */
    .signature-section {
        margin-top: .85cm;
        text-align: center;
        page-break-inside: avoid;
    }

    .signature-row {
        width: 100%;
        margin-top: 1.85cm;
    }

    .signature-cell {
        display: inline-block;
        width: 39%;
        text-align: center;
        vertical-align: top;
        margin: 0 3%;
    }

    .signature-line-contract {
        border-top: 1px solid #000;
        padding-top: 0.62cm;
        font-weight: bold;
        font-size: 10px;
        min-height: 1.15cm;
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
    $branding = $branding ?? [];

    $logoRelativePath = $branding['logo_path'] ?? 'assets/images/logo.png';
    $logoPath = public_path($logoRelativePath);

    if (!file_exists($logoPath)) {
        $logoPath = public_path('assets/images/logo.png');
    }

    if (!file_exists($logoPath)) {
        $logoPath = public_path('images/logo.png');
    }

    $hasLogo = file_exists($logoPath);

    $footerAddress = $branding['address_line'] ?? 'VISITANOS EN 3 ORIENTE #736 COL. RICARDO FLORES MAGON TEHUACAN PUEBLA.';
    $footerPhone = $branding['phone_line'] ?? 'TELEFONO 238 289 0712';

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

    $vendedorContrato = trim((string) ($documentData['vendedor_contrato'] ?? ''));

    if ($vendedorContrato === '') {
        $vendedorContrato = 'DANY FRANK PABLO FLORES';
    }

    $fecha = \Carbon\Carbon::parse($contract->fecha_emision ?? now());
    $dia = $fecha->format('d');
    $mes = mb_strtoupper($fecha->locale('es')->translatedFormat('F'));
    $anio = $fecha->format('Y');

    $importe = (float) ($contract->importe ?? 0);
    $pagoInicial = (float) ($contract->monto_pago_inicial ?? 0);
    $cuota = (float) ($contract->cuota_mensual ?? 0);
    $meses = (int) ($contract->meses ?? 0);
@endphp

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

<div class="contract-footer">
    {{ $footerAddress }}<br>
    {{ $footerPhone }}
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
        C. {{ mb_strtoupper($vendedorContrato) }}
        Y POR LA OTRA PARTE COMO COMPRADOR EL C.
        {{ mb_strtoupper($clientName) }},
        CON DIRECCIÓN EN {{ mb_strtoupper($direccionComprador ?: '____________________________') }},
        CON NÚMERO TELEFÓNICO {{ $telefonoComprador ?: '________________' }}.
        QUE SE CELEBRA EN LA CIUDAD DE {{ mb_strtoupper($ciudadFirma) }},
        CONTRATO QUE SE SUJETA AL TENOR DE LAS SIGUIENTES DECLARACIONES Y CLÁUSULAS.
    </p>

    <p class="center-title">DECLARACIONES:</p>

    <p>
        <span class="clause-title">PRIMERA.</span>
        El vendedor declara ser legítimo poseedor del terreno ubicado en
        {{ mb_strtoupper($ubicacionTerreno ?: '____________________________') }}.
    </p>

    <p>
        <span class="clause-title">SEGUNDA.</span>
        El comprador declara por su propio derecho tener plena capacidad jurídica y económica,
        para celebrar tanto este contrato de compra-venta, como contrato definitivo, objetivo presente.
    </p>

    <p>
        Hechas las declaraciones anteriores al efecto se otorgan las siguientes:
    </p>

    <p class="center-title">CLÁUSULAS:</p>

    <p>
        <span class="clause-title">PRIMERA.</span>
        Ambas partes se obligan a perfeccionar la presente operación mediante la documentación debida
        ante la autoridad correspondiente, después de la liquidación total del mismo.
    </p>

    <p>
        <span class="clause-title">SEGUNDA.</span>
        El vendedor se obliga a enajenar y el comprador se obliga a adquirir para sí o para un tercero
        el lote No. {{ $lotNames ?: '__________' }} ubicado en la manzana No. {{ $manzanas ?: '__________' }}
        con las siguientes medidas y colindancias:
    </p>

    <div class="measure-table-wrapper">
        <table class="measure-table">
            <tr>
                <td class="measure-side">
                    Al norte mide <span class="measure-value">{{ $norteMedida ?: '__________' }} m</span>
                </td>
                <td class="measure-boundary">
                    colinda con <span class="boundary-value">{{ $norteColindancia ?: '________________________' }}</span>
                </td>
            </tr>

            <tr>
                <td class="measure-side">
                    Al sur mide <span class="measure-value">{{ $surMedida ?: '__________' }} m</span>
                </td>
                <td class="measure-boundary">
                    colinda con <span class="boundary-value">{{ $surColindancia ?: '________________________' }}</span>
                </td>
            </tr>

            <tr>
                <td class="measure-side">
                    Al oriente mide <span class="measure-value">{{ $orienteMedida ?: '__________' }} m</span>
                </td>
                <td class="measure-boundary">
                    colinda con <span class="boundary-value">{{ $orienteColindancia ?: '________________________' }}</span>
                </td>
            </tr>

            <tr>
                <td class="measure-side">
                    Al poniente mide <span class="measure-value">{{ $ponienteMedida ?: '__________' }} m</span>
                </td>
                <td class="measure-boundary">
                    colinda con <span class="boundary-value">{{ $ponienteColindancia ?: '________________________' }}</span>
                </td>
            </tr>
        </table>
    </div>

    <p>
        Con una superficie total de {{ $area ?: '__________' }} metros cuadrados.
    </p>

    @if($templateKey === 'e_contado')
        <p>
            <span class="clause-title">TERCERA.</span>
            El precio total fijado para la celebración de la mencionada operación es la cantidad de
            ${{ number_format($importe, 2) }} PESOS 00/100 M.N.
            Que de común acuerdo han convenido las partes contratantes, y que, en el momento de la firma
            de este contrato, la parte compradora cubre en su totalidad.
        </p>

        <p>
            <span class="clause-title">CUARTA.</span>
            Al momento de la firma del presente contrato, la parte vendedora como legítimo propietario
            del inmueble, se obliga a entregarlo desocupado.
        </p>

        <p>
            <span class="clause-title">QUINTA.</span>
            Se compromete el vendedor a acudir ante el comisariado ejidal correspondiente para otorgarle
            la firma de su constancia de posesión del lote antes referido una vez liquidado este contrato
            privado de compra y venta.
        </p>

        <p>
            <span class="clause-title">SEXTA.</span>
            Para la interpretación y cumplimiento de este contrato, las partes manifiestan su conformidad
            y sometimiento a los tribunales de la jurisdicción del Distrito Judicial de Tehuacán Puebla,
            renunciando expresamente al fuero de su domicilio presente o futuro.
        </p>
    @endif

    @if($templateKey === 'p_contado')
        <p>
            <span class="clause-title">TERCERA.</span>
            El precio total fijado para la celebración de la mencionada operación es la cantidad de
            ${{ number_format($importe, 2) }} PESOS 00/100 M.N.
            Que de común acuerdo han convenido las partes contratantes, y que, en el momento de la firma
            de este contrato, la parte compradora cubre en su totalidad.
        </p>

        <p>
            <span class="clause-title">CUARTA.</span>
            Al momento de la firma del presente contrato, la parte vendedora como legítimo propietario
            del inmueble, se obliga a entregarlo desocupado.
        </p>

        <p>
            <span class="clause-title">QUINTA.</span>
            Con lo dispuesto en este contrato, EL COMPRADOR acuerda que, para la firma de las escrituras
            del presente predio, realizará la solicitud de otorgamiento de escrituras y/o juicio de usucapión
            ante un Juez Civil, por lo que los gastos notariales, ISABI, ISR, avalúos, segregaciones y todo
            lo que se requiera con respecto a la escritura, así como los pagos por impuestos, servicios,
            derechos y cooperaciones a la colonia, a partir de la firma del presente, así como los correspondientes
            a la escrituración, serán por cuenta del COMPRADOR.
        </p>

        <p>
            <span class="clause-title">SEXTA.</span>
            Para la interpretación y cumplimiento de este contrato, las partes manifiestan su conformidad
            y sometimiento a los tribunales de la jurisdicción del Distrito Judicial de Tehuacán Puebla,
            renunciando expresamente al fuero de su domicilio presente o futuro.
        </p>
    @endif

    @if($templateKey === 'e_credito')
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
            construcción de albañilería o de materiales hasta que tenga cubierto el 50% del valor del lote,
            únicamente se autoriza la construcción de jacales de madera, carrizo con techumbre de lámina.
        </p>

        <p>
            <span class="clause-title">QUINTA.</span>
            Se compromete el vendedor a acudir ante el comisariado ejidal correspondiente para otorgarle
            la firma de su constancia de posesión del lote antes referido una vez liquidado este contrato
            privado de compra y venta a crédito.
        </p>

        <p>
            <span class="clause-title">SEXTA.</span>
            El lote de terreno que se entrega es un lote rústico sin servicios, donde se observa la proximidad
            de los mismos en la colonia adjunta, por lo cual será la comunidad de los colonos la que se organizará
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
            se le cobrará el 10% de intereses monetarios sobre el saldo mensual acordado en este contrato,
            y si se negara a cubrir los intereses, perderá automáticamente su derecho sobre el lote y la
            devolución de sus pagos, motivo de este contrato.
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
            los derechos sobre el lote y el monto total de sus pagos, motivo de este contrato.
        </p>

        <p>
            <span class="clause-title">UNDÉCIMA.</span>
            En caso de que el comprador decida cancelar por cualquier motivo el presente contrato, perderá
            el monto total de sus pagos hasta la fecha de la cancelación sin responsabilidad para el vendedor.
        </p>
    @endif

    @if($templateKey === 'p_credito')
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
            construcción de albañilería o de materiales hasta que tenga cubierto el 50% del valor del lote,
            únicamente se autoriza la construcción de jacales de madera, carrizo con techumbre de lámina.
        </p>

        <p>
            <span class="clause-title">QUINTA.</span>
            Con lo dispuesto en este contrato, EL COMPRADOR acuerda que, para la firma de las escrituras
            del presente predio, realizará la solicitud de otorgamiento de escrituras y/o juicio de usucapión
            ante un Juez Civil, por lo que los gastos notariales, ISABI, ISR, avalúos, segregaciones y todo
            lo que se requiera con respecto a la escritura, así como los pagos por impuestos, servicios,
            derechos y cooperaciones a la colonia, a partir de la firma del presente, así como los correspondientes
            a la escrituración, serán por cuenta del COMPRADOR.
        </p>

        <p>
            <span class="clause-title">SEXTA.</span>
            El lote de terreno que se entrega es un lote rústico sin servicios, donde se observa la proximidad
            de los mismos en la colonia adjunta, por lo cual será la comunidad de los colonos la que se organizará
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
            se le cobrará el 10% de recargos monetarios sobre el saldo mensual acordado en este contrato,
            y si se negara a cubrir los intereses, perderá automáticamente su derecho sobre el lote y la
            devolución de sus pagos, motivo de este contrato.
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
            los derechos sobre el lote y el monto total de sus pagos, motivo de este contrato, sin responsabilidad
            del vendedor.
        </p>

        <p>
            <span class="clause-title">UNDÉCIMA.</span>
            En caso de que el comprador decida cancelar por cualquier motivo el presente contrato, perderá
            el monto total de sus pagos hasta la fecha de la cancelación sin responsabilidad para el vendedor.
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
                {{ mb_strtoupper($vendedorContrato) }}
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