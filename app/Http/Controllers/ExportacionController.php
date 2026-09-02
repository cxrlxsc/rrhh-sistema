<?php

namespace App\Http\Controllers;

use App\Models\Planilla;
use App\Services\ExportadorPlanilla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Archivos para las instituciones: ISSS, AFP y Ministerio de Hacienda.
 */
class ExportacionController extends Controller
{
    public function __construct(private readonly ExportadorPlanilla $exportador)
    {
    }

    public function index()
    {
        // Períodos que realmente tienen planilla generada: no tiene sentido
        // ofrecer la descarga de un mes vacío.
        $periodos = Planilla::selectRaw('anio, mes_numero, mes, COUNT(*) as registros')
            ->groupBy('anio', 'mes_numero', 'mes')
            ->orderByDesc('anio')
            ->orderByDesc('mes_numero')
            ->get();

        return view('exportaciones.index', [
            'periodos' => $periodos,
            'anios' => Planilla::distinct()->orderByDesc('anio')->pluck('anio'),
        ]);
    }

    public function descargar(Request $request)
    {
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['isss', 'afp', 'renta'])],
            'anio' => ['required', 'integer', 'between:2020,'.(now()->year + 1)],
            'mes' => ['required_unless:tipo,renta', 'nullable', 'integer', 'between:1,12'],
        ]);

        $anio = (int) $datos['anio'];
        $mes = (int) ($datos['mes'] ?? 0);

        if ($datos['tipo'] !== 'renta' && ! Planilla::delPeriodo($anio, $mes)->exists()) {
            $nombreMes = ucfirst(Carbon::create($anio, $mes, 1)->translatedFormat('F'));

            return back()->with('error', "No hay planilla generada para {$nombreMes} {$anio}.");
        }

        return match ($datos['tipo']) {
            'isss' => $this->exportador->isss($anio, $mes),
            'afp' => $this->exportador->afp($anio, $mes),
            'renta' => $this->exportador->retencionesRenta($anio),
        };
    }
}
