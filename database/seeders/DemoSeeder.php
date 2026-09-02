<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empleado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de demostración: departamentos, empleados con salarios que caen en
 * los cuatro tramos de renta, y dos semanas de marcajes de asistencia.
 * Pensado para mostrar el sistema funcionando sin capturar nada a mano.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $departamentos = collect([
            ['nombre' => 'Gerencia General', 'descripcion' => 'Dirección estratégica de la empresa'],
            ['nombre' => 'Recursos Humanos', 'descripcion' => 'Gestión del talento y planillas'],
            ['nombre' => 'Tecnología', 'descripcion' => 'Desarrollo y soporte de sistemas'],
            ['nombre' => 'Ventas', 'descripcion' => 'Atención a clientes y facturación'],
            ['nombre' => 'Operaciones', 'descripcion' => 'Producción y logística'],
        ])->mapWithKeys(fn ($d) => [
            $d['nombre'] => Departamento::firstOrCreate(['nombre' => $d['nombre']], $d),
        ]);

        // Salarios elegidos a propósito para tocar los 4 tramos del ISR.
        $empleados = [
            ['nombres' => 'Ana Sofía',  'apellidos' => 'Martínez Rivas',  'dui' => '01234567-8', 'salario_base' => 365.00,  'depto' => 'Operaciones'],
            ['nombres' => 'Carlos José','apellidos' => 'Hernández Cruz',  'dui' => '02345678-9', 'salario_base' => 650.00,  'depto' => 'Ventas'],
            ['nombres' => 'María José', 'apellidos' => 'López Ramírez',   'dui' => '03456789-0', 'salario_base' => 1200.00, 'depto' => 'Recursos Humanos'],
            ['nombres' => 'Jorge Luis', 'apellidos' => 'Menjívar Aguilar','dui' => '04567890-1', 'salario_base' => 2500.00, 'depto' => 'Tecnología'],
            ['nombres' => 'Rocío',      'apellidos' => 'Portillo Serrano','dui' => '05678901-2', 'salario_base' => 3800.00, 'depto' => 'Gerencia General'],
            ['nombres' => 'Diego',      'apellidos' => 'Alvarenga Flores','dui' => '06789012-3', 'salario_base' => 900.00,  'depto' => 'Tecnología'],
        ];

        foreach ($empleados as $i => $datos) {
            $empleado = Empleado::firstOrCreate(
                ['dui' => $datos['dui']],
                [
                    'nombres' => $datos['nombres'],
                    'apellidos' => $datos['apellidos'],
                    'fecha_nacimiento' => Carbon::now()->subYears(25 + $i)->toDateString(),
                    'fecha_contratacion' => Carbon::now()->subYears(1)->subMonths($i)->toDateString(),
                    'salario_base' => $datos['salario_base'],
                    'departamento_id' => $departamentos[$datos['depto']]->id,
                    'activo' => true,
                ]
            );

            $this->sembrarAsistencia($empleado);
        }
    }

    /**
     * Genera los marcajes de los últimos 14 días hábiles, con algunas
     * tardanzas para que el reporte tenga algo que mostrar.
     */
    private function sembrarAsistencia(Empleado $empleado): void
    {
        for ($dia = 14; $dia >= 1; $dia--) {
            $fecha = Carbon::today()->subDays($dia);

            if ($fecha->isWeekend()) {
                continue;
            }

            $tarde = random_int(1, 5) === 1;
            $entrada = $fecha->copy()->setTime(7, random_int(30, 59));

            if ($tarde) {
                $entrada = $fecha->copy()->setTime(8, random_int(10, 45));
            }

            $salida = $fecha->copy()->setTime(17, random_int(0, 30));
            $minutosTarde = $tarde ? (int) $fecha->copy()->setTime(8, 5)->diffInMinutes($entrada, absolute: true) : 0;

            Asistencia::firstOrCreate(
                ['empleado_id' => $empleado->id, 'fecha' => $fecha->toDateString()],
                [
                    'hora_entrada' => $entrada->toTimeString(),
                    'hora_salida' => $salida->toTimeString(),
                    'ultimo_marcaje_at' => $salida,
                    'estado_entrada' => $tarde ? Asistencia::ESTADO_TARDE : Asistencia::ESTADO_PUNTUAL,
                    'minutos_tarde' => $minutosTarde,
                    'minutos_trabajados' => (int) $entrada->diffInMinutes($salida, absolute: true),
                ]
            );
        }
    }
}
