<?php

namespace App\Services;

use App\Models\Empleado;
use App\Support\DesgloseAguinaldo;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Aguinaldo (Código de Trabajo, Arts. 196-202).
 *
 * Los días de salario dependen de la antigüedad medida al 12 de diciembre:
 *
 *   1 a menos de 3 años  -> 15 días
 *   3 a menos de 10 años -> 19 días
 *   10 años o más        -> 21 días
 *
 * Quien no ha cumplido un año recibe la parte proporcional al tiempo laborado
 * sobre la base de 15 días.
 *
 * Fiscalmente: la porción que excede dos salarios mínimos está gravada con
 * renta, y el aguinaldo no cotiza ISSS ni AFP.
 */
class CalculadoraAguinaldo
{
    public function __construct(private readonly CalculadoraNomina $nomina)
    {
    }

    public function calcular(Empleado $empleado, int $anio): DesgloseAguinaldo
    {
        $corte = $this->fechaDeCorte($anio);
        $salarioDiario = $empleado->salarioDiario();

        $anios = $empleado->antiguedadEnAnios($corte);
        $diasServicio = max(0, $empleado->diasDeServicio($corte));
        $proporcional = $anios < 1;

        $dias = $proporcional
            ? $this->diasProporcionales($diasServicio)
            : (float) $this->diasPorAntiguedad($anios);

        $bruto = round($dias * $salarioDiario, 2);

        // Parte exenta de renta: hasta dos salarios mínimos mensuales.
        $exento = round(min($bruto, $this->montoExento()), 2);
        $gravado = round($bruto - $exento, 2);

        // La retención se calcula al margen del salario ordinario del mes,
        // que es como realmente tributa el ingreso extraordinario.
        $renta = $this->nomina->rentaSobreIngresoAdicional(
            $this->baseImponibleOrdinaria($empleado),
            $gravado
        );

        return new DesgloseAguinaldo(
            aniosServicio: $anios,
            diasAplicados: round($dias, 2),
            salarioDiario: $salarioDiario,
            proporcional: $proporcional,
            montoBruto: $bruto,
            montoExento: $exento,
            montoGravado: $gravado,
            renta: $renta,
            montoNeto: round($bruto - $renta, 2),
        );
    }

    /**
     * Días de salario que corresponden según los años cumplidos.
     */
    public function diasPorAntiguedad(int $anios): int
    {
        foreach (config('prestaciones.aguinaldo.tramos') as $tramo) {
            $sobreElPiso = $anios >= $tramo['desde_anios'];
            $bajoElTecho = $tramo['hasta_anios'] === null || $anios < $tramo['hasta_anios'];

            if ($sobreElPiso && $bajoElTecho) {
                return (int) $tramo['dias'];
            }
        }

        return (int) config('prestaciones.aguinaldo.dias_proporcional', 15);
    }

    /**
     * Proporción para quien tiene menos de un año de servicio.
     */
    public function diasProporcionales(int $diasServicio): float
    {
        $base = (int) config('prestaciones.aguinaldo.dias_proporcional', 15);

        return round($base * (min($diasServicio, 365) / 365), 2);
    }

    /**
     * Fecha legal de corte para medir la antigüedad (12 de diciembre).
     */
    public function fechaDeCorte(int $anio): CarbonInterface
    {
        return Carbon::create(
            $anio,
            (int) config('prestaciones.aguinaldo.mes_corte', 12),
            (int) config('prestaciones.aguinaldo.dia_corte', 12)
        )->endOfDay();
    }

    public function montoExento(): float
    {
        return round(
            (float) config('nomina.salario_minimo_mensual', 365.00)
            * (int) config('prestaciones.aguinaldo.exento_salarios_minimos', 2),
            2
        );
    }

    /**
     * Base imponible del salario ordinario del empleado, necesaria para saber
     * a qué tasa marginal tributa el aguinaldo gravado.
     */
    private function baseImponibleOrdinaria(Empleado $empleado): float
    {
        $salario = (float) $empleado->salario_base;

        return round($salario - $this->nomina->calcularIsss($salario) - $this->nomina->calcularAfp($salario), 2);
    }
}
