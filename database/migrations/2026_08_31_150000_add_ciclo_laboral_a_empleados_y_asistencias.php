<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cierra el ciclo laboral:
     *  - el empleado ahora puede tener fecha y motivo de salida (liquidación),
     *  - cada marcaje calcula el tiempo extraordinario del día, que después
     *    alimenta la planilla como horas extra.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->date('fecha_salida')->nullable()->after('activo');
            $table->string('motivo_salida')->nullable()->after('fecha_salida');

            // Identificadores previsionales y fiscales: sin ellos no se puede
            // armar la planilla que se sube a los portales del ISSS, la AFP
            // y el Ministerio de Hacienda.
            $table->string('nit', 20)->nullable()->after('dui');
            $table->string('numero_isss', 20)->nullable()->after('nit');
            $table->string('numero_afp', 20)->nullable()->after('numero_isss');
            $table->string('afp_administradora', 50)->nullable()->after('numero_afp');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->unsignedSmallInteger('minutos_extra')->default(0)->after('minutos_trabajados');
        });

        Schema::table('planillas', function (Blueprint $table) {
            // Trazabilidad: cuánto del monto de bonificaciones viene de horas extra.
            $table->unsignedSmallInteger('minutos_extra')->default(0)->after('bonificaciones');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn([
                'fecha_salida', 'motivo_salida',
                'nit', 'numero_isss', 'numero_afp', 'afp_administradora',
            ]);
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('minutos_extra');
        });

        Schema::table('planillas', function (Blueprint $table) {
            $table->dropColumn('minutos_extra');
        });
    }
};
