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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('dui', 10)->unique(); // Formato 00000000-0
            $table->date('fecha_nacimiento');
            $table->date('fecha_contratacion');
            $table->decimal('salario_base', 8, 2); // Permite hasta 999,999.99
            
            // Relación con la tabla departamentos
            $table->foreignId('departamento_id')->constrained('departamentos')->onDelete('restrict');
            
            $table->boolean('activo')->default(true); // Para saber si sigue trabajando en la empresa
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
