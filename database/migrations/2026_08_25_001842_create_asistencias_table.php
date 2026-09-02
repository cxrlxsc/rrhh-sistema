<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            
            // Relación con el empleado
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            
            // Registro de tiempo
            $table->date('fecha');
            $table->time('hora_entrada')->nullable();
            $table->time('hora_salida')->nullable();
            
            // Para el control de llegadas tardías
            $table->string('estado_entrada')->default('Puntual'); // 'Puntual' o 'Llegada Tardía'
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
