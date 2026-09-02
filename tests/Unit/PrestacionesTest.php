<?php

namespace Tests\Unit;

use App\Models\Departamento;
use App\Models\Empleado;
use App\Services\CalculadoraAguinaldo;
use App\Services\CalculadoraHorasExtra;
use App\Services\CalculadoraLiquidacion;
use App\Services\CalculadoraVacaciones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Verifica las prestaciones laborales contra las reglas del Código de Trabajo.
 */
class PrestacionesTest extends TestCase
{
    use RefreshDatabase;

    private function empleado(string $fechaContratacion, float $salario = 600.00): Empleado
    {
        $departamento = Departamento::firstOrCreate(['nombre' => 'Pruebas']);

        return Empleado::create([
            'nombres' => 'Empleado',
            'apellidos' => 'De Prueba',
            'dui' => str_pad((string) random_int(1, 99999999), 8, '0', STR_PAD_LEFT).'-1',
            'fecha_nacimiento' => '1990-01-01',
            'fecha_contratacion' => $fechaContratacion,
            'salario_base' => $salario,
            'departamento_id' => $departamento->id,
            'activo' => true,
        ]);
    }

    // ------------------------------------------------------------------
    // Aguinaldo
    // ------------------------------------------------------------------

    public function test_aguinaldo_de_dos_anios_de_servicio_paga_quince_dias(): void
    {
        $calculadora = app(CalculadoraAguinaldo::class);
        // Contratado el 1/1/2024, al 12/12/2026 lleva 2 años cumplidos.
        $empleado = $this->empleado('2024-01-01', 600.00);

        $desglose = $calculadora->calcular($empleado, 2026);

        $this->assertSame(2, $desglose->aniosServicio);
        $this->assertEqualsWithDelta(15.0, $desglose->diasAplicados, 0.01);
        $this->assertEqualsWithDelta(20.00, $desglose->salarioDiario, 0.01);  // 600 / 30
        $this->assertEqualsWithDelta(300.00, $desglose->montoBruto, 0.01);    // 15 * 20
        $this->assertFalse($desglose->proporcional);
    }

    public function test_aguinaldo_sube_a_diecinueve_dias_a_partir_de_tres_anios(): void
    {
        $calculadora = app(CalculadoraAguinaldo::class);
        $empleado = $this->empleado('2020-01-01', 600.00);

        $desglose = $calculadora->calcular($empleado, 2026);

        $this->assertSame(6, $desglose->aniosServicio);
        $this->assertEqualsWithDelta(19.0, $desglose->diasAplicados, 0.01);
        $this->assertEqualsWithDelta(380.00, $desglose->montoBruto, 0.01);
    }

    public function test_aguinaldo_llega_a_veintiun_dias_con_diez_anios(): void
    {
        $calculadora = app(CalculadoraAguinaldo::class);
        $empleado = $this->empleado('2010-01-01', 600.00);

        $desglose = $calculadora->calcular($empleado, 2026);

        $this->assertEqualsWithDelta(21.0, $desglose->diasAplicados, 0.01);
        $this->assertEqualsWithDelta(420.00, $desglose->montoBruto, 0.01);
    }

    public function test_quien_no_cumple_un_anio_recibe_aguinaldo_proporcional(): void
    {
        $calculadora = app(CalculadoraAguinaldo::class);
        // Contratado el 12/06/2026: 183 días al 12/12/2026.
        $empleado = $this->empleado('2026-06-12', 600.00);

        $desglose = $calculadora->calcular($empleado, 2026);

        $this->assertTrue($desglose->proporcional);
        $this->assertSame(0, $desglose->aniosServicio);
        // 15 días * (183/365) = 7.52 días
        $this->assertEqualsWithDelta(7.52, $desglose->diasAplicados, 0.05);
        $this->assertLessThan(300.00, $desglose->montoBruto);
    }

