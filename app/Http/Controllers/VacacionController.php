<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Vacacion;
use App\Services\CalculadoraVacaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VacacionController extends Controller
{
    public function __construct(private readonly CalculadoraVacaciones $calculadora)
    {
    }

    public function index(Request $request)
    {
        $vacaciones = Vacacion::with('empleado')
            ->when($request->filled('empleado_id'), fn ($q) => $q->where('empleado_id', $request->integer('empleado_id')))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->input('estado')))
            ->orderByDesc('fecha_inicio')
            ->paginate(20)
            ->withQueryString();

        // Saldo de días por empleado: lo ganado menos lo ya gozado.
        $saldos = Empleado::activos()->orderBy('apellidos')->get()->map(fn ($empleado) => [
            'empleado' => $empleado,
            'ganados' => $this->calculadora->diasGanados($empleado),
            'tomados' => $this->calculadora->diasTomados($empleado),
            'disponibles' => $this->calculadora->diasDisponibles($empleado),
        ]);

        return view('vacaciones.index', [
            'vacaciones' => $vacaciones,
            'saldos' => $saldos,
            'empleados' => Empleado::activos()->orderBy('apellidos')->get(),
            'estados' => $this->calculadora->estados(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'empleado_id' => ['required', 'exists:empleados,id'],
            'fecha_inicio' => ['required', 'date'],
            'dias' => ['required', 'integer', 'min:1', 'max:30'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $empleado = Empleado::findOrFail($datos['empleado_id']);
        $inicio = Carbon::parse($datos['fecha_inicio']);
        $fin = $inicio->copy()->addDays($datos['dias'] - 1);

        $this->validarDisponibilidad($empleado, $inicio, $fin, $datos['dias']);

        $desglose = $this->calculadora->calcular($empleado, $datos['dias']);

        Vacacion::create($desglose->toArray() + [
            'empleado_id' => $empleado->id,
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $fin->toDateString(),
            'estado' => Vacacion::ESTADO_PROGRAMADA,
            'observaciones' => $datos['observaciones'] ?? null,
            'registrada_por' => $request->user()->id,
        ]);

        return redirect()->route('vacaciones.index')->with(
            'success',
            "Vacaciones registradas para {$empleado->nombre_completo}: {$datos['dias']} días por \${$desglose->totalPagado} (incluye el 30% de recargo legal)."
        );
    }

    /**
     * Cambia el estado del período (gozada / cancelada).
     */
    public function update(Request $request, Vacacion $vacacion)
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::in(array_keys($this->calculadora->estados()))],
        ]);

        $vacacion->update($datos);

        return redirect()->route('vacaciones.index')
            ->with('success', 'Estado del período actualizado.');
    }

    /**
     * Dos reglas que protegen el saldo: no exceder los días ganados y no
     * traslapar dos períodos del mismo empleado.
     */
    private function validarDisponibilidad(Empleado $empleado, Carbon $inicio, Carbon $fin, int $dias): void
    {
        $disponibles = $this->calculadora->diasDisponibles($empleado);

        if ($disponibles < $dias) {
            throw ValidationException::withMessages([
                'dias' => "{$empleado->nombre_completo} tiene {$disponibles} día(s) disponibles y se solicitaron {$dias}.",
            ]);
        }

        if ($this->calculadora->hayTraslape($empleado, $inicio, $fin)) {
            throw ValidationException::withMessages([
                'fecha_inicio' => 'El período se traslapa con otras vacaciones ya registradas para este empleado.',
            ]);
        }
    }
}
