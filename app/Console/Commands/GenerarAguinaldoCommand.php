<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GeneradorAguinaldo;
use Illuminate\Console\Command;

/**
 * Aguinaldo desde la terminal. En el cron del servidor:
 *
 *   0 6 5 12 *  cd /home/usuario/rrhh-sistema && php artisan aguinaldo:generar
 *
 * (el 5 de diciembre, para tenerlo listo antes del plazo legal de pago).
 */
class GenerarAguinaldoCommand extends Command
{
    protected $signature = 'aguinaldo:generar
                            {--anio= : Año a procesar. Por defecto, el año actual}
                            {--usuario= : Correo del usuario al que se le atribuye el cálculo}';

    protected $description = 'Calcula el aguinaldo del año para todos los empleados activos';

    public function handle(GeneradorAguinaldo $generador): int
    {
        $anio = (int) ($this->option('anio') ?: now()->year);

        $usuario = $this->option('usuario')
            ? User::where('email', $this->option('usuario'))->first()
            : null;

        if ($this->option('usuario') && ! $usuario) {
            $this->error("No existe un usuario con el correo {$this->option('usuario')}.");

            return self::FAILURE;
        }

        $this->info("Calculando aguinaldos de {$anio}...");

        $resultado = $generador->generar($anio, $usuario);

        $this->table(
            ['Año', 'Calculados', 'Omitidos (ya existían)'],
            [[$anio, $resultado['generados'], $resultado['omitidos']]]
        );

        return self::SUCCESS;
    }
}
