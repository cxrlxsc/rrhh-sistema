<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Support\DesgloseHorasExtra;

/**
 * Tiempo extraordinario (Código de Trabajo, Arts. 161-170).
 *
 * Es el puente entre el módulo de asistencia y el de nómina: el kiosco
 * registra los minutos reales de cada jornada, aquí se convierten en horas
 * extra y de ahí pasan a la planilla como ingreso gravable.
 *
 * La hora extra diurna se paga con un recargo del 100% (el doble de la hora
 * ordinaria).
 */
class CalculadoraHorasExtra
{
    /**
     * Minutos extraordinarios de una jornada.
     *
     * Primero se descuenta el refrigerio y solo después se compara contra la
     * jornada ordinaria; el tope diario evita que un olvido de marcaje de
     * salida se convierta en una jornada de catorce horas.
     */
    public function minutosExtraDeLaJornada(int $minutosTrabajados): int
    {
        $descanso = (int) config('prestaciones.horas_extra.descanso_minutos', 60);
        $jornada = (int) config('prestaciones.horas_extra.jornada_ordinaria_minutos', 480);
        $tope = (int) config('prestaciones.horas_extra.maximo_minutos_dia', 240);

        $efectivos = max(0, $minutosTrabajados - $descanso);

        return (int) min(max(0, $efectivos - $jornada), $tope);
    }

    /**
     * Tiempo extraordinario acumulado por un empleado en un período.
     */
    public function delPeriodo(Empleado $empleado, int $anio, int $mes): DesgloseHorasExtra
    {
        $registros = Asistencia::where('empleado_id', $empleado->id)
            ->delPeriodo($anio, $mes)
            ->where('minutos_extra', '>', 0);

        $minutos = (int) (clone $registros)->sum('minutos_extra');
        $dias = (clone $registros)->count();

        return $this->desglosar($empleado, $minutos, $dias);
    }

    /**
     * Convierte minutos extra en dinero para un empleado dado.
     */
    public function desglosar(Empleado $empleado, int $minutos, int $dias = 0): DesgloseHorasExtra
    {
        $valorHora = $empleado->salarioHora();
        $recargo = (float) config('prestaciones.horas_extra.recargo_diurna', 1.00);
        $valorHoraExtra = round($valorHora * (1 + $recargo), 4);

        $horas = round($minutos / 60, 2);

        return new DesgloseHorasExtra(
            minutos: $minutos,
            horas: $horas,
            valorHoraOrdinaria: $valorHora,
            valorHoraExtra: $valorHoraExtra,
            monto: round($horas * $valorHoraExtra, 2),
            diasConTiempoExtra: $dias,
        );
    }
}
