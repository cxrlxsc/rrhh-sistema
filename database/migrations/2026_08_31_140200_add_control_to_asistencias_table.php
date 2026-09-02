<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Control y trazabilidad del kiosco:
     *  - marca de tiempo exacta del último marcaje (para el "cooldown"),
     *  - IP del dispositivo que marcó,
     *  - minutos de tardanza y minutos trabajados ya calculados,
     *  - índice único (empleado, fecha) para que dos peticiones simultáneas
     *    no puedan crear dos registros del mismo día.
     */
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->timestamp('ultimo_marcaje_at')->nullable()->after('hora_salida');
            $table->string('ip_marcaje', 45)->nullable()->after('ultimo_marcaje_at');
            $table->unsignedSmallInteger('minutos_tarde')->default(0)->after('estado_entrada');
            $table->unsignedSmallInteger('minutos_trabajados')->default(0)->after('minutos_tarde');

            $table->unique(['empleado_id', 'fecha'], 'asistencias_empleado_fecha_unico');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropUnique('asistencias_empleado_fecha_unico');
            $table->dropColumn(['ultimo_marcaje_at', 'ip_marcaje', 'minutos_tarde', 'minutos_trabajados']);
        });
    }
};
