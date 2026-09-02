<?php

namespace Tests\Feature;

use App\Models\Aguinaldo;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Liquidacion;
use App\Models\User;
use App\Models\Vacacion;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PrestacionesFlujoTest extends TestCase
{
    use RefreshDatabase;

    private User $rrhh;

    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->rrhh = User::factory()->create();
        $this->rrhh->assignRole(User::ROL_RRHH);

        $departamento = Departamento::create(['nombre' => 'Operaciones']);

        $this->empleado = Empleado::create([
            'nombres' => 'Ana',
            'apellidos' => 'Martínez',
            'dui' => '01234567-8',
            'fecha_nacimiento' => '1992-04-10',
            'fecha_contratacion' => now()->subYears(4)->toDateString(),
            'salario_base' => 900.00,
            'departamento_id' => $departamento->id,
            'activo' => true,
        ]);
    }

    // ------------------------------------------------------------------
    // Aguinaldo
    // ------------------------------------------------------------------

    public function test_rrhh_genera_el_aguinaldo_de_todos_los_activos(): void
    {
        $this->actingAs($this->rrhh)
             ->post(route('aguinaldos.generar'), ['anio' => 2026])
             ->assertRedirect();

        $this->assertSame(1, Aguinaldo::count());
        $this->assertSame(19, (int) Aguinaldo::first()->dias_aplicados); // 4 años => 19 días
    }

    public function test_el_aguinaldo_no_se_calcula_dos_veces_para_el_mismo_anio(): void
    {
        $this->actingAs($this->rrhh)->post(route('aguinaldos.generar'), ['anio' => 2026]);
        $this->actingAs($this->rrhh)->post(route('aguinaldos.generar'), ['anio' => 2026]);

        $this->assertSame(1, Aguinaldo::count());
    }

    // ------------------------------------------------------------------
    // Vacaciones
    // ------------------------------------------------------------------

    public function test_registra_vacaciones_con_el_recargo_legal(): void
    {
        $this->actingAs($this->rrhh)
             ->post(route('vacaciones.store'), [
                 'empleado_id' => $this->empleado->id,
                 'fecha_inicio' => now()->addWeek()->toDateString(),
                 'dias' => 15,
             ])
             ->assertRedirect(route('vacaciones.index'));

        $vacacion = Vacacion::first();

        $this->assertNotNull($vacacion);
        $this->assertEqualsWithDelta(450.00, (float) $vacacion->monto_base, 0.01);   // 15 * $30
        $this->assertEqualsWithDelta(135.00, (float) $vacacion->recargo, 0.01);      // 30%
        $this->assertEqualsWithDelta(585.00, (float) $vacacion->total_pagado, 0.01);
    }

    public function test_no_se_pueden_otorgar_mas_dias_de_los_ganados(): void
    {
        // Con 4 años tiene 60 días ganados; se piden 30 dos veces y luego más.
        $this->actingAs($this->rrhh)->post(route('vacaciones.store'), [
            'empleado_id' => $this->empleado->id,
            'fecha_inicio' => now()->addMonths(1)->toDateString(),
            'dias' => 30,
        ]);

        $this->actingAs($this->rrhh)->post(route('vacaciones.store'), [
            'empleado_id' => $this->empleado->id,
            'fecha_inicio' => now()->addMonths(6)->toDateString(),
            'dias' => 30,
        ]);

        $this->actingAs($this->rrhh)
             ->post(route('vacaciones.store'), [
                 'empleado_id' => $this->empleado->id,
                 'fecha_inicio' => now()->addMonths(10)->toDateString(),
                 'dias' => 5,
             ])
             ->assertSessionHasErrors('dias');

        $this->assertSame(2, Vacacion::count());
    }

    public function test_no_se_permiten_periodos_traslapados(): void
    {
        $inicio = now()->addMonth();

        $this->actingAs($this->rrhh)->post(route('vacaciones.store'), [
            'empleado_id' => $this->empleado->id,
            'fecha_inicio' => $inicio->toDateString(),
            'dias' => 10,
        ]);

        $this->actingAs($this->rrhh)
             ->post(route('vacaciones.store'), [
                 'empleado_id' => $this->empleado->id,
                 'fecha_inicio' => $inicio->copy()->addDays(3)->toDateString(),
                 'dias' => 5,
             ])
             ->assertSessionHasErrors('fecha_inicio');

        $this->assertSame(1, Vacacion::count());
    }

    // ------------------------------------------------------------------
    // Liquidación
    // ------------------------------------------------------------------

    public function test_el_calculo_previo_del_finiquito_no_guarda_nada(): void
    {
        $this->actingAs($this->rrhh)
             ->post(route('liquidaciones.create'), [
                 'empleado_id' => $this->empleado->id,
                 'fecha_salida' => now()->toDateString(),
                 'motivo' => 'despido_injustificado',
             ])
             ->assertOk();

        $this->assertSame(0, Liquidacion::count());
        $this->assertTrue($this->empleado->fresh()->activo);
    }

    public function test_confirmar_la_liquidacion_da_de_baja_al_empleado(): void
    {
        $fechaSalida = now()->toDateString();

        $this->actingAs($this->rrhh)
             ->post(route('liquidaciones.store'), [
                 'empleado_id' => $this->empleado->id,
                 'fecha_salida' => $fechaSalida,
                 'motivo' => 'despido_injustificado',
                 'salarios_pendientes' => 300,
             ])
             ->assertRedirect(route('liquidaciones.index'));

        $liquidacion = Liquidacion::first();
        $empleado = $this->empleado->fresh();

        $this->assertNotNull($liquidacion);
        $this->assertGreaterThan(0, (float) $liquidacion->indemnizacion);
        $this->assertFalse($empleado->activo, 'El empleado debe quedar dado de baja.');
        $this->assertSame($fechaSalida, $empleado->fecha_salida->toDateString());
        $this->assertSame('despido_injustificado', $empleado->motivo_salida);
    }

    // ------------------------------------------------------------------
    // Exportaciones
    // ------------------------------------------------------------------

    public function test_la_pantalla_de_exportaciones_carga(): void
    {
        $this->actingAs($this->rrhh)->get(route('exportaciones.index'))->assertOk();
    }

    public function test_exporta_la_planilla_del_isss_en_csv(): void
    {
        $this->actingAs($this->rrhh)->post(route('planillas.generar'), ['mes' => 8, 'anio' => 2026]);

        $respuesta = $this->actingAs($this->rrhh)->post(route('exportaciones.descargar'), [
            'tipo' => 'isss',
            'anio' => 2026,
            'mes' => 8,
        ]);

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('No. Afiliacion ISSS', $contenido);
        $this->assertStringContainsString('Martínez', $contenido);
    }

    public function test_avisa_si_el_periodo_no_tiene_planilla_generada(): void
    {
        $this->actingAs($this->rrhh)
             ->post(route('exportaciones.descargar'), ['tipo' => 'afp', 'anio' => 2026, 'mes' => 3])
             ->assertSessionHas('error');
    }

    // ------------------------------------------------------------------
    // Autoservicio del empleado
    // ------------------------------------------------------------------

    public function test_el_empleado_consulta_sus_propias_prestaciones(): void
    {
        $usuario = User::factory()->create(['empleado_id' => $this->empleado->id]);
        $usuario->assignRole(User::ROL_EMPLEADO);

        $this->actingAs($usuario)->get(route('autoservicio.prestaciones'))->assertOk();

        // No puede entrar al módulo administrativo de prestaciones.
        $this->actingAs($usuario)->get(route('aguinaldos.index'))->assertRedirect(route('dashboard'));
    }

    public function test_el_empleado_descarga_su_aguinaldo_pero_no_el_de_otro(): void
    {
        $this->actingAs($this->rrhh)->post(route('aguinaldos.generar'), ['anio' => 2026]);
        $propio = Aguinaldo::first();

        $otroEmpleado = Empleado::create([
            'nombres' => 'Otro', 'apellidos' => 'Empleado', 'dui' => '09876543-2',
            'fecha_nacimiento' => '1991-01-01', 'fecha_contratacion' => now()->subYears(5)->toDateString(),
            'salario_base' => 800, 'departamento_id' => $this->empleado->departamento_id, 'activo' => true,
        ]);

        $ajeno = Aguinaldo::create([
            'empleado_id' => $otroEmpleado->id, 'anio' => 2026, 'anios_servicio' => 5,
            'dias_aplicados' => 19, 'salario_diario' => 26.6667, 'monto_bruto' => 506.67,
            'monto_exento' => 506.67, 'monto_gravado' => 0, 'descuento_renta' => 0, 'monto_neto' => 506.67,
        ]);

        $usuario = User::factory()->create(['empleado_id' => $this->empleado->id]);
        $usuario->assignRole(User::ROL_EMPLEADO);

        $this->actingAs($usuario)->get(route('aguinaldos.pdf', $propio))->assertOk();
        $this->actingAs($usuario)->get(route('aguinaldos.pdf', $ajeno))->assertForbidden();
    }
}
