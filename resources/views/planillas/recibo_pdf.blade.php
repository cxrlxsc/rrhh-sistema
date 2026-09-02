<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago · {{ $planilla->empleado->nombre_completo }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 24px; color: #222; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 18px; }
        .company-title { font-size: 22px; font-weight: bold; text-transform: uppercase; }
        .company-meta { font-size: 11px; color: #666; margin-top: 3px; }
        .receipt-title { font-size: 15px; margin-top: 6px; color: #444; letter-spacing: 1px; }
        .info-table { width: 100%; margin-bottom: 16px; }
        .info-table td { padding: 4px 5px; }
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .details-table th, .details-table td { border: 1px solid #ccc; padding: 7px 9px; }
        .details-table th { background-color: #f4f4f4; text-align: left; }
        .num { text-align: right; }
        .totals-table { width: 55%; float: right; border-collapse: collapse; }
        .totals-table th, .totals-table td { padding: 5px 8px; text-align: right; }
        .totals-table .net-pay { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        .nota { clear: both; font-size: 10px; color: #666; border-top: 1px dashed #bbb; padding-top: 8px; margin-top: 8px; }
        .footer { clear: both; margin-top: 55px; text-align: center; }
        .signature-line { width: 260px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px; }
        .badge { background: #eef2ff; color: #3730a3; padding: 2px 6px; border-radius: 3px; font-size: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-title">{{ $empresa['nombre'] }}</div>
        <div class="company-meta">
            NIT: {{ $empresa['nit'] }} · NRC: {{ $empresa['nrc'] }} · {{ $empresa['direccion'] }}
        </div>
        <div class="receipt-title">COMPROBANTE DE PAGO DE NÓMINA</div>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Empleado:</strong> {{ $planilla->empleado->nombre_completo }}</td>
            <td><strong>DUI:</strong> {{ $planilla->empleado->dui }}</td>
        </tr>
        <tr>
            <td><strong>Departamento:</strong> {{ $planilla->empleado->departamento->nombre ?? 'N/A' }}</td>
            <td><strong>Período:</strong> {{ $planilla->mes }} {{ $planilla->anio }}</td>
        </tr>
        <tr>
            <td><strong>Días marcados:</strong> {{ $planilla->dias_laborados }}</td>
            <td><strong>Llegadas tardías:</strong> {{ $planilla->llegadas_tardias }}</td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="num">Devengado (+)</th>
                <th class="num">Deducciones (-)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Salario base ordinario</td>
                <td class="num">${{ number_format($planilla->salario_base, 2) }}</td>
                <td></td>
            </tr>
            @if ($planilla->bonificaciones > 0)
                <tr>
                    <td>Bonificaciones y otros ingresos gravables</td>
                    <td class="num">${{ number_format($planilla->bonificaciones, 2) }}</td>
                    <td></td>
                </tr>
            @endif
            <tr>
                <td>Cotización ISSS (3% · techo $1,000.00)</td>
                <td></td>
                <td class="num">${{ number_format($planilla->descuento_isss, 2) }}</td>
            </tr>
            <tr>
                <td>Cotización AFP (7.25%)</td>
                <td></td>
                <td class="num">${{ number_format($planilla->descuento_afp, 2) }}</td>
            </tr>
            <tr>
                <td>
                    Retención de Impuesto sobre la Renta
                    <span class="badge">Tramo {{ $planilla->tramo_renta }}</span>
                    <br>
                    <small>Base imponible: ${{ number_format($planilla->renta_base_imponible, 2) }}</small>
                </td>
                <td></td>
                <td class="num">${{ number_format($planilla->descuento_renta, 2) }}</td>
            </tr>
            @if ($planilla->otras_deducciones > 0)
                <tr>
                    <td>Otras deducciones</td>
                    <td></td>
                    <td class="num">${{ number_format($planilla->otras_deducciones, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <th>Total devengado:</th>
            <td>${{ number_format($planilla->total_devengado, 2) }}</td>
        </tr>
        <tr>
            <th>Total deducciones:</th>
            <td>${{ number_format($planilla->total_deducciones, 2) }}</td>
        </tr>
        <tr>
            <th class="net-pay">Líquido a recibir:</th>
            <td class="net-pay">${{ number_format($planilla->salario_liquido, 2) }}</td>
        </tr>
    </table>

    <div class="nota">
        <strong>Aportes patronales (no se descuentan al trabajador):</strong>
        ISSS ${{ number_format($planilla->aporte_patronal_isss, 2) }} ·
        AFP ${{ number_format($planilla->aporte_patronal_afp, 2) }} ·
        Costo total para la empresa ${{ number_format($planilla->costo_patronal, 2) }}.
        <br>
        La retención de renta se calcula sobre el salario devengado menos las cotizaciones de ISSS y AFP,
        aplicando la tabla mensual vigente del Ministerio de Hacienda.
    </div>

    <div class="footer">
        <div class="signature-line">
            Firma de recibido conforme<br>
            {{ $planilla->empleado->nombre_completo }}
        </div>
    </div>

</body>
</html>
