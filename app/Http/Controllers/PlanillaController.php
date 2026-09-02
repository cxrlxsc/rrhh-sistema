<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerarPlanillaRequest;
use App\Models\Planilla;
use App\Services\GeneradorPlanilla;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PlanillaController extends Controller
{
    /**
     * Listado administrativo de planillas, filtrable por período.
     */
    public function index(Request $request)
    {
        $this->authorize('verTodas', Planilla::class);

        $anio = $request->integer('anio') ?: now()->year;
        $mes = $request->integer('mes') ?: null;

        $consulta = Planilla::with('empleado.departamento')
            ->where('anio', $anio)
            ->when($mes, fn ($q) => $q->where('mes_numero', $mes))
            ->recientesPrimero();

        // Totales del período consultado (sin paginar), para la fila de resumen.
        $totales = Planilla::where('anio', $anio)
            ->when($mes, fn ($q) => $q->where('mes_numero', $mes))
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(total_devengado), 0) as devengado')
            ->selectRaw('COALESCE(SUM(descuento_isss), 0) as isss')
            ->selectRaw('COALESCE(SUM(descuento_afp), 0) as afp')
            ->selectRaw('COALESCE(SUM(descuento_renta), 0) as renta')
            ->selectRaw('COALESCE(SUM(salario_liquido), 0) as liquido')
            ->selectRaw('COALESCE(SUM(costo_patronal), 0) as costo_patronal')
            ->first();

        return view('planillas.index', [
            'planillas' => $consulta->paginate(20)->withQueryString(),
            'totales' => $totales,
            'anio' => $anio,
            'mes' => $mes,
            'meses' => $this->meses(),
            'anios' => range(now()->year, now()->year - 5),
        ]);
    }

    /**
     * Procesa la nómina de un período completo.
     * El trabajo real lo hace GeneradorPlanilla, compartido con el comando
     * `php artisan planilla:generar`.
     */
    public function generar(GenerarPlanillaRequest $request, GeneradorPlanilla $generador)
    {
        $mes = $request->integer('mes');
        $anio = $request->integer('anio');

        $resultado = $generador->generar($mes, $anio, $request->user());
        $nombreMes = $resultado['mes'];

        $mensaje = $resultado['generados'] > 0
            ? "Se procesaron {$resultado['generados']} planillas de {$nombreMes} {$anio}."
            : "Todos los empleados activos ya tienen planilla de {$nombreMes} {$anio}.";

        if ($resultado['generados'] > 0 && $resultado['omitidos'] > 0) {
            $mensaje .= " Se omitieron {$resultado['omitidos']} que ya estaban generadas.";
        }

        return redirect()
            ->route('planillas.index', ['anio' => $anio, 'mes' => $mes])
            ->with('success', $mensaje);
    }

    /**
     * Autoservicio: el empleado ve únicamente su propio historial de recibos.
     */
    public function mios(Request $request)
    {
        $empleado = $request->user()->empleado;

        abort_if(! $empleado, 403, 'Tu usuario no está enlazado a una ficha de empleado.');

        $planillas = $empleado->planillas()->recientesPrimero()->paginate(12);

        return view('planillas.mios', compact('empleado', 'planillas'));
    }

    /**
     * Descarga del recibo en PDF. La PlanillaPolicy decide si el usuario
     * puede verlo: RRHH ve todos, el empleado solo los suyos.
     */
    public function descargarPdf(Planilla $planilla)
    {
        $this->authorize('view', $planilla);

        $planilla->load('empleado.departamento');

        $pdf = Pdf::loadView('planillas.recibo_pdf', [
            'planilla' => $planilla,
            'empresa' => config('nomina.empresa'),
        ]);

        $nombreArchivo = sprintf(
            'recibo_%s_%s_%d.pdf',
            str($planilla->empleado->nombre_completo)->slug('_'),
            str($planilla->mes)->slug(),
            $planilla->anio
        );

        return $pdf->download($nombreArchivo);
    }

    /**
     * Meses en español para los filtros, sin depender del locale del servidor.
     */
    private function meses(): array
    {
        return collect(range(1, 12))
            ->mapWithKeys(fn ($m) => [$m => ucfirst(Carbon::create(null, $m, 1)->translatedFormat('F'))])
            ->all();
    }
}
