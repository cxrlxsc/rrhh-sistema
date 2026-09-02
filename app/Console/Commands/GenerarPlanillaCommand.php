<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GeneradorPlanilla;
use Illuminate\Console\Command;

/**
 * Genera la nómina desde la terminal. Pensado para el cron del servidor:
 *
 *   0 6 28 * *  cd /home/usuario/rrhh-sistema && php artisan planilla:generar
 */
class GenerarPlanillaCommand extends Command
{
    protected $signature = 'planilla:generar
                            {--mes= : Mes a procesar (1-12). Por defecto, el mes actual}
                            {--anio= : Año a procesar. Por defecto, el año actual}
                            {--usuario= : Correo del usuario al que se le atribuye la generación}';

    protected $description = 'Calcula la planilla del período para todos los empleados activos';

    public function handle(GeneradorPlanilla $generador): int
    {
        $mes = (int) ($this->option('mes') ?: now()->month);
        $anio = (int) ($this->option('anio') ?: now()->year);

        if ($mes < 1 || $mes > 12) {
            $this->error("El mes '{$mes}' no es válido. Usa un número del 1 al 12.");

            return self::FAILURE;
        }

        $usuario = $this->option('usuario')
            ? User::where('email', $this->option('usuario'))->first()
            : null;

        if ($this->option('usuario') && ! $usuario) {
            $this->error("No existe un usuario con el correo {$this->option('usuario')}.");

            return self::FAILURE;
        }

        $this->info("Procesando planilla de {$generador->nombreDelMes($mes, $anio)} {$anio}...");

        $resultado = $generador->generar($mes, $anio, $usuario);

        $this->table(
            ['Período', 'Generadas', 'Omitidas (ya existían)'],
            [["{$resultado['mes']} {$anio}", $resultado['generados'], $resultado['omitidos']]]
        );

        return self::SUCCESS;
    }
}
