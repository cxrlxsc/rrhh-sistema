<?php

namespace App\Http\Controllers;

use App\Services\CalculadoraVacaciones;
use Illuminate\Http\Request;

/**
 * Vista del empleado sobre sus propias prestaciones: saldo de vacaciones,
 * aguinaldos recibidos y, si aplica, su finiquito.
 */
class AutoservicioController extends Controller
{
    public function prestaciones(Request $request, CalculadoraVacaciones $vacaciones)
    {
        $empleado = $request->user()->empleado;

        abort_if(! $empleado, 403, 'Tu usuario no está enlazado a una ficha de empleado.');

        return view('autoservicio.prestaciones', [
            'empleado' => $empleado,
            'saldo' => [
                'ganados' => $vacaciones->diasGanados($empleado),
                'tomados' => $vacaciones->diasTomados($empleado),
                'disponibles' => $vacaciones->diasDisponibles($empleado),
            ],
            'vacaciones' => $empleado->vacaciones()->orderByDesc('fecha_inicio')->limit(10)->get(),
            'aguinaldos' => $empleado->aguinaldos()->orderByDesc('anio')->get(),
            'liquidacion' => $empleado->liquidacion,
        ]);
    }
}
