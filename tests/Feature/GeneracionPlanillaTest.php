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

class GeneracionPlanillaTest extends TestCase
{
    use RefreshDatabase;

    private User $rrhh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->rrhh = User::factory()->create();
        $this->rrhh->assignRole(User::ROL_RRHH);

        $departamento = Departamento::create(['nombre' => 'Recursos Humanos']);

        foreach ([['01234567-8', 1200.00], ['02345678-9', 365.00]] as $i => [$dui, $salario]) {
            Empleado::create([
                'nombres' => "Empleado {$i}",
                'apellidos' => 'De Prueba',
                'dui' => $dui,
                'fecha_nacimiento' => '1993-01-01',
                'fecha_contratacion' => '2024-01-01',
                'salario_base' => $salario,
                'departamento_id' => $departamento->id,
                'activo' => true,
            ]);
        }
    }

    public function test_genera_una_planilla_por_empleado_activo_con_la_renta_calculada(): void
    {
        $this->actingAs($this->rrhh)
             ->post(route('planillas.generar'), ['mes' => 8, 'anio' => 2026])
             ->assertRedirect();

        $this->assertSame(2, Planilla::count());

        $recibo = Planilla::whereRelation('empleado', 'dui', '01234567-8')->first();

        $this->assertEqualsWithDelta(30.00, (float) $recibo->descuento_isss, 0.01);
        $this->assertEqualsWithDelta(87.00, (float) $recibo->descuento_afp, 0.01);
        $this->assertEqualsWithDelta(97.55, (float) $recibo->descuento_renta, 0.01);
        $this->assertSame('III', $recibo->tramo_renta);
        $this->assertEqualsWithDelta(985.45, (float) $recibo->salario_liquido, 0.01);
        $this->assertSame('Agosto', $recibo->mes);
        $this->assertSame($this->rrhh->id, $recibo->generada_por);
    }

    public function test_no_duplica_la_planilla_del_mismo_periodo(): void
    {
        $datos = ['mes' => 8, 'anio' => 2026];

        $this->actingAs($this->rrhh)->post(route('planillas.generar'), $datos);
        $this->actingAs($this->rrhh)->post(route('planillas.generar'), $datos);

        $this->assertSame(2, Planilla::count());
    }

    public function test_permite_generar_periodos_distintos(): void
    {
        $this->actingAs($this->rrhh)->post(route('planillas.generar'), ['mes' => 7, 'anio' => 2026]);
        $this->actingAs($this->rrhh)->post(route('planillas.generar'), ['mes' => 8, 'anio' => 2026]);

        $this->assertSame(4, Planilla::count());
        $this->assertSame(2, Planilla::delPeriodo(2026, 7)->count());
    }

    public function test_los_empleados_inactivos_quedan_fuera_de_la_nomina(): void
    {
        Empleado::where('dui', '02345678-9')->update(['activo' => false]);

        $this->actingAs($this->rrhh)->post(route('planillas.generar'), ['mes' => 8, 'anio' => 2026]);

        $this->assertSame(1, Planilla::count());
    }

    public function test_un_empleado_sin_permisos_no_puede_generar_planilla(): void
    {
        $usuario = User::factory()->create();
        $usuario->assignRole(User::ROL_EMPLEADO);

        $this->actingAs($usuario)
             ->post(route('planillas.generar'), ['mes' => 8, 'anio' => 2026])
             ->assertRedirect(route('dashboard'));

        $this->assertSame(0, Planilla::count());
    }

    public function test_el_comando_de_consola_genera_la_misma_planilla(): void
    {
        $this->artisan('planilla:generar', ['--mes' => 8, '--anio' => 2026])
             ->assertSuccessful();

        $this->assertSame(2, Planilla::delPeriodo(2026, 8)->count());

        $recibo = Planilla::whereRelation('empleado', 'dui', '01234567-8')->first();
        $this->assertEqualsWithDelta(97.55, (float) $recibo->descuento_renta, 0.01);
    }

    public function test_el_comando_no_reprocesa_un_periodo_ya_generado(): void
    {
        $this->artisan('planilla:generar', ['--mes' => 8, '--anio' => 2026]);
        $this->artisan('planilla:generar', ['--mes' => 8, '--anio' => 2026])->assertSuccessful();

        $this->assertSame(2, Planilla::count());
    }

    public function test_el_comando_rechaza_un_mes_invalido(): void
    {
        $this->artisan('planilla:generar', ['--mes' => 15])->assertFailed();

        $this->assertSame(0, Planilla::count());
    }

    public function test_rechaza_un_periodo_invalido(): void
    {
        $this->actingAs($this->rrhh)
             ->post(route('planillas.generar'), ['mes' => 13, 'anio' => 2026])
             ->assertSessionHasErrors('mes');
    }
}
