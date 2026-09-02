<?php

namespace Tests\Unit;

use App\Services\CalculadoraNomina;
use Tests\TestCase;

/**
 * Verifica la matemática de la planilla contra las tablas oficiales.
 * Si una reforma cambia las tasas o los tramos, estos tests fallan primero.
 */
class CalculadoraNominaTest extends TestCase
{
    private CalculadoraNomina $calculadora;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculadora = new CalculadoraNomina();
    }

    public function test_tramo_uno_no_retiene_renta(): void
    {
        // Salario mínimo: la base imponible queda debajo de $472.00
        $desglose = $this->calculadora->calcular(365.00);

        $this->assertEqualsWithDelta(10.95, $desglose->isss, 0.01);
        $this->assertEqualsWithDelta(26.46, $desglose->afp, 0.01);
        $this->assertEqualsWithDelta(327.59, $desglose->rentaBaseImponible, 0.01);
        $this->assertSame('I', $desglose->tramoRenta);
        $this->assertEqualsWithDelta(0.00, $desglose->renta, 0.01);
        $this->assertEqualsWithDelta(327.59, $desglose->salarioLiquido, 0.01);
    }

    public function test_tramo_dos_aplica_diez_por_ciento_mas_cuota_fija(): void
    {
        // Base imponible $583.37 => 17.67 + 10% sobre el exceso de $472.00
        $desglose = $this->calculadora->calcular(650.00);

        $this->assertSame('II', $desglose->tramoRenta);
        $this->assertEqualsWithDelta(583.37, $desglose->rentaBaseImponible, 0.01);
        $this->assertEqualsWithDelta(28.81, $desglose->renta, 0.01);
    }

    public function test_tramo_tres_aplica_veinte_por_ciento(): void
    {
        // Base imponible $1,083.00 => 60.00 + 20% sobre el exceso de $895.24
        $desglose = $this->calculadora->calcular(1200.00);

        $this->assertSame('III', $desglose->tramoRenta);
        $this->assertEqualsWithDelta(1083.00, $desglose->rentaBaseImponible, 0.01);
        $this->assertEqualsWithDelta(97.55, $desglose->renta, 0.01);
        $this->assertEqualsWithDelta(985.45, $desglose->salarioLiquido, 0.01);
    }

    public function test_tramo_cuatro_aplica_treinta_por_ciento(): void
    {
        // Base imponible $2,288.75 => 288.57 + 30% sobre el exceso de $2,038.10
        $desglose = $this->calculadora->calcular(2500.00);

        $this->assertSame('IV', $desglose->tramoRenta);
        $this->assertEqualsWithDelta(2288.75, $desglose->rentaBaseImponible, 0.01);
        $this->assertEqualsWithDelta(363.77, $desglose->renta, 0.01);
    }

    public function test_isss_respeta_el_techo_de_mil_dolares(): void
    {
        // Con $5,000 el 3% serían $150, pero la ley topa la cotización en $30.
        $this->assertEqualsWithDelta(30.00, $this->calculadora->calcularIsss(5000.00), 0.01);
        $this->assertEqualsWithDelta(75.00, $this->calculadora->calcularIsssPatronal(5000.00), 0.01);
    }

    public function test_afp_respeta_su_propio_techo_cotizable(): void
    {
        // Techo AFP: $6,377.14 * 7.25%
        $this->assertEqualsWithDelta(462.34, $this->calculadora->calcularAfp(9000.00), 0.01);
    }

    public function test_calcula_el_costo_patronal_total(): void
    {
        $desglose = $this->calculadora->calcular(650.00);

        $this->assertEqualsWithDelta(48.75, $desglose->aportePatronalIsss, 0.01);
        $this->assertEqualsWithDelta(56.88, $desglose->aportePatronalAfp, 0.01);
        $this->assertEqualsWithDelta(755.63, $desglose->costoPatronal, 0.01);
    }

    public function test_las_bonificaciones_aumentan_la_base_gravable(): void
    {
        $sinBono = $this->calculadora->calcular(650.00);
        $conBono = $this->calculadora->calcular(650.00, bonificaciones: 200.00);

        $this->assertGreaterThan($sinBono->renta, $conBono->renta);
        $this->assertEqualsWithDelta(850.00, $conBono->totalDevengado, 0.01);
    }

    public function test_las_deducciones_nunca_dejan_el_liquido_negativo(): void
    {
        $desglose = $this->calculadora->calcular(365.00, otrasDeducciones: 50.00);

        $this->assertEqualsWithDelta(277.59, $desglose->salarioLiquido, 0.01);
        $this->assertGreaterThan(0, $desglose->salarioLiquido);
    }

    public function test_rechaza_montos_negativos(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->calculadora->calcular(-100.00);
    }

    public function test_la_suma_de_deducciones_cuadra_con_el_liquido(): void
    {
        foreach ([365, 650, 900, 1200, 2500, 3800, 7000] as $salario) {
            $d = $this->calculadora->calcular((float) $salario);

            $this->assertEqualsWithDelta(
                $d->totalDevengado - $d->totalDeducciones,
                $d->salarioLiquido,
                0.01,
                "El líquido no cuadra para el salario {$salario}"
            );
        }
    }
}
