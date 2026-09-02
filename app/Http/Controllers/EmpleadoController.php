<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmpleadoRequest;
use App\Models\Departamento;
use App\Models\Empleado;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EmpleadoController extends Controller
{
    /**
     * Directorio con búsqueda y filtros.
     */
    public function index(Request $request)
    {
        $empleados = Empleado::with('departamento')
            ->buscar($request->input('q'))
            ->when($request->filled('departamento_id'), fn ($query) => $query->where('departamento_id', $request->integer('departamento_id')))
            ->when($request->input('estado') === 'activos', fn ($query) => $query->where('activo', true))
            ->when($request->input('estado') === 'inactivos', fn ($query) => $query->where('activo', false))
            ->orderBy('apellidos')
            ->paginate(15)
            ->withQueryString();

        return view('empleados.index', [
            'empleados' => $empleados,
            'departamentos' => Departamento::orderBy('nombre')->get(),
            'filtros' => $request->only(['q', 'departamento_id', 'estado']),
        ]);
    }

    public function create()
    {
        return view('empleados.create', [
            'departamentos' => Departamento::orderBy('nombre')->get(),
        ]);
    }

    public function store(EmpleadoRequest $request)
    {
        $empleado = Empleado::create($request->validated() + ['activo' => true]);

        return redirect()
            ->route('empleados.index')
            ->with('success', "Empleado {$empleado->nombre_completo} registrado exitosamente.");
    }

    /**
     * Ficha del empleado: datos, últimos recibos y asistencia reciente.
     */
    public function show(Empleado $empleado)
    {
        $empleado->load('departamento');

        return view('empleados.show', [
            'empleado' => $empleado,
            'planillas' => $empleado->planillas()->recientesPrimero()->limit(6)->get(),
            'asistencias' => $empleado->asistencias()->orderByDesc('fecha')->limit(10)->get(),
        ]);
    }

    public function edit(Empleado $empleado)
    {
        return view('empleados.edit', [
            'empleado' => $empleado,
            'departamentos' => Departamento::orderBy('nombre')->get(),
        ]);
    }

    public function update(EmpleadoRequest $request, Empleado $empleado)
    {
        $empleado->update($request->validated());

        return redirect()
            ->route('empleados.index')
            ->with('success', "Datos de {$empleado->nombre_completo} actualizados.");
    }

    /**
     * En RRHH no se borra a nadie: se da de baja.
     * Así se conserva el historial de planillas y asistencia (integridad legal).
     */
    public function destroy(Empleado $empleado)
    {
        $empleado->update(['activo' => ! $empleado->activo]);

        $estado = $empleado->activo ? 'reactivado' : 'dado de baja';

        return redirect()
            ->route('empleados.index')
            ->with('success', "{$empleado->nombre_completo} fue {$estado}.");
    }

    /**
     * Gafete imprimible con código QR (el QR contiene el DUI, que es lo que
     * lee el kiosco de marcaje).
     */
    public function credencial(Empleado $empleado)
    {
        $empleado->load('departamento');

        $qr = QrCode::size(150)->generate($empleado->dui);

        return view('empleados.credencial', compact('empleado', 'qr'));
    }
}
