<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Liquidacion;
use App\Services\CalculadoraLiquidacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LiquidacionController extends Controller
{
    public function __construct(private readonly CalculadoraLiquidacion $calculadora)
    {
    }

    public function index()
    {
        return view('liquidaciones.index', [
            'liquidaciones' => Liquidacion::with('empleado')->orderByDesc('fecha_salida')->paginate(20),
        ]);
    }

    /**
     * Formulario del finiquito. Si vienen datos, muestra el cálculo previo
     * SIN guardar nada: RRHH revisa las cifras antes de confirmar.
     */
    public function create(Request $request)
    {
        $desglose = null;
        $empleado = null;

        if ($request->filled(['empleado_id', 'fecha_salida', 'motivo'])) {
            $datos = $this->validar($request);
            $empleado = Empleado::findOrFail($datos['empleado_id']);

            $desglose = $this->calculadora->calcular(
                $empleado,
                Carbon::parse($datos['fecha_salida']),
                $datos['motivo'],
                (float) ($datos['salarios_pendientes'] ?? 0),
                (float) ($datos['otras_deducciones'] ?? 0),
            );
        }

        return view('liquidaciones.create', [
            'empleados' => Empleado::activos()->orderBy('apellidos')->get(),
            'motivos' => config('prestaciones.liquidacion.motivos'),
            'desglose' => $desglose,
            'empleado' => $empleado,
            'datos' => $request->only(['empleado_id', 'fecha_salida', 'motivo', 'salarios_pendientes', 'otras_deducciones', 'observaciones']),
        ]);
    }

    /**
     * Confirma la liquidación: guarda el finiquito y da de baja al empleado.
     * Ambas cosas en una transacción, porque no tiene sentido una sin la otra.
     */
    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $empleado = Empleado::findOrFail($datos['empleado_id']);
        $fechaSalida = Carbon::parse($datos['fecha_salida']);

        $desglose = $this->calculadora->calcular(
            $empleado,
            $fechaSalida,
            $datos['motivo'],
            (float) ($datos['salarios_pendientes'] ?? 0),
            (float) ($datos['otras_deducciones'] ?? 0),
        );

        $liquidacion = DB::transaction(function () use ($empleado, $desglose, $datos, $fechaSalida, $request) {
            $liquidacion = Liquidacion::create($desglose->toArray() + [
                'empleado_id' => $empleado->id,
                'fecha_salida' => $fechaSalida->toDateString(),
                'observaciones' => $datos['observaciones'] ?? null,
                'generada_por' => $request->user()->id,
            ]);

            $empleado->update([
                'activo' => false,
                'fecha_salida' => $fechaSalida->toDateString(),
                'motivo_salida' => $datos['motivo'],
            ]);

            return $liquidacion;
        });

        return redirect()->route('liquidaciones.index')->with(
            'success',
            "Liquidación de {$empleado->nombre_completo} registrada por \${$liquidacion->total_a_pagar}. El empleado quedó dado de baja."
        );
    }

    public function pdf(Liquidacion $liquidacion)
    {
        $this->authorize('view', $liquidacion);

        $liquidacion->load('empleado.departamento');

        $pdf = Pdf::loadView('liquidaciones.finiquito_pdf', [
            'liquidacion' => $liquidacion,
            'empresa' => config('nomina.empresa'),
        ]);

        return $pdf->download(sprintf(
            'finiquito_%s.pdf',
            str($liquidacion->empleado->nombre_completo)->slug('_')
        ));
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'empleado_id' => ['required', 'exists:empleados,id'],
            'fecha_salida' => ['required', 'date'],
            'motivo' => ['required', Rule::in(array_keys(config('prestaciones.liquidacion.motivos')))],
            'salarios_pendientes' => ['nullable', 'numeric', 'min:0'],
            'otras_deducciones' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