    public function test_el_aguinaldo_esta_exento_hasta_dos_salarios_minimos(): void
    {
        $calculadora = app(CalculadoraAguinaldo::class);

        // Salario bajo: el aguinaldo completo queda exento y no retiene renta.
        $modesto = $calculadora->calcular($this->empleado('2024-01-01', 600.00), 2026);
        $this->assertEqualsWithDelta($modesto->montoBruto, $modesto->montoExento, 0.01);
        $this->assertEqualsWithDelta(0.00, $modesto->montoGravado, 0.01);
        $this->assertEqualsWithDelta(0.00, $modesto->renta, 0.01);

        // Salario alto: el excedente sobre $730.00 sí tributa.
        $alto = $calculadora->calcular($this->empleado('2024-01-01', 3000.00), 2026);
        $this->assertEqualsWithDelta(730.00, $alto->montoExento, 0.01);
        $this->assertGreaterThan(0, $alto->montoGravado);
        $this->assertGreaterThan(0, $alto->renta);
        $this->assertEqualsWithDelta($alto->montoBruto - $alto->renta, $alto->montoNeto, 0.01);
    }

    // ------------------------------------------------------------------
    // Vacaciones
    // ------------------------------------------------------------------

    public function test_las_vacaciones_se_pagan_con_el_recargo_del_treinta_por_ciento(): void
    {
        $calculadora = app(CalculadoraVacaciones::class);
        $empleado = $this->empleado('2024-01-01', 600.00);

        $desglose = $calculadora->calcular($empleado, 15);

        $this->assertEqualsWithDelta(300.00, $desglose->montoBase, 0.01);   // 15 * 20
        $this->assertEqualsWithDelta(90.00, $desglose->recargo, 0.01);      // 30%
        $this->assertEqualsWithDelta(390.00, $desglose->totalPagado, 0.01);
    }

    public function test_los_dias_de_vacaciones_se_ganan_por_anio_cumplido(): void
    {
        $calculadora = app(CalculadoraVacaciones::class);

        $nuevo = $this->empleado(now()->subMonths(6)->toDateString());
        $veterano = $this->empleado(now()->subYears(3)->subDay()->toDateString());

        $this->assertSame(0, $calculadora->diasGanados($nuevo), 'Sin un año cumplido no se ganan vacaciones.');
        $this->assertSame(45, $calculadora->diasGanados($veterano), '3 años => 45 días.');
    }

    public function test_el_saldo_descuenta_las_vacaciones_ya_tomadas(): void
    {
        $calculadora = app(CalculadoraVacaciones::class);
        $empleado = $this->empleado(now()->subYears(2)->subDay()->toDateString());

        $empleado->vacaciones()->create([
            'fecha_inicio' => now()->subMonth()->toDateString(),
            'fecha_fin' => now()->subMonth()->addDays(9)->toDateString(),
            'dias' => 10,
            'salario_diario' => 20,
            'monto_base' => 200,
            'recargo' => 60,
            'total_pagado' => 260,
            'estado' => 'gozada',
        ]);

        $this->assertSame(30, $calculadora->diasGanados($empleado));
        $this->assertSame(10, $calculadora->diasTomados($empleado));
        $this->assertSame(20, $calculadora->diasDisponibles($empleado));
    }

    // ------------------------------------------------------------------
    // Horas extra
    // ------------------------------------------------------------------

    public function test_solo_hay_hora_extra_despues_de_la_jornada_y_el_refrigerio(): void
    {
        $calculadora = app(CalculadoraHorasExtra::class);

        // 9 horas marcadas - 1 h de refrigerio = 8 h efectivas: sin tiempo extra.
        $this->assertSame(0, $calculadora->minutosExtraDeLaJornada(540));

        // 10 h marcadas - 1 h = 9 h efectivas: 1 hora extra.
        $this->assertSame(60, $calculadora->minutosExtraDeLaJornada(600));
    }

    public function test_el_tiempo_extra_diario_tiene_tope(): void
    {
        $calculadora = app(CalculadoraHorasExtra::class);

        // Olvido de marcaje: 20 horas seguidas no generan 11 horas extra.
        $this->assertSame(240, $calculadora->minutosExtraDeLaJornada(1200));
    }

