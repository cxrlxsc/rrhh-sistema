<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Planilla;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Un empleado sin permisos administrativos no ve el tablero de RRHH:
        // se le manda directo a su autoservicio.
        if (! auth()->user()->esPersonalRrhh()) {
            return redirect()->route('planillas.mios');
        }

        $anio = now()->year;
        $mes = now()->month;
        $nombreMes = ucfirst(now()->translatedFormat('F'));

        // 1. KPIs principales
        $totalEmpleados = Empleado::activos()->count();
        $totalDepartamentos = Departamento::count();

        $nomina = Planilla::delPeriodo($anio, $mes)
            ->selectRaw('COALESCE(SUM(salario_liquido), 0) as liquido')
            ->selectRaw('COALESCE(SUM(costo_patronal), 0) as costo_patronal')
            ->selectRaw('COALESCE(SUM(descuento_renta), 0) as renta')
            ->first();

        // 2. Pulso de asistencia del día
        $presentesHoy = Asistencia::hoy()->count();
        $tardanzasHoy = Asistencia::hoy()->tardias()->count();

        $asistenciaHoy = [
            'presentes' => $presentesHoy,
            'tardanzas' => $tardanzasHoy,
            'ausentes' => max(0, $totalEmpleados - $presentesHoy),
            'porcentaje' => $totalEmpleados > 0 ? round($presentesHoy / $totalEmpleados * 100) : 0,
        ];

        // 3. Distribución por departamento (gráfico de dona)
        $departamentos = Departamento::withCount(['empleados' => fn ($q) => $q->where('activo', true)])->get();

        // 4. Evolución de la nómina en los últimos 6 meses (gráfico de barras)
        $historico = collect(range(5, 0))->map(function ($atras) {
            $fecha = Carbon::now()->subMonths($atras);
            $total = Planilla::delPeriodo($fecha->year, $fecha->month)->sum('salario_liquido');

            return [
                'etiqueta' => ucfirst($fecha->translatedFormat('M Y')),
                'total' => round((float) $total, 2),
            ];
        });

        return view('dashboard', [
            'totalEmpleados' => $totalEmpleados,
            'totalDepartamentos' => $totalDepartamentos,
            'gastoNomina' => $nomina->liquido,
            'costoPatronal' => $nomina->costo_patronal,
            'rentaRetenida' => $nomina->renta,
            'mesActual' => $nombreMes,
            'asistenciaHoy' => $asistenciaHoy,
            'nombresDeptos' => $departamentos->pluck('nombre'),
            'conteosDeptos' => $departamentos->pluck('empleados_count'),
            'historicoEtiquetas' => $historico->pluck('etiqueta'),
            'historicoTotales' => $historico->pluck('total'),
        ]);
    }
}
