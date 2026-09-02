<?php

namespace App\Support;

class DesgloseLiquidacion implements \JsonSerializable
{
    public function __construct(
        public readonly string $motivo,
        public readonly int $aniosServicio,
        public readonly int $diasServicio,
        public readonly float $salarioDiario,
        public readonly float $salarioDiarioTopado,
        public readonly float $indemnizacion,
        public readonly float $prestacionRenuncia,
        public readonly float $vacacionProporcional,
        public readonly float $aguinaldoProporcional,
        public readonly float $salariosPendientes,
        public readonly float $otrasDeducciones,
        public readonly float $totalAPagar,
    ) {
    }

    public function toArray(): array
    {
        return [
            'motivo' => $this->motivo,
            'anios_servicio' => $this->aniosServicio,
            'dias_servicio' => $this->diasServicio,
            'salario_diario' => $this->salarioDiario,
            'salario_diario_topado' => $this->salarioDiarioTopado,
            'indemnizacion' => $this->indemnizacion,
            'prestacion_renuncia' => $this->prestacionRenuncia,
            'vacacion_proporcional' => $this->vacacionProporcional,
            'aguinaldo_proporcional' => $this->aguinaldoProporcional,
            'salarios_pendientes' => $this->salariosPendientes,
            'otras_deducciones' => $this->otrasDeducciones,
            'total_a_pagar' => $this->totalAPagar,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
