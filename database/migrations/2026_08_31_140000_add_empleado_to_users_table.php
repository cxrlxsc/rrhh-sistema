<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enlaza la cuenta de acceso (users) con la ficha de RRHH (empleados).
     * Gracias a esto un usuario con rol "empleado" puede ver únicamente sus
     * propios recibos y su propia asistencia.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empleado_id')
                  ->nullable()
                  ->after('email')
                  ->constrained('empleados')
                  ->nullOnDelete();

            $table->boolean('activo')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('empleado_id');
            $table->dropColumn('activo');
        });
    }
};
