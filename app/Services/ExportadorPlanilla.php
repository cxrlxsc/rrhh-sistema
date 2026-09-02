<?php

namespace App\Services;

use App\Models\Planilla;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generación de los archivos que se entregan a las instituciones.
 *
 * NOTA IMPORTANTE: cada portal (ISSS, AFP, Ministerio de Hacienda) publica su
 * propio layout y lo actualiza de vez en cuando. Aquí se produce un CSV con
 * los campos que esos archivos requieren, en UTF-8 con BOM para que Excel
 * respete los acentos. Antes de subirlo hay que confirmar el orden y el
 * formato exacto de columnas contra el instructivo vigente de cada entidad.
 */
class ExportadorPlanilla
{
    /**
     * Planilla previsional de salud (ISSS) de un período.
     */
    public function isss(int $anio, int $mes): StreamedResponse
    {
        $encabezados = [
            'No. Afiliacion ISSS', 'DUI', 'Apellidos', 'Nombres',
            'Salario Devengado', 'Dias Laborados',
            'Cotizacion Trabajador', 'Aporte Patronal', 'Total',
        ];

        $filas = $this->planillasDelPeriodo($anio, $mes)->map(fn (Planilla $p) => [
            $p->empleado->numero_isss,
            $p->empleado->dui,
            $p->empleado->apellidos,
            $p->empleado->nombres,
            number_format((float) $p->total_devengado, 2, '.', ''),
            $p->dias_laborados,
            number_format((float) $p->descuento_isss, 2, '.', ''),
            number_format((float) $p->aporte_patronal_isss, 2, '.', ''),
            number_format((float) $p->descuento_isss + (float) $p->aporte_patronal_isss, 2, '.', ''),
        ]);

        return $this->csv("planilla_isss_{$anio}_".str_pad((string) $mes, 2, '0', STR_PAD_LEFT), $encabezados, $filas);
    }

    /**
     * Planilla previsional de pensiones (AFP) de un período.
     */
    public function afp(int $anio, int $mes): StreamedResponse
    {
        $encabezados = [
            'NUP / No. AFP', 'Administradora', 'DUI', 'Apellidos', 'Nombres',
            'Salario Devengado', 'Aporte Trabajador', 'Aporte Patronal', 'Total',
        ];

        $filas = $this->planillasDelPeriodo($anio, $mes)->map(fn (Planilla $p) => [
            $p->empleado->numero_afp,
            $p->empleado->afp_administradora,
            $p->empleado->dui,
            $p->empleado->apellidos,
            $p->empleado->nombres,
            number_format((float) $p->total_devengado, 2, '.', ''),
            number_format((float) $p->descuento_afp, 2, '.', ''),
            number_format((float) $p->aporte_patronal_afp, 2, '.', ''),
            number_format((float) $p->descuento_afp + (float) $p->aporte_patronal_afp, 2, '.', ''),
        ]);

        return $this->csv("planilla_afp_{$anio}_".str_pad((string) $mes, 2, '0', STR_PAD_LEFT), $encabezados, $filas);
    }

    /**
     * Informe anual de retenciones de renta por empleado (base del F-910).
     */
    public function retencionesRenta(int $anio): StreamedResponse
    {
        $encabezados = [
            'NIT', 'DUI', 'Apellidos', 'Nombres',
            'Total Devengado Anual', 'ISSS Anual', 'AFP Anual',
            'Renta Retenida Anual', 'Meses Reportados',
        ];

        $filas = Planilla::with('empleado')
            ->where('anio', $anio)
            ->get()
            ->groupBy('empleado_id')
            ->map(function ($planillas) {
                $empleado = $planillas->first()->empleado;

                return [
                    $empleado->nit,
                    $empleado->dui,
                    $empleado->apellidos,
                    $empleado->nombres,
                    number_format((float) $planillas->sum('total_devengado'), 2, '.', ''),
                    number_format((float) $planillas->sum('descuento_isss'), 2, '.', ''),
                    number_format((float) $planillas->sum('descuento_afp'), 2, '.', ''),
                    number_format((float) $planillas->sum('descuento_renta'), 2, '.', ''),
                    $planillas->count(),
                ];
            })
            ->values();

        return $this->csv("retenciones_renta_{$anio}", $encabezados, $filas);
    }

    private function planillasDelPeriodo(int $anio, int $mes)
    {
        return Planilla::with('empleado')
            ->delPeriodo($anio, $mes)
            ->get()
            ->sortBy(fn (Planilla $p) => $p->empleado->apellidos)
            ->values();
    }

    /**
     * Descarga en streaming: no carga el archivo completo en memoria, así que
     * aguanta una planilla de miles de empleados sin agotar el límite de PHP.
     */
    private function csv(string $nombre, array $encabezados, $filas): StreamedResponse
    {
        $nombreArchivo = $nombre.'_'.Carbon::now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($encabezados, $filas) {
            $salida = fopen('php://output', 'w');

            // BOM UTF-8: sin esto Excel en Windows rompe los acentos.
            fwrite($salida, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($salida, $encabezados);

            foreach ($filas as $fila) {
                fputcsv($salida, $fila);
            }

            fclose($salida);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
