<?php

namespace App\Support;

/**
 * Resultado inmutable del cálculo de un salario.
 *
 * Se usa como "contrato" entre la CalculadoraNomina y todo lo que consume el
 * cálculo (controlador, seeders, tests, reportes), para que nadie tenga que
 * recordar el orden de los números.
 */
class DesgloseNomina implements \JsonSerializable
{
    public function __construct(
        public readonly float $salarioBase,
        public readonly float $bonificaciones,
        public readonly float $totalDevengado,
        public readonly float $isss,
        public readonly float $afp,
        public readonly float $rentaBaseImponible,
        public readonly float $renta,
        public readonly string $tramoRenta,
        public readonly float $otrasDeducciones,
        public readonly float $totalDeducciones,
        public readonly float $salarioLiquido,
        public readonly float $aportePatronalIsss,
        public readonly float $aportePatronalAfp,
        public readonly float $costoPatronal,
    ) {
    }

    /**
     * Claves listas para Planilla::create().
     */
    public function toArray(): array
    {
        return [
            'salario_base' => $this->salarioBase,
            'bonificaciones' => $this->bonificaciones,
            'total_devengado' => $this->totalDevengado,
            'descuento_isss' => $this->isss,
            'descuento_afp' => $this->afp,
            'renta_base_imponible' => $this->rentaBaseImponible,
            'descuento_renta' => $this->renta,
            'tramo_renta' => $this->tramoRenta,
            'otras_deducciones' => $this->otrasDeducciones,
            'total_deducciones' => $this->totalDeducciones,
            'salario_liquido' => $this->salarioLiquido,
            'aporte_patronal_isss' => $this->aportePatronalIsss,
            'aporte_patronal_afp' => $this->aportePatronalAfp,
            'costo_patronal' => $this->costoPatronal,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
