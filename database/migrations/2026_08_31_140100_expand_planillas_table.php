<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enriquece la planilla con el desglose completo:
     *  - período identificable por número de mes (el nombre en texto dependía
     *    del locale y rompía la comparación "ya se generó este mes"),
     *  - base imponible y tramo de renta aplicado (trazabilidad fiscal),
     *  - aportes patronales para conocer el costo real de cada empleado,
     *  - resumen de asistencia del período,
     *  - auditoría de quién generó la planilla.
     */
    public function up(): void
    {
        Schema::table('planillas', function (Blueprint $table) {
            $table->unsignedTinyInteger('mes_numero')->default(1)->after('mes');

            $table->decimal('bonificaciones', 10, 2)->default(0)->after('salario_base');
            $table->decimal('total_devengado', 10, 2)->default(0)->after('bonificaciones');

            $table->decimal('renta_base_imponible', 10, 2)->default(0)->after('descuento_afp');
            $table->string('tramo_renta', 5)->default('I')->after('descuento_renta');
            $table->decimal('otras_deducciones', 10, 2)->default(0)->after('tramo_renta');
            $table->decimal('total_deducciones', 10, 2)->default(0)->after('otras_deducciones');

            $table->decimal('aporte_patronal_isss', 10, 2)->default(0)->after('salario_liquido');
            $table->decimal('aporte_patronal_afp', 10, 2)->default(0)->after('aporte_patronal_isss');
            $table->decimal('costo_patronal', 10, 2)->default(0)->after('aporte_patronal_afp');

            $table->unsignedSmallInteger('dias_laborados')->default(0)->after('costo_patronal');
            $table->unsignedSmallInteger('llegadas_tardias')->default(0)->after('dias_laborados');

            $table->foreignId('generada_por')->nullable()->after('llegadas_tardias')
                  ->constrained('users')->nullOnDelete();
        });

        $this->rellenarDatosExistentes();

        // El candado único se agrega DESPUÉS del relleno: si se creara antes,
        // las planillas viejas (todas con mes_numero = 1) chocarían entre sí.
        Schema::table('planillas', function (Blueprint $table) {
            $table->unique(['empleado_id', 'anio', 'mes_numero'], 'planillas_periodo_unico');
        });
    }

    /**
     * Migra las planillas ya generadas al nuevo esquema:
     *  - deduce el número de mes a partir del nombre guardado (el locale del
     *    proyecto estuvo en inglés, así que se contemplan ambos idiomas),
     *  - completa devengado y total de deducciones con los montos existentes.
     */
    private function rellenarDatosExistentes(): void
    {
        $meses = [
            'enero' => 1, 'january' => 1,
            'febrero' => 2, 'february' => 2,
            'marzo' => 3, 'march' => 3,
            'abril' => 4, 'april' => 4,
            'mayo' => 5, 'may' => 5,
            'junio' => 6, 'june' => 6,
            'julio' => 7, 'july' => 7,
            'agosto' => 8, 'august' => 8,
            'septiembre' => 9, 'setiembre' => 9, 'september' => 9,
            'octubre' => 10, 'october' => 10,
            'noviembre' => 11, 'november' => 11,
            'diciembre' => 12, 'december' => 12,
        ];

        foreach ($meses as $nombre => $numero) {
            DB::table('planillas')->whereRaw('LOWER(mes) = ?', [$nombre])->update(['mes_numero' => $numero]);
        }

        DB::table('planillas')->update([
            'total_devengado' => DB::raw('salario_base'),
            'total_deducciones' => DB::raw('descuento_isss + descuento_afp + descuento_renta'),
            'renta_base_imponible' => DB::raw('salario_base - descuento_isss - descuento_afp'),
        ]);
    }

    public function down(): void
    {
        Schema::table('planillas', function (Blueprint $table) {
            $table->dropUnique('planillas_periodo_unico');
            $table->dropConstrainedForeignId('generada_por');
            $table->dropColumn([
                'mes_numero',
                'bonificaciones',
                'total_devengado',
                'renta_base_imponible',
                'tramo_renta',
                'otras_deducciones',
                'total_deducciones',
                'aporte_patronal_isss',
                'aporte_patronal_afp',
                'costo_patronal',
                'dias_laborados',
                'llegadas_tardias',
            ]);
        });
    }
};
