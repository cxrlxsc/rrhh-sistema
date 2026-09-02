<?php

namespace Tests\Feature;

use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pruebas de humo: cada pantalla administrativa debe renderizar sin errores
 * de Blade. Barato de mantener y atrapa variables faltantes en las vistas.
 */
class VistasAdministrativasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROL_ADMIN);

        $departamento = Departamento::create(['nombre' => 'Operaciones']);

        $this->empleado = Empleado::create([
            'nombres' => 'Rocío',
            'apellidos' => 'Portillo',
            'dui' => '05678901-2',
            'fecha_nacimiento' => '1992-08-20',
            'fecha_contratacion' => '2023-01-15',
            'salario_base' => 3800.00,
            'departamento_id' => $departamento->id,
            'activo' => true,
        ]);
    }

    public static function rutasAdministrativas(): array
    {
        return [
            'panel' => ['dashboard'],
            'directorio' => ['empleados.index'],
            'alta de empleado' => ['empleados.create'],
            'departamentos' => ['departamentos.index'],
            'nuevo departamento' => ['departamentos.create'],
            'planillas' => ['planillas.index'],
            'asistencia' => ['asistencias.index'],
            'usuarios' => ['usuarios.index'],
            'nueva cuenta' => ['usuarios.create'],
        ];
    }

    #[DataProvider('rutasAdministrativas')]
    public function test_las_pantallas_administrativas_cargan(string $ruta): void
    {
        $this->actingAs($this->admin)->get(route($ruta))->assertOk();
    }

    public function test_las_pantallas_de_un_empleado_especifico_cargan(): void
    {
        $this->actingAs($this->admin)->get(route('empleados.show', $this->empleado))->assertOk();
        $this->actingAs($this->admin)->get(route('empleados.edit', $this->empleado))->assertOk();
        $this->actingAs($this->admin)->get(route('empleados.credencial', $this->empleado))->assertOk();
    }

    public function test_el_alta_de_empleado_valida_el_formato_del_dui(): void
    {
        $this->actingAs($this->admin)
             ->post(route('empleados.store'), [
                 'nombres' => 'Nuevo',
                 'apellidos' => 'Empleado',
                 'dui' => 'no-es-un-dui',
                 'fecha_nacimiento' => '2000-01-01',
                 'fecha_contratacion' => '2024-01-01',
                 'salario_base' => 600,
                 'departamento_id' => $this->empleado->departamento_id,
             ])
             ->assertSessionHasErrors('dui');
    }

    public function test_dar_de_baja_conserva_el_registro_del_empleado(): void
    {
        $this->actingAs($this->admin)->delete(route('empleados.destroy', $this->empleado));

        $this->assertDatabaseHas('empleados', ['id' => $this->empleado->id, 'activo' => false]);
    }

    public function test_no_se_elimina_un_departamento_con_empleados(): void
    {
        $this->actingAs($this->admin)
             ->delete(route('departamentos.destroy', $this->empleado->departamento_id))
             ->assertSessionHas('error');

        $this->assertDatabaseHas('departamentos', ['id' => $this->empleado->departamento_id]);
    }
}
