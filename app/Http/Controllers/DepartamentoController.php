<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartamentoController extends Controller
{
    public function index()
    {
        // withCount evita el N+1 al mostrar cuántos empleados tiene cada área.
        $departamentos = Departamento::withCount(['empleados' => fn ($q) => $q->where('activo', true)])
            ->orderBy('nombre')
            ->get();

        return view('departamentos.index', compact('departamentos'));
    }

    public function create()
    {
        return view('departamentos.create');
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        Departamento::create($datos);

        return redirect()->route('departamentos.index')->with('success', 'Departamento creado exitosamente.');
    }

    public function edit(Departamento $departamento)
    {
        return view('departamentos.edit', compact('departamento'));
    }

    public function update(Request $request, Departamento $departamento)
    {
        $departamento->update($this->validar($request, $departamento));

        return redirect()->route('departamentos.index')->with('success', 'Departamento actualizado.');
    }

    /**
     * Solo se puede eliminar un departamento vacío: la llave foránea usa
     * onDelete('restrict') y no queremos empleados huérfanos.
     */
    public function destroy(Departamento $departamento)
    {
        if ($departamento->empleados()->exists()) {
            return redirect()
                ->route('departamentos.index')
                ->with('error', 'No se puede eliminar: el departamento aún tiene empleados asignados.');
        }

        $departamento->delete();

        return redirect()->route('departamentos.index')->with('success', 'Departamento eliminado.');
    }

    private function validar(Request $request, ?Departamento $departamento = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('departamentos', 'nombre')->ignore($departamento)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
