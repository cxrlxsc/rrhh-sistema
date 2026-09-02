<?php

namespace App\Support;

class DesgloseVacaciones implements \JsonSerializable
{
    public function __construct(
        public readonly int $dias,
        public readonly float $salarioDiario,
        public readonly float $montoBase,
        public readonly float $recargo,
        public readonly float $totalPagado,
        public readonly int $diasDisponibles,
    ) {
    }

    public function toArray(): array
    {
        return [
            'dias' => $this->dias,
            'salario_diario' => $this->salarioDiario,
            'monto_base' => $this->montoBase,
            'recargo' => $this->recargo,
            'total_pagado' => $this->totalPagado,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
