<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El registro público está desactivado a propósito (ver routes/auth.php).
 *
 * En un sistema de RRHH las cuentas las crea el administrador, que además
 * asigna el rol y enlaza la ficha de empleado. Alguien que se autoregistrara
 * quedaría sin rol y sin ficha: no podría ver nada y toparía con un 403.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pantalla_de_registro_publico_no_existe(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_no_se_pueden_crear_cuentas_desde_fuera(): void
    {
        $this->post('/register', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_la_ruta_con_nombre_register_ya_no_esta_registrada(): void
    {
        // La vista de bienvenida usa Route::has('register') para decidir si
        // muestra el botón: al no existir la ruta, el botón desaparece solo.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('register'));
    }

    public function test_el_administrador_si_puede_crear_cuentas(): void
    {
        // La vía correcta es /usuarios, cubierta por RolesYPermisosTest y
        // VistasAdministrativasTest. Aquí solo se confirma que el modelo
        // sigue permitiendo crear usuarios desde dentro del sistema.
        $usuario = User::factory()->create(['email' => 'nuevo@empresa.com']);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@empresa.com']);
        $this->assertNotNull($usuario->id);
    }
}
