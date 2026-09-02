<?php

namespace App\Services;

use App\Models\Aguinaldo;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Procesa el aguinaldo de todos los empleados activos de un año.
 * Mismo patrón que GeneradorPlanilla: idempotente y en una transacción.
 */
class GeneradorAguinaldo
{
    public function __construct(private readonly CalculadoraAguinaldo $calculadora)
    {
    }

    /**
     * @return array{generados: int, omitidos: int}
     */
    public function generar(int $anio, ?User $usuario = null): array
    {
        return DB::transaction(function () use ($anio, $usuario) {
            $generados = 0;
            $omitidos = 0;

            $yaProcesados = Aguinaldo::delAnio($anio)->pluck('empleado_id')->all();

            Empleado::activos()->chunkById(100, function ($empleados) use ($anio, $yaProcesados, $usuario, &$generados, &$omitidos) {
                foreach ($empleados as $empleado) {
                    if (in_array($empleado->id, $yaProcesados, true)) {
                        $omitidos++;

                        continue;
                    }

                    $desglose = $this->calculadora->calcular($empleado, $anio);

                    Aguinaldo::create($desglose->toArray() + [
                        'empleado_id' => $empleado->id,
                        'anio' => $anio,
                        'generado_por' => $usuario?->id,
                    ]);

                    $generados++;
                }
            });

            return compact('generados', 'omitidos');
        });
    }
}
