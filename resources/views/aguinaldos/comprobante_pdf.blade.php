<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Aguinaldo · {{ $aguinaldo->empleado->nombre_completo }}</title>
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
        .total-row td { font-weight: bold; font-size: 14px; background: #f9f9f9; }
        .nota { font-size: 10px; color: #666; border-top: 1px dashed #bbb; padding-top: 8px; margin-top: 12px; }
        .footer { margin-top: 55px; text-align: center; }
        .signature-line { width: 260px; border-top: 1px solid #333; margin: 0 auto; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-title">{{ $empresa['nombre'] }}</div>
        <div class="company-meta">NIT: {{ $empresa['nit'] }} · {{ $empresa['direccion'] }}</div>
        <div class="receipt-title">COMPROBANTE DE PAGO DE AGUINALDO {{ $aguinaldo->anio }}</div>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Empleado:</strong> {{ $aguinaldo->empleado->nombre_completo }}</td>
            <td><strong>DUI:</strong> {{ $aguinaldo->empleado->dui }}</td>
        </tr>
        <tr>
            <td><strong>Departamento:</strong> {{ $aguinaldo->empleado->departamento->nombre ?? 'N/A' }}</td>
            <td><strong>Fecha de ingreso:</strong> {{ $aguinaldo->empleado->fecha_contratacion?->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Antigüedad:</strong> {{ $aguinaldo->anios_servicio }} año(s)</td>
            <td><strong>Salario diario:</strong> ${{ number_format($aguinaldo->salario_diario, 2) }}</td>
        </tr>
    </table>

    <table class="details-table">
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="num">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Aguinaldo: {{ rtrim(rtrim(number_format($aguinaldo->dias_aplicados, 2), '0'), '.') }} días de salario
                    @if ($aguinaldo->proporcional)
                        <br><small>Cálculo proporcional por tener menos de un año de servicio.</small>
                    @endif
                </td>
                <td class="num">${{ number_format($aguinaldo->monto_bruto, 2) }}</td>
            </tr>
            <tr>
                <td>Porción exenta de renta (dos salarios mínimos)</td>
                <td class="num">${{ number_format($aguinaldo->monto_exento, 2) }}</td>
            </tr>
            <tr>
                <td>Porción gravada</td>
                <td class="num">${{ number_format($aguinaldo->monto_gravado, 2) }}</td>
            </tr>
            <tr>
                <td>Retención de Impuesto sobre la Renta</td>
                <td class="num">-${{ number_format($aguinaldo->descuento_renta, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL A RECIBIR</td>
                <td class="num">${{ number_format($aguinaldo->monto_neto, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="nota">
        Calculado conforme a los Arts. 196-202 del Código de Trabajo. El aguinaldo no está sujeto
        a cotizaciones de ISSS ni AFP. La retención de renta aplica únicamente sobre la porción
        que excede el monto exento.
    </div>

    <div class="footer">
        <div class="signature-line">
            Firma de recibido conforme<br>
            {{ $aguinaldo->empleado->nombre_completo }}
        </div>
    </div>

</body>
</html>
