<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prestaciones laborales: aguinaldo, vacaciones y liquidación.
     *
     * Cada una vive en su propia tabla porque se paga en un momento distinto
     * al salario ordinario y tiene su propio comprobante legal.
     */
    public function up(): void
    {
        // --- Aguinaldo: uno por empleado y año ---
        Schema::create('aguinaldos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->year('anio');

            $table->unsignedSmallInteger('anios_servicio');
            // Decimal y no entero: quien lleva menos de un año recibe una
            // fracción de días (p. ej. 7.40) y esa precisión no se puede perder.
            $table->decimal('dias_aplicados', 6, 2);
            $table->decimal('salario_diario', 10, 4);
            $table->boolean('proporcional')->default(false);

            $table->decimal('monto_bruto', 10, 2);
            $table->decimal('monto_exento', 10, 2)->default(0);
            $table->decimal('monto_gravado', 10, 2)->default(0);
            $table->decimal('descuento_renta', 10, 2)->default(0);
            $table->decimal('monto_neto', 10, 2);

            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['empleado_id', 'anio'], 'aguinaldos_empleado_anio_unico');
        });

        // --- Vacaciones: un registro por período gozado ---
        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();

            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedSmallInteger('dias');

            $table->decimal('salario_diario', 10, 4);
            $table->decimal('monto_base', 10, 2);
            $table->decimal('recargo', 10, 2);        // 30% legal
            $table->decimal('total_pagado', 10, 2);

            $table->string('estado', 20)->default('programada'); // programada | gozada | cancelada
            $table->text('observaciones')->nullable();

            $table->foreignId('registrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['empleado_id', 'fecha_inicio']);
        });

        // --- Liquidación / finiquito: una por empleado ---
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();

            $table->date('fecha_salida');
            $table->string('motivo', 50);
            $table->unsignedSmallInteger('anios_servicio');
            $table->unsignedSmallInteger('dias_servicio');
            $table->decimal('salario_diario', 10, 4);
            $table->decimal('salario_diario_topado', 10, 4);

            $table->decimal('indemnizacion', 10, 2)->default(0);
            $table->decimal('prestacion_renuncia', 10, 2)->default(0);
            $table->decimal('vacacion_proporcional', 10, 2)->default(0);
            $table->decimal('aguinaldo_proporcional', 10, 2)->default(0);
            $table->decimal('salarios_pendientes', 10, 2)->default(0);
            $table->decimal('otras_deducciones', 10, 2)->default(0);
            $table->decimal('total_a_pagar', 10, 2);

            $table->text('observaciones')->nullable();
            $table->foreignId('generada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('empleado_id', 'liquidaciones_empleado_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
        Schema::dropIfExists('vacaciones');
        Schema::dropIfExists('aguinaldos');
    }
};
