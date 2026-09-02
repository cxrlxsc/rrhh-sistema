<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Support\ResultadoMarcaje;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio del reloj marcador.
 *
 * Vive fuera del controlador porque concentra las decisiones delicadas:
 * tardanzas, doble escaneo (anti-spam), cierre de jornada y concurrencia.
 */
class RegistroAsistencia
{
    /**
     * Procesa el escaneo de un gafete y devuelve qué ocurrió.
     */
    public function marcar(Empleado $empleado, ?string $ip = null, ?CarbonInterface $momento = null): ResultadoMarcaje
    {
        $ahora = $momento ? Carbon::parse($momento) : Carbon::now();
        $hoy = $ahora->copy()->startOfDay();

        return DB::transaction(function () use ($empleado, $ip, $ahora, $hoy) {
            // lockForUpdate evita que dos escaneos simultáneos creen dos filas.
            $asistencia = Asistencia::where('empleado_id', $empleado->id)
                ->whereDate('fecha', $hoy->toDateString())
                ->lockForUpdate()
                ->first();

            if ($asistencia && ($bloqueo = $this->verificarCooldown($asistencia, $empleado, $ahora))) {
                return $bloqueo;
            }

            if (! $asistencia) {
                return $this->registrarEntrada($empleado, $ip, $ahora, $hoy);
            }

            if ($asistencia->hora_salida) {
                return ResultadoMarcaje::duplicado(
                    "{$empleado->nombres}, tu jornada de hoy ya está cerrada ".
                    "({$this->formatoHora($asistencia->hora_entrada)} - {$this->formatoHora($asistencia->hora_salida)})."
                );
            }

            return $this->registrarSalida($asistencia, $empleado, $ip, $ahora);
        });
    }

    /**
     * ANTI-SPAM: si el empleado ya marcó hace menos de N segundos, se ignora
     * el escaneo. Esto evita que un lector que dispara dos veces seguidas
     * cierre la jornada un segundo después de haberla abierto.
     */
    private function verificarCooldown(Asistencia $asistencia, Empleado $empleado, CarbonInterface $ahora): ?ResultadoMarcaje
    {
        $cooldown = (int) config('asistencia.cooldown_segundos');
        $ultimo = $asistencia->ultimo_marcaje_at;

        if ($cooldown <= 0 || ! $ultimo) {
            return null;
        }

        $transcurridos = (int) $ultimo->diffInSeconds($ahora, absolute: true);

        if ($transcurridos >= $cooldown) {
            return null;
        }

        $restantes = $cooldown - $transcurridos;

        return ResultadoMarcaje::bloqueado(
            "{$empleado->nombres}, tu marcaje ya fue registrado hace un momento. ".
            "Espera {$restantes} segundo(s) antes de volver a escanear."
        );
    }

    private function registrarEntrada(Empleado $empleado, ?string $ip, CarbonInterface $ahora, CarbonInterface $hoy): ResultadoMarcaje
    {
        $limite = $this->horaLimiteEntrada($hoy);
        $minutosTarde = $ahora->greaterThan($limite) ? (int) $limite->diffInMinutes($ahora, absolute: true) : 0;

        Asistencia::create([
            'empleado_id' => $empleado->id,
            'fecha' => $hoy->toDateString(),
            'hora_entrada' => $ahora->toTimeString(),
            'ultimo_marcaje_at' => $ahora,
            'ip_marcaje' => $ip,
            'estado_entrada' => $minutosTarde > 0 ? Asistencia::ESTADO_TARDE : Asistencia::ESTADO_PUNTUAL,
            'minutos_tarde' => $minutosTarde,
        ]);

        $saludo = $minutosTarde > 0
            ? "Entrada registrada con {$minutosTarde} min de retraso."
            : '¡Entrada registrada a tiempo!';

        return ResultadoMarcaje::entrada(
            "{$saludo} {$ahora->format('h:i A')} · Bienvenido/a, {$empleado->nombres}."
        );
    }

    private function registrarSalida(Asistencia $asistencia, Empleado $empleado, ?string $ip, CarbonInterface $ahora): ResultadoMarcaje
    {
        $entrada = Carbon::parse($asistencia->fecha->toDateString().' '.$asistencia->hora_entrada);
        $minutosTrabajados = (int) $entrada->diffInMinutes($ahora, absolute: true);
        $jornadaMinima = (int) config('asistencia.jornada_minima_min');

        // Segundo candado anti-error: nadie cierra su jornada a los 5 minutos
        // de haber entrado; casi siempre es un re-escaneo accidental.
        if ($jornadaMinima > 0 && $minutosTrabajados < $jornadaMinima) {
            return ResultadoMarcaje::bloqueado(
                "{$empleado->nombres}, tu entrada fue hace {$minutosTrabajados} min. ".
                "La salida solo puede marcarse después de {$jornadaMinima} min de jornada."
            );
        }

        $asistencia->update([
            'hora_salida' => $ahora->toTimeString(),
            'ultimo_marcaje_at' => $ahora,
            'ip_marcaje' => $ip,
            'minutos_trabajados' => $minutosTrabajados,
        ]);

        $jornada = intdiv($minutosTrabajados, 60).'h '.($minutosTrabajados % 60).'m';

        return ResultadoMarcaje::salida(
            "Salida registrada: {$ahora->format('h:i A')} · Jornada de {$jornada}. ¡Hasta pronto, {$empleado->nombres}!"
        );
    }

    /**
     * Hora a partir de la cual se considera tardanza (incluye la tolerancia).
     */
    public function horaLimiteEntrada(CarbonInterface $dia): CarbonInterface
    {
        [$hora, $minuto] = array_pad(explode(':', (string) config('asistencia.hora_limite_entrada', '08:00')), 2, '0');

        return Carbon::parse($dia)
            ->startOfDay()
            ->setTime((int) $hora, (int) $minuto)
            ->addMinutes((int) config('asistencia.tolerancia_minutos', 0));
    }

    private function formatoHora(?string $hora): string
    {
        return $hora ? Carbon::parse($hora)->format('h:i A') : '—';
    }
}
