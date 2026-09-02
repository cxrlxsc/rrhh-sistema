<?php

namespace App\Support;

class DesgloseHorasExtra implements \JsonSerializable
{
    public function __construct(
        public readonly int $minutos,
        public readonly float $horas,
        public readonly float $valorHoraOrdinaria,
        public readonly float $valorHoraExtra,
        public readonly float $monto,
        public readonly int $diasConTiempoExtra,
    ) {
    }

    public function hayTiempoExtra(): bool
    {
        return $this->minutos > 0;
    }

    public function toArray(): array
    {
        return [
            'minutos' => $this->minutos,
            'horas' => $this->horas,
            'valor_hora_ordinaria' => $this->valorHoraOrdinaria,
            'valor_hora_extra' => $this->valorHoraExtra,
            'monto' => $this->monto,
            'dias_con_tiempo_extra' => $this->diasConTiempoExtra,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
