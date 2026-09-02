<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empleado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KioscoAsistenciaTest extends TestCase
{
    use RefreshDatabase;

    private Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();

        $departamento = Departamento::create(['nombre' => 'Tecnología']);

        $this->empleado = Empleado::create([
            'nombres' => 'Jorge',
            'apellidos' => 'Menjívar',
            'dui' => '04567890-1',
            'fecha_nacimiento' => '1995-01-01',
            'fecha_contratacion' => '2024-01-01',
            'salario_base' => 1200.00,
            'departamento_id' => $departamento->id,
            'activo' => true,
        ]);
    }

    public function test_el_kiosco_es_publico_y_no_exige_sesion(): void
    {
        $this->get(route('asistencias.kiosco'))->assertOk();
    }

    public function test_registra_la_entrada_como_puntual_antes_de_la_hora_limite(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 45));

        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1'])
             ->assertRedirect(route('asistencias.kiosco'))
             ->assertSessionHas('success');

        $asistencia = Asistencia::first();

        $this->assertSame(Asistencia::ESTADO_PUNTUAL, $asistencia->estado_entrada);
        $this->assertSame(0, $asistencia->minutos_tarde);
        $this->assertNull($asistencia->hora_salida);
    }

    public function test_marca_llegada_tardia_pasada_la_tolerancia(): void
    {
        // Límite 08:00 + 5 min de tolerancia => 08:20 son 15 minutos tarde.
        Carbon::setTestNow(Carbon::today()->setTime(8, 20));

        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1']);

        $asistencia = Asistencia::first();

        $this->assertSame(Asistencia::ESTADO_TARDE, $asistencia->estado_entrada);
        $this->assertSame(15, $asistencia->minutos_tarde);
    }

    /**
     * ANTI-SPAM: el caso que motivó la validación. Un lector que dispara dos
     * veces no debe cerrar la jornada un segundo después de abrirla.
     */
    public function test_el_segundo_escaneo_inmediato_es_ignorado(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 45));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1']);

        // Mismo gafete, 3 segundos después
        Carbon::setTestNow(Carbon::today()->setTime(7, 45, 3));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1'])
             ->assertSessionHas('warning');

        $this->assertSame(1, Asistencia::count());
        $this->assertNull(Asistencia::first()->hora_salida, 'La jornada no debió cerrarse con el doble escaneo.');
    }

    public function test_no_permite_cerrar_la_jornada_antes_del_minimo_configurado(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 45));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1']);

        // Pasó el cooldown (60 s) pero no la jornada mínima (60 min).
        Carbon::setTestNow(Carbon::today()->setTime(8, 15));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1'])
             ->assertSessionHas('warning');

        $this->assertNull(Asistencia::first()->hora_salida);
    }

    public function test_registra_la_salida_y_calcula_la_jornada(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 45));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1']);

        Carbon::setTestNow(Carbon::today()->setTime(16, 45));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1'])
             ->assertSessionHas('success');

        $asistencia = Asistencia::first();

        $this->assertNotNull($asistencia->hora_salida);
        $this->assertSame(540, $asistencia->minutos_trabajados); // 9 horas
    }

    public function test_avisa_cuando_la_jornada_del_dia_ya_esta_cerrada(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 45));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1']);

        Carbon::setTestNow(Carbon::today()->setTime(16, 45));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1']);

        Carbon::setTestNow(Carbon::today()->setTime(17, 30));
        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1'])
             ->assertSessionHas('warning');

        $this->assertSame(1, Asistencia::count());
    }

    public function test_acepta_el_dui_sin_guion_porque_algunos_lectores_lo_envian_asi(): void
    {
        Carbon::setTestNow(Carbon::today()->setTime(7, 45));

        $this->post(route('asistencias.marcar'), ['dui' => '045678901'])
             ->assertSessionHas('success');

        $this->assertSame(1, Asistencia::count());
    }

    public function test_rechaza_un_gafete_desconocido_sin_revelar_informacion(): void
    {
        $this->post(route('asistencias.marcar'), ['dui' => '99999999-9'])
             ->assertSessionHas('error');

        $this->assertSame(0, Asistencia::count());
    }

    public function test_un_empleado_inactivo_no_puede_marcar(): void
    {
        $this->empleado->update(['activo' => false]);

        $this->post(route('asistencias.marcar'), ['dui' => '04567890-1'])
             ->assertSessionHas('error');

        $this->assertSame(0, Asistencia::count());
    }

    public function test_el_token_del_kiosco_bloquea_dispositivos_no_autorizados(): void
    {
        config()->set('asistencia.token', 'token-secreto');

        $this->get(route('asistencias.kiosco'))->assertForbidden();
        $this->get(route('asistencias.kiosco', ['token' => 'token-secreto']))->assertOk();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
