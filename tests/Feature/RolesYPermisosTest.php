<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Planilla;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolesYPermisosTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $departamento = Departamento::create(['nombre' => 'Ventas']);

        $this->empleado = Empleado::create([
            'nombres' => 'Ana',
            'apellidos' => 'Martínez',
            'dui' => '01234567-8',
            'fecha_nacimiento' => '1994-05-10',
            'fecha_contratacion' => '2023-02-01',
            'salario_base' => 900.00,
            'departamento_id' => $departamento->id,
            'activo' => true,
        ]);
    }

    private function usuarioCon(string $rol, ?Empleado $empleado = null): User
    {
        $user = User::factory()->create(['empleado_id' => $empleado?->id]);
        $user->assignRole($rol);

        return $user;
    }

    public function test_el_administrador_entra_a_todos_los_modulos(): void
    {
        $admin = $this->usuarioCon(User::ROL_ADMIN);

        $this->actingAs($admin)->get(route('planillas.index'))->assertOk();
        $this->actingAs($admin)->get(route('empleados.index'))->assertOk();
        $this->actingAs($admin)->get(route('asistencias.index'))->assertOk();
        $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();
    }

    public function test_rrhh_opera_el_sistema_pero_no_administra_cuentas(): void
    {
        $rrhh = $this->usuarioCon(User::ROL_RRHH);

        $this->actingAs($rrhh)->get(route('planillas.index'))->assertOk();
        $this->actingAs($rrhh)->get(route('empleados.index'))->assertOk();

        // Gestión de usuarios es exclusiva del administrador.
        $this->actingAs($rrhh)->get(route('usuarios.index'))->assertRedirect(route('dashboard'));
    }

    public function test_el_empleado_no_ve_la_planilla_de_toda_la_empresa(): void
    {
        $empleado = $this->usuarioCon(User::ROL_EMPLEADO, $this->empleado);

        $this->actingAs($empleado)->get(route('planillas.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($empleado)->get(route('empleados.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($empleado)->get(route('asistencias.index'))->assertRedirect(route('dashboard'));
    }

    public function test_el_empleado_si_accede_a_su_propio_autoservicio(): void
    {
        $empleado = $this->usuarioCon(User::ROL_EMPLEADO, $this->empleado);

        $this->actingAs($empleado)->get(route('planillas.mios'))->assertOk();
        $this->actingAs($empleado)->get(route('asistencias.mias'))->assertOk();
    }

    public function test_el_panel_redirige_al_empleado_a_su_autoservicio(): void
    {
        $empleado = $this->usuarioCon(User::ROL_EMPLEADO, $this->empleado);

        $this->actingAs($empleado)->get(route('dashboard'))->assertRedirect(route('planillas.mios'));
    }

    public function test_un_empleado_no_puede_descargar_el_recibo_de_otro(): void
    {
        $otroEmpleado = Empleado::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Hernández',
            'dui' => '02345678-9',
            'fecha_nacimiento' => '1990-03-15',
            'fecha_contratacion' => '2022-06-01',
            'salario_base' => 1500.00,
            'departamento_id' => $this->empleado->departamento_id,
            'activo' => true,
        ]);

        $reciboAjeno = Planilla::create([
            'empleado_id' => $otroEmpleado->id,
            'mes' => 'Agosto', 'mes_numero' => 8, 'anio' => 2026,
            'salario_base' => 1500, 'total_devengado' => 1500,
            'descuento_isss' => 30, 'descuento_afp' => 108.75,
            'renta_base_imponible' => 1361.25, 'descuento_renta' => 153.20, 'tramo_renta' => 'III',
            'total_deducciones' => 291.95, 'salario_liquido' => 1208.05,
        ]);

        $usuario = $this->usuarioCon(User::ROL_EMPLEADO, $this->empleado);

        $this->actingAs($usuario)->get(route('planillas.pdf', $reciboAjeno))->assertForbidden();
    }

    public function test_un_empleado_si_puede_descargar_su_propio_recibo(): void
    {
        $recibo = Planilla::create([
            'empleado_id' => $this->empleado->id,
            'mes' => 'Agosto', 'mes_numero' => 8, 'anio' => 2026,
            'salario_base' => 900, 'total_devengado' => 900,
            'descuento_isss' => 27, 'descuento_afp' => 65.25,
            'renta_base_imponible' => 807.75, 'descuento_renta' => 51.25, 'tramo_renta' => 'II',
            'total_deducciones' => 143.50, 'salario_liquido' => 756.50,
        ]);

        $usuario = $this->usuarioCon(User::ROL_EMPLEADO, $this->empleado);

        $this->actingAs($usuario)->get(route('planillas.pdf', $recibo))->assertOk();
    }

    public function test_un_invitado_no_entra_a_ningun_modulo_interno(): void
    {
        $this->get(route('planillas.index'))->assertRedirect(route('login'));
        $this->get(route('empleados.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
