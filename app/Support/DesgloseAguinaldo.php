<?php

namespace App\Support;

/**
 * Resultado inmutable del cálculo de aguinaldo.
 */
class DesgloseAguinaldo implements \JsonSerializable
{
    public function __construct(
        public readonly int $aniosServicio,
        public readonly float $diasAplicados,
        public readonly float $salarioDiario,
        public readonly bool $proporcional,
        public readonly float $montoBruto,
        public readonly float $montoExento,
        public readonly float $montoGravado,
        public readonly float $renta,
        public readonly float $montoNeto,
    ) {
    }

    public function toArray(): array
    {
        return [
            'anios_servicio' => $this->aniosServicio,
            'dias_aplicados' => $this->diasAplicados,
            'salario_diario' => $this->salarioDiario,
            'proporcional' => $this->proporcional,
            'monto_bruto' => $this->montoBruto,
            'monto_exento' => $this->montoExento,
            'monto_gravado' => $this->montoGravado,
            'descuento_renta' => $this->renta,
            'monto_neto' => $this->montoNeto,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
