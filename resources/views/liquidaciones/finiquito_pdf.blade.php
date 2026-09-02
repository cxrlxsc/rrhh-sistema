<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Finiquito Laboral · {{ $liquidacion->empleado->nombre_completo }}</title>
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
        .declaracion { font-size: 11px; text-align: justify; line-height: 1.5; margin-top: 18px; }
        .nota { font-size: 10px; color: #666; border-top: 1px dashed #bbb; padding-top: 8px; margin-top: 12px; }
        .firmas { margin-top: 60px; width: 100%; }
        .firmas td { text-align: center; padding: 0 20px; }
        .signature-line { border-top: 1px solid #333; padding-top: 5px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-title">{{ $empresa['nombre'] }}</div>
        <div class="company-meta">NIT: {{ $empresa['nit'] }} · {{ $empresa['direccion'] }}</div>
        <div class="receipt-title">FINIQUITO LABORAL</div>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Empleado:</strong> {{ $liquidacion->empleado->nombre_completo }}</td>
            <td><strong>DUI:</strong> {{ $liquidacion->empleado->dui }}</td>
        </tr>
        <tr>
            <td><strong>Cargo / Departamento:</strong> {{ $liquidacion->empleado->departamento->nombre ?? 'N/A' }}</td>
            <td><strong>Salario mensual:</strong> ${{ number_format($liquidacion->empleado->salario_base, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Fecha de ingreso:</strong> {{ $liquidacion->empleado->fecha_contratacion?->format('d/m/Y') }}</td>
            <td><strong>Fecha de retiro:</strong> {{ $liquidacion->fecha_salida->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Tiempo de servicio:</strong> {{ $liquidacion->anios_servicio }} año(s) ({{ $liquidacion->dias_servicio }} días)</td>
            <td><strong>Motivo:</strong> {{ $liquidacion->motivo_legible }}</td>
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
                    Indemnización por despido sin causa justificada
                    <br><small>30 días de salario por año de servicio · salario diario aplicado
                    ${{ number_format($liquidacion->salario_diario_topado, 2) }}</small>
                </td>
                <td class="num">${{ number_format($liquidacion->indemnizacion, 2) }}</td>
            </tr>
            <tr>
                <td>
                    Prestación económica por renuncia voluntaria
                    <br><small>15 días de salario por año de servicio</small>
                </td>
                <td class="num">${{ number_format($liquidacion->prestacion_renuncia, 2) }}</td>
            </tr>
            <tr>
                <td>Vacación proporcional (incluye el 30% de recargo legal)</td>
                <td class="num">${{ number_format($liquidacion->vacacion_proporcional, 2) }}</td>
            </tr>
            <tr>
                <td>Aguinaldo proporcional</td>
                <td class="num">${{ number_format($liquidacion->aguinaldo_proporcional, 2) }}</td>
            </tr>
            <tr>
                <td>Salarios devengados pendientes de pago</td>
                <td class="num">${{ number_format($liquidacion->salarios_pendientes, 2) }}</td>
            </tr>
            <tr>
                <td>Otras deducciones</td>
                <td class="num">-${{ number_format($liquidacion->otras_deducciones, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL A PAGAR</td>
                <td class="num">${{ number_format($liquidacion->total_a_pagar, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($liquidacion->observaciones)
        <p><strong>Observaciones:</strong> {{ $liquidacion->observaciones }}</p>
    @endif

    <div class="declaracion">
        Por este medio hago constar que he recibido de <strong>{{ $empresa['nombre'] }}</strong> la cantidad de
        <strong>${{ number_format($liquidacion->total_a_pagar, 2) }}</strong> en concepto de las prestaciones
        detalladas anteriormente, correspondientes a la terminación de mi relación laboral con fecha
        {{ $liquidacion->fecha_salida->format('d/m/Y') }}.
    </div>

    <div class="nota">
        Documento calculado conforme al Código de Trabajo de El Salvador. Este comprobante no sustituye el
        acta de finiquito que debe otorgarse ante notario o ante el Ministerio de Trabajo cuando la ley así lo exija.
    </div>

    <table class="firmas">
        <tr>
            <td width="50%">
                <div class="signature-line">
                    {{ $liquidacion->empleado->nombre_completo }}<br>
                    <small>Empleado</small>
                </div>
            </td>
            <td width="50%">
                <div class="signature-line">
                    {{ $empresa['nombre'] }}<br>
                    <small>Representante patronal</small>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
