<?php

namespace App\Services;

use App\Support\DesgloseNomina;
use InvalidArgumentException;

/**
 * Motor de cálculo salarial para El Salvador.
 *
 * Toda la matemática de la planilla vive aquí (y solo aquí): los controladores
 * únicamente piden el desglose y lo guardan. Las tasas y la tabla de tramos de
 * renta se leen de config/nomina.php, de modo que una reforma fiscal se
 * resuelve editando configuración, sin tocar código.
 *
 * Orden legal del cálculo:
 *   1. Total devengado          = salario base + bonificaciones gravables
 *   2. ISSS  (3% con techo de $1,000 => máximo $30.00)
 *   3. AFP   (7.25% con techo de $6,377.14)
 *   4. Base imponible de renta  = devengado - ISSS - AFP
 *   5. Renta = cuota fija del tramo + % sobre el exceso
 *   6. Líquido = devengado - ISSS - AFP - renta - otras deducciones
 */
class CalculadoraNomina
{
    /**
     * Calcula el desglose completo de un salario.
     *
     * @param  float  $salarioBase       Salario nominal del contrato.
     * @param  float  $bonificaciones    Ingresos gravables adicionales (horas extra, comisiones).
     * @param  float  $otrasDeducciones  Descuentos ajenos a la ley (préstamos, anticipos).
     * @param  string $periodicidad      'mensual' o 'quincenal'.
     */
    public function calcular(
        float $salarioBase,
        float $bonificaciones = 0.0,
        float $otrasDeducciones = 0.0,
        ?string $periodicidad = null,
    ): DesgloseNomina {
        if ($salarioBase < 0 || $bonificaciones < 0 || $otrasDeducciones < 0) {
            throw new InvalidArgumentException('Los montos de la planilla no pueden ser negativos.');
        }

        $periodicidad ??= config('nomina.renta.periodicidad_por_defecto', 'mensual');

        $totalDevengado = $this->redondear($salarioBase + $bonificaciones);

        $isss = $this->calcularIsss($totalDevengado);
        $afp = $this->calcularAfp($totalDevengado);

        $baseImponible = $this->redondear($totalDevengado - $isss - $afp);
        $tramo = $this->tramoAplicable($baseImponible, $periodicidad);
        $renta = $this->calcularRenta($baseImponible, $periodicidad);

        $otrasDeducciones = $this->redondear($otrasDeducciones);
        $totalDeducciones = $this->redondear($isss + $afp + $renta + $otrasDeducciones);
        $liquido = $this->redondear($totalDevengado - $totalDeducciones);

        $patronalIsss = $this->calcularIsssPatronal($totalDevengado);
        $patronalAfp = $this->calcularAfpPatronal($totalDevengado);

        return new DesgloseNomina(
            salarioBase: $this->redondear($salarioBase),
            bonificaciones: $this->redondear($bonificaciones),
            totalDevengado: $totalDevengado,
            isss: $isss,
            afp: $afp,
            rentaBaseImponible: $baseImponible,
            renta: $renta,
            tramoRenta: $tramo['tramo'],
            otrasDeducciones: $otrasDeducciones,
            totalDeducciones: $totalDeducciones,
            salarioLiquido: $liquido,
            aportePatronalIsss: $patronalIsss,
            aportePatronalAfp: $patronalAfp,
            costoPatronal: $this->redondear($totalDevengado + $patronalIsss + $patronalAfp),
        );
    }

    /**
     * Cotización de salud del trabajador: 3% sobre un máximo cotizable de $1,000.
     */
    public function calcularIsss(float $devengado): float
    {
        $base = min($devengado, (float) config('nomina.isss.techo_cotizable'));

        return $this->redondear($base * (float) config('nomina.isss.tasa_empleado'));
    }

