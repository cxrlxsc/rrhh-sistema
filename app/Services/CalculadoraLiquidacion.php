<?php

namespace App\Services;

use App\Models\Empleado;
use App\Support\DesgloseLiquidacion;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Liquidación / finiquito al terminar la relación laboral.
 *
 * Qué se paga depende del motivo de la salida:
 *
 *  - Despido sin causa justificada: indemnización de 30 días de salario por
 *    año de servicio (Art. 58), con el salario diario topado a 4 salarios
 *    mínimos diarios.
 *  - Renuncia voluntaria con 2 años o más: prestación económica de 15 días
 *    por año (D.L. 592), con su propio tope.
 *  - En TODOS los casos: vacación y aguinaldo proporcionales, más los
 *    salarios devengados y no pagados.
 */
class CalculadoraLiquidacion
{
    public function __construct(
        private readonly CalculadoraVacaciones $vacaciones,
        private readonly CalculadoraAguinaldo $aguinaldo,
    ) {
    }

    public function calcular(
        Empleado $empleado,
        CarbonInterface $fechaSalida,
        string $motivo,
        float $salariosPendientes = 0.0,
        float $otrasDeducciones = 0.0,
    ): DesgloseLiquidacion {
        if (! array_key_exists($motivo, config('prestaciones.liquidacion.motivos'))) {
            throw new InvalidArgumentException("Motivo de salida no reconocido: {$motivo}");
        }

        if ($empleado->fecha_contratacion && $fechaSalida->lessThan($empleado->fecha_contratacion)) {
            throw new InvalidArgumentException('La fecha de salida no puede ser anterior a la de contratación.');
        }

        $salarioDiario = $empleado->salarioDiario();
        $anios = $empleado->antiguedadEnAnios($fechaSalida);
        $dias = $empleado->diasDeServicio($fechaSalida);

        $indemnizacion = $this->indemnizacion($motivo, $dias, $salarioDiario);
        $prestacionRenuncia = $this->prestacionPorRenuncia($motivo, $anios, $dias, $salarioDiario);
        $vacacionProporcional = $this->vacaciones->proporcionalALaFecha($empleado, $fechaSalida);
        $aguinaldoProporcional = $this->aguinaldoProporcional($empleado, $fechaSalida);

        $total = round(
            $indemnizacion
            + $prestacionRenuncia
            + $vacacionProporcional
            + $aguinaldoProporcional
            + $salariosPendientes
            - $otrasDeducciones,
            2
        );

        return new DesgloseLiquidacion(
            motivo: $motivo,
            aniosServicio: $anios,
            diasServicio: $dias,
            salarioDiario: $salarioDiario,
            salarioDiarioTopado: $this->salarioTopadoIndemnizacion($salarioDiario),
            indemnizacion: $indemnizacion,
            prestacionRenuncia: $prestacionRenuncia,
            vacacionProporcional: $vacacionProporcional,
            aguinaldoProporcional: $aguinaldoProporcional,
            salariosPendientes: round($salariosPendientes, 2),
            otrasDeducciones: round($otrasDeducciones, 2),
            totalAPagar: max(0, $total),
        );
    }

    /**
     * Indemnización por despido: 30 días por año, incluida la fracción.
     */
    private function indemnizacion(string $motivo, int $diasServicio, float $salarioDiario): float
    {
        if (! in_array($motivo, config('prestaciones.liquidacion.motivos_con_indemnizacion', []), true)) {
            return 0.00;
        }

        $diasPorAnio = (int) config('prestaciones.liquidacion.despido.dias_por_anio', 30);
        $diasAPagar = $diasPorAnio * ($diasServicio / 365);

        return round($diasAPagar * $this->salarioTopadoIndemnizacion($salarioDiario), 2);
    }

    /**
     * Prestación por renuncia voluntaria: exige una antigüedad mínima.
     */
    private function prestacionPorRenuncia(string $motivo, int $anios, int $diasServicio, float $salarioDiario): float
    {
        if (! in_array($motivo, config('prestaciones.liquidacion.motivos_con_prestacion_renuncia', []), true)) {
            return 0.00;
        }

        if ($anios < (int) config('prestaciones.liquidacion.renuncia.anios_minimos', 2)) {
            return 0.00;
        }

        $diasPorAnio = (int) config('prestaciones.liquidacion.renuncia.dias_por_anio', 15);
        $diasAPagar = $diasPorAnio * ($diasServicio / 365);

        return round($diasAPagar * $this->salarioTopadoRenuncia($salarioDiario), 2);
    }

    /**
     * Aguinaldo proporcional desde el último 12 de diciembre hasta la salida.
     */
    private function aguinaldoProporcional(Empleado $empleado, CarbonInterface $fechaSalida): float
    {
        $corteAnterior = $this->aguinaldo->fechaDeCorte($fechaSalida->year);

        if ($corteAnterior->greaterThan($fechaSalida)) {
            $corteAnterior = $this->aguinaldo->fechaDeCorte($fechaSalida->year - 1);
        }

        // Si entró después del último corte, se cuenta desde su contratación.
        $inicio = $empleado->fecha_contratacion && $empleado->fecha_contratacion->greaterThan($corteAnterior)
            ? Carbon::parse($empleado->fecha_contratacion)
            : Carbon::parse($corteAnterior);

        $diasTranscurridos = min(365, max(0, (int) $inicio->diffInDays($fechaSalida)));

        $diasCorrespondientes = $this->aguinaldo->diasPorAntiguedad($empleado->antiguedadEnAnios($fechaSalida));
        $diasAPagar = $diasCorrespondientes * ($diasTranscurridos / 365);

        return round($diasAPagar * $empleado->salarioDiario(), 2);
    }

    public function salarioTopadoIndemnizacion(float $salarioDiario): float
    {
        $tope = (int) config('prestaciones.liquidacion.despido.tope_salarios_minimos_diarios', 4)
            * $this->salarioMinimoDiario();

        return round(min($salarioDiario, $tope), 4);
    }

    public function salarioTopadoRenuncia(float $salarioDiario): float
    {
        $tope = (int) config('prestaciones.liquidacion.renuncia.tope_salarios_minimos', 2)
            * $this->salarioMinimoDiario();

        return round(min($salarioDiario, $tope), 4);
    }

    private function salarioMinimoDiario(): float
    {
        return (float) config('nomina.salario_minimo_mensual', 365.00)
            / (int) config('nomina.dias_mes_comercial', 30);
    }
}
