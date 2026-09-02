<?php

namespace App\Http\Controllers;

use App\Models\Aguinaldo;
use App\Services\CalculadoraAguinaldo;
use App\Services\GeneradorAguinaldo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AguinaldoController extends Controller
{
    public function index(Request $request, CalculadoraAguinaldo $calculadora)
    {
        $anio = $request->integer('anio') ?: now()->year;

        $aguinaldos = Aguinaldo::with('empleado.departamento')
            ->delAnio($anio)
            ->join('empleados', 'empleados.id', '=', 'aguinaldos.empleado_id')
            ->orderBy('empleados.apellidos')
            ->select('aguinaldos.*')
            ->paginate(20)
            ->withQueryString();

        $totales = Aguinaldo::delAnio($anio)
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('COALESCE(SUM(monto_bruto), 0) as bruto')
            ->selectRaw('COALESCE(SUM(descuento_renta), 0) as renta')
            ->selectRaw('COALESCE(SUM(monto_neto), 0) as neto')
            ->first();

        return view('aguinaldos.index', [
            'aguinaldos' => $aguinaldos,
            'totales' => $totales,
            'anio' => $anio,
            'anios' => range(now()->year, now()->year - 5),
            'montoExento' => $calculadora->montoExento(),
            'fechaCorte' => $calculadora->fechaDeCorte($anio),
        ]);
    }

    public function generar(Request $request, GeneradorAguinaldo $generador)
    {
        $datos = $request->validate([
            'anio' => ['required', 'integer', 'between:2020,'.(now()->year + 1)],
        ]);

        $resultado = $generador->generar($datos['anio'], $request->user());

        $mensaje = $resultado['generados'] > 0
            ? "Se calcularon {$resultado['generados']} aguinaldos de {$datos['anio']}."
            : "Todos los empleados activos ya tienen aguinaldo calculado para {$datos['anio']}.";

        return redirect()
            ->route('aguinaldos.index', ['anio' => $datos['anio']])
            ->with('success', $mensaje);
    }

    public function pdf(Aguinaldo $aguinaldo)
    {
        $this->authorize('view', $aguinaldo);

        $aguinaldo->load('empleado.departamento');

        $pdf = Pdf::loadView('aguinaldos.comprobante_pdf', [
            'aguinaldo' => $aguinaldo,
            'empresa' => config('nomina.empresa'),
        ]);

        return $pdf->download(sprintf(
            'aguinaldo_%s_%d.pdf',
            str($aguinaldo->empleado->nombre_completo)->slug('_'),
            $aguinaldo->anio
        ));
    }
}