    public function test_la_hora_extra_se_paga_al_doble(): void
    {
        $calculadora = app(CalculadoraHorasExtra::class);
        $empleado = $this->empleado('2024-01-01', 480.00); // $16/día, $2/hora

        $desglose = $calculadora->desglosar($empleado, minutos: 120);

        $this->assertEqualsWithDelta(2.00, $desglose->valorHoraOrdinaria, 0.01);
        $this->assertEqualsWithDelta(4.00, $desglose->valorHoraExtra, 0.01);
        $this->assertEqualsWithDelta(8.00, $desglose->monto, 0.01); // 2 h * $4
    }

    // ------------------------------------------------------------------
    // Liquidación
    // ------------------------------------------------------------------

    public function test_el_despido_injustificado_indemniza_treinta_dias_por_anio(): void
    {
        $calculadora = app(CalculadoraLiquidacion::class);
        $empleado = $this->empleado('2024-01-01', 600.00);

        $desglose = $calculadora->calcular($empleado, Carbon::parse('2026-01-01'), 'despido_injustificado');

        $this->assertSame(2, $desglose->aniosServicio);
        // 2 años * 30 días * $20 = $1,200 (el salario no llega al tope legal)
        $this->assertEqualsWithDelta(1200.00, $desglose->indemnizacion, 5.00);
        $this->assertEqualsWithDelta(0.00, $desglose->prestacionRenuncia, 0.01);
    }

    public function test_la_indemnizacion_respeta_el_tope_de_cuatro_salarios_minimos(): void
    {
        $calculadora = app(CalculadoraLiquidacion::class);
        // Salario alto: el diario real ($100) supera el tope de 4 mínimos ($48.67).
        $empleado = $this->empleado('2024-01-01', 3000.00);

        $desglose = $calculadora->calcular($empleado, Carbon::parse('2026-01-01'), 'despido_injustificado');

        $this->assertEqualsWithDelta(100.00, $desglose->salarioDiario, 0.01);
        $this->assertEqualsWithDelta(48.67, $desglose->salarioDiarioTopado, 0.05);
        $this->assertLessThan(3000.00, $desglose->indemnizacion);
    }

    public function test_la_renuncia_voluntaria_no_genera_indemnizacion_por_despido(): void
    {
        $calculadora = app(CalculadoraLiquidacion::class);
        $empleado = $this->empleado('2024-01-01', 600.00);

        $desglose = $calculadora->calcular($empleado, Carbon::parse('2026-01-01'), 'renuncia_voluntaria');

        $this->assertEqualsWithDelta(0.00, $desglose->indemnizacion, 0.01);
        $this->assertGreaterThan(0, $desglose->prestacionRenuncia, 'Con 2 años sí hay prestación por renuncia.');
    }

    public function test_la_prestacion_por_renuncia_exige_dos_anios_de_antiguedad(): void
    {
        $calculadora = app(CalculadoraLiquidacion::class);
        $empleado = $this->empleado('2025-06-01', 600.00);

        $desglose = $calculadora->calcular($empleado, Carbon::parse('2026-01-01'), 'renuncia_voluntaria');

        $this->assertEqualsWithDelta(0.00, $desglose->prestacionRenuncia, 0.01);
    }

    public function test_todo_motivo_de_salida_paga_los_proporcionales(): void
    {
        $calculadora = app(CalculadoraLiquidacion::class);
        $empleado = $this->empleado('2024-01-01', 600.00);

        foreach (['despido_injustificado', 'renuncia_voluntaria', 'despido_justificado', 'mutuo_acuerdo'] as $motivo) {
            $desglose = $calculadora->calcular($empleado, Carbon::parse('2026-06-30'), $motivo);

            $this->assertGreaterThan(0, $desglose->vacacionProporcional, "Falta vacación proporcional en {$motivo}");
            $this->assertGreaterThan(0, $desglose->aguinaldoProporcional, "Falta aguinaldo proporcional en {$motivo}");
        }
    }

    public function test_rechaza_un_motivo_de_salida_inexistente(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(CalculadoraLiquidacion::class)->calcular(
            $this->empleado('2024-01-01'),
            Carbon::parse('2026-01-01'),
            'motivo_inventado'
        );
    }
}
