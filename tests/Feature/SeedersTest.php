<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Empleado;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El seeder es la puerta de entrada al proyecto (y a la demo del portafolio):
 * si se rompe, nadie puede levantar el sistema. Por eso se prueba.
 */
class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_deja_el_sistema_listo_para_usarse(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Datos de demostración
        $this->assertGreaterThanOrEqual(5, Empleado::count());
        $this->assertGreaterThan(0, Asistencia::count());

        // Las tres cuentas con su rol correspondiente
        $this->assertTrue(User::where('email', 'admin@rrhh.test')->first()->hasRole(User::ROL_ADMIN));
        $this->assertTrue(User::where('email', 'rrhh@rrhh.test')->first()->hasRole(User::ROL_RRHH));

        $empleado = User::where('email', 'empleado@rrhh.test')->first();
        $this->assertTrue($empleado->hasRole(User::ROL_EMPLEADO));
        $this->assertNotNull($empleado->empleado_id, 'La cuenta de empleado debe quedar enlazada a su ficha.');
    }

    public function test_el_seeder_es_idempotente(): void
    {
        $this->seed(DatabaseSeeder::class);
        $conteoInicial = Empleado::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($conteoInicial, Empleado::count(), 'Volver a sembrar no debe duplicar empleados.');
    }
}