    /**
     * Aporte patronal de salud: 7.5% sobre el mismo techo cotizable.
     */
    public function calcularIsssPatronal(float $devengado): float
    {
        $base = min($devengado, (float) config('nomina.isss.techo_cotizable'));

        return $this->redondear($base * (float) config('nomina.isss.tasa_patronal'));
    }

    /**
     * Aporte de pensiones del trabajador: 7.25% con techo cotizable propio.
     */
    public function calcularAfp(float $devengado): float
    {
        $base = min($devengado, (float) config('nomina.afp.techo_cotizable'));

        return $this->redondear($base * (float) config('nomina.afp.tasa_empleado'));
    }

    /**
     * Aporte patronal de pensiones: 8.75%.
     */
    public function calcularAfpPatronal(float $devengado): float
    {
        $base = min($devengado, (float) config('nomina.afp.techo_cotizable'));

        return $this->redondear($base * (float) config('nomina.afp.tasa_patronal'));
    }

    /**
     * Retención de ISR sobre la base imponible (devengado menos ISSS y AFP).
     *
     * Fórmula por tramo: cuota fija + (base - excedente) * porcentaje.
     */
    public function calcularRenta(float $baseImponible, ?string $periodicidad = null): float
    {
        if ($baseImponible <= 0) {
            return 0.00;
        }

        $tramo = $this->tramoAplicable($baseImponible, $periodicidad);

        if ($tramo['porcentaje'] <= 0) {
            return 0.00;
        }

        $exceso = max(0.0, $baseImponible - (float) $tramo['sobre_exceso_de']);
        $retencion = (float) $tramo['cuota_fija'] + ($exceso * (float) $tramo['porcentaje']);

        // Blindaje: la retención jamás puede dejar el líquido en negativo.
        return $this->redondear(min($retencion, $baseImponible));
    }

    /**
     * Renta que genera un ingreso extraordinario (aguinaldo gravado, bono,
     * vacación) al sumarse al salario del período.
     *
     * Se calcula al margen: la retención del total menos la retención que ya
     * correspondía al salario ordinario. Así el ingreso adicional tributa a la
     * tasa que realmente le toca y no se recalcula dos veces lo mismo.
     */
    public function rentaSobreIngresoAdicional(
        float $baseImponibleOrdinaria,
        float $ingresoAdicional,
        ?string $periodicidad = null,
    ): float {
        if ($ingresoAdicional <= 0) {
            return 0.00;
        }

        $conAdicional = $this->calcularRenta($baseImponibleOrdinaria + $ingresoAdicional, $periodicidad);
        $sinAdicional = $this->calcularRenta($baseImponibleOrdinaria, $periodicidad);

        return $this->redondear(max(0, $conAdicional - $sinAdicional));
    }

    /**
     * Devuelve el tramo de la tabla que le corresponde a una base imponible.
     * Es público a propósito: la vista del recibo muestra el tramo aplicado.
     */
    public function tramoAplicable(float $baseImponible, ?string $periodicidad = null): array
    {
        $tabla = $this->tabla($periodicidad);

        foreach ($tabla as $tramo) {
            $dentroDelPiso = $baseImponible >= (float) $tramo['desde'];
            $dentroDelTecho = $tramo['hasta'] === null || $baseImponible <= (float) $tramo['hasta'];

            if ($dentroDelPiso && $dentroDelTecho) {
                return $tramo;
            }
        }

        // Base menor a $0.01: exenta, corresponde al primer tramo.
        return $tabla[0];
    }

    /**
     * Tabla de retención vigente para la periodicidad indicada.
     */
    public function tabla(?string $periodicidad = null): array
    {
        $periodicidad ??= config('nomina.renta.periodicidad_por_defecto', 'mensual');
        $tabla = config("nomina.renta.tablas.{$periodicidad}");

        if (empty($tabla)) {
            throw new InvalidArgumentException("No existe tabla de renta para la periodicidad '{$periodicidad}'.");
        }

        return $tabla;
    }

    private function redondear(float $monto): float
    {
        return round($monto, 2);
    }
}
