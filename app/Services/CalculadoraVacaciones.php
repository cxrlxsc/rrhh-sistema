<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Vacacion;
use App\Support\DesgloseVacaciones;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Vacaciones anuales (Código de Trabajo, Arts. 177-189).
 *
 * Tras cada año continuo de trabajo el empleado gana 15 días remunerados,
 * que se pagan con un recargo del 30% sobre el salario ordinario de esos días
 * y deben cancelarse ANTES de que inicie el goce.
 */
class CalculadoraVacaciones
{
    public function calcular(Empleado $empleado, int $dias): DesgloseVacaciones
    {
        if ($dias < 1) {
            throw new InvalidArgumentException('Las vacaciones deben ser de al menos un día.');
        }

        $salarioDiario = $empleado->salarioDiario();
        $base = round($dias * $salarioDiario, 2);
        $recargo = round($base * (float) config('prestaciones.vacaciones.recargo', 0.30), 2);

        return new DesgloseVacaciones(
            dias: $dias,
            salarioDiario: $salarioDiario,
            montoBase: $base,
            recargo: $recargo,
            totalPagado: round($base + $recargo, 2),
            diasDisponibles: $this->diasDisponibles($empleado),
        );
    }

    /**
     * Días ganados: 15 por cada año continuo cumplido.
     */
    public function diasGanados(Empleado $empleado, ?CarbonInterface $corte = null): int
    {
        $anios = $empleado->antiguedadEnAnios($corte);
        $minimos = (int) config('prestaciones.vacaciones.anios_minimos', 1);

        if ($anios < $minimos) {
            return 0;
        }

        return $anios * (int) config('prestaciones.vacaciones.dias_por_anio', 15);
    }

    /**
     * Días ya gozados o programados (los cancelados no consumen saldo).
     */
    public function diasTomados(Empleado $empleado): int
    {
        return (int) $empleado->vacaciones()->vigentes()->sum('dias');
    }

    /**
     * Saldo disponible. Nunca negativo: si se otorgó más de lo ganado,
     * el saldo simplemente queda en cero.
     */
    public function diasDisponibles(Empleado $empleado, ?CarbonInterface $corte = null): int
    {
        return max(0, $this->diasGanados($empleado, $corte) - $this->diasTomados($empleado));
    }

    /**
     * Vacación proporcional para la liquidación: la fracción del año en curso
     * transcurrida desde el último aniversario laboral.
     */
    public function proporcionalALaFecha(Empleado $empleado, CarbonInterface $fecha): float
    {
        if (! $empleado->fecha_contratacion) {
            return 0.00;
        }

        $aniversario = $empleado->fecha_contratacion->copy()
            ->setYear($fecha->year);

        if ($aniversario->greaterThan($fecha)) {
            $aniversario->subYear();
        }

        $diasTranscurridos = min(365, max(0, (int) $aniversario->diffInDays($fecha)));
        $diasProporcionales = (int) config('prestaciones.vacaciones.dias_por_anio', 15) * ($diasTranscurridos / 365);

        $base = $diasProporcionales * $empleado->salarioDiario();
        $recargo = $base * (float) config('prestaciones.vacaciones.recargo', 0.30);

        return round($base + $recargo, 2);
    }

    /**
     * Verifica que un período no se traslape con otro ya registrado.
     */
    public function hayTraslape(Empleado $empleado, CarbonInterface $inicio, CarbonInterface $fin, ?int $ignorarId = null): bool
    {
        return $empleado->vacaciones()
            ->vigentes()
            ->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))
            ->where('fecha_inicio', '<=', $fin->toDateString())
            ->where('fecha_fin', '>=', $inicio->toDateString())
            ->exists();
    }

    /**
     * Estados posibles de un registro de vacaciones.
     */
    public function estados(): array
    {
        return [
            Vacacion::ESTADO_PROGRAMADA => 'Programada',
            Vacacion::ESTADO_GOZADA => 'Gozada',
            Vacacion::ESTADO_CANCELADA => 'Cancelada',
        ];
    }
}
