<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Procesa la nómina de un período completo.
 *
 * Vive fuera del controlador para que la misma lógica sirva a la pantalla web
 * y al comando de consola (`php artisan planilla:generar`), que es lo que se
 * programa en el cron del servidor.
 */
class GeneradorPlanilla
{
    public function __construct(private readonly CalculadoraNomina $calculadora)
    {
    }

    /**
     * @return array{generados: int, omitidos: int, mes: string}
     */
    public function generar(int $mes, int $anio, ?User $usuario = null): array
    {
        $nombreMes = $this->nombreDelMes($mes, $anio);

        // Todo en una transacción: si un empleado falla, no queda media
        // planilla a medio escribir en la base de datos.
        $resultado = DB::transaction(function () use ($mes, $anio, $nombreMes, $usuario) {
            $generados = 0;
            $omitidos = 0;

            $yaProcesados = Planilla::delPeriodo($anio, $mes)->pluck('empleado_id')->all();

            Empleado::activos()->chunkById(100, function ($empleados) use (
                $mes, $anio, $nombreMes, $yaProcesados, $usuario, &$generados, &$omitidos
            ) {
                foreach ($empleados as $empleado) {
                    if (in_array($empleado->id, $yaProcesados, true)) {
                        $omitidos++;

                        continue;
                    }

                    $desglose = $this->calculadora->calcular((float) $empleado->salario_base);
                    $asistencia = $this->resumenAsistencia($empleado->id, $anio, $mes);

                    Planilla::create(array_merge($desglose->toArray(), [
                        'empleado_id' => $empleado->id,
                        'mes' => $nombreMes,
                        'mes_numero' => $mes,
                        'anio' => $anio,
                        'dias_laborados' => $asistencia['dias'],
                        'llegadas_tardias' => $asistencia['tardanzas'],
                        'generada_por' => $usuario?->id,
                    ]));

                    $generados++;
                }
            });

            return compact('generados', 'omitidos');
        });

        return $resultado + ['mes' => $nombreMes];
    }

    public function nombreDelMes(int $mes, int $anio): string
    {
        return ucfirst(Carbon::create($anio, $mes, 1)->translatedFormat('F'));
    }

    /**
     * Días efectivamente marcados y tardanzas del período (van en el recibo).
     */
    private function resumenAsistencia(int $empleadoId, int $anio, int $mes): array
    {
        $registros = Asistencia::where('empleado_id', $empleadoId)->delPeriodo($anio, $mes);

        return [
            'dias' => (clone $registros)->count(),
            'tardanzas' => (clone $registros)->tardias()->count(),
        ];
    }
}
