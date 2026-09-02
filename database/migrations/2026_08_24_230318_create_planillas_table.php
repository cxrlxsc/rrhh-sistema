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
        Schema::create('planillas', function (Blueprint $table) {
            $table->id();
            
            // Relación con el empleado
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            
            // Período de pago
            $table->string('mes'); // Ej: "Agosto"
            $table->integer('anio'); // Ej: 2026
            
            // Cálculos financieros
            $table->decimal('salario_base', 8, 2);
            $table->decimal('descuento_isss', 8, 2);   // 3% en El Salvador
            $table->decimal('descuento_afp', 8, 2);    // 7.25% en El Salvador
            $table->decimal('descuento_renta', 8, 2)->default(0); // Renta (si aplica)
            $table->decimal('salario_liquido', 8, 2);  // Lo que recibe al final
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planillas');
    }
};
