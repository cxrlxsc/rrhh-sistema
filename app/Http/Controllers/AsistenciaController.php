<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarcarAsistenciaRequest;
use App\Models\Asistencia;
use App\Models\Empleado;
use App\Services\RegistroAsistencia;
use App\Support\ResultadoMarcaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AsistenciaController extends Controller
{
    public function __construct(private readonly RegistroAsistencia $registro)
    {
    }

    /**
     * Pantalla del kiosco (tablet en recepción). Es pública, por eso admite
     * un token opcional: si KIOSCO_TOKEN está definido en el .env, solo abre
     * desde /kiosco?token=xxxxx.
     */
    public function kiosco(Request $request)
    {
        $this->verificarTokenKiosco($request);

        return view('asistencias.kiosco');
    }

    /**
     * Registra entrada o salida a partir del DUI escaneado.
     */
    public function marcar(MarcarAsistenciaRequest $request)
    {
        $this->verificarTokenKiosco($request);

        $empleado = Empleado::activos()->where('dui', $request->validated('dui'))->first();

        if (! $empleado) {
            // Mensaje genérico: el kiosco es público y no debe confirmar
            // si un DUI existe o no en la base de datos.
            Log::warning('Marcaje rechazado en kiosco', ['ip' => $request->ip()]);

            return $this->respuestaKiosco(
                ResultadoMarcaje::rechazado('Gafete no reconocido. Contacta a Recursos Humanos.')
            );
        }

        return $this->respuestaKiosco($this->registro->marcar($empleado, $request->ip()));
    }

    /**
     * Reporte de asistencia para RRHH, con filtros por día y por empleado.
     */
    public function index(Request $request)
    {
        $desde = $request->date('desde') ?? now()->startOfMonth();
        $hasta = $request->date('hasta') ?? now()->endOfMonth();

        $asistencias = Asistencia::with('empleado')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($request->filled('empleado_id'), fn ($q) => $q->where('empleado_id', $request->integer('empleado_id')))
            ->orderByDesc('fecha')
            ->orderBy('hora_entrada')
            ->paginate(25)
            ->withQueryString();

        $resumen = [
            'registros' => $asistencias->total(),
            'tardanzas' => Asistencia::whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->tardias()->count(),
            'sin_salida' => Asistencia::whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->whereNull('hora_salida')->count(),
        ];

        return view('asistencias.index', [
            'asistencias' => $asistencias,
            'empleados' => Empleado::activos()->orderBy('nombres')->get(),
            'resumen' => $resumen,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    /**
     * Autoservicio: el empleado consulta su propia asistencia del mes.
     */
    public function mias(Request $request)
    {
        $empleado = $request->user()->empleado;

        abort_if(! $empleado, 403, 'Tu usuario no está enlazado a una ficha de empleado.');

        $asistencias = $empleado->asistencias()
            ->delPeriodo(now()->year, now()->month)
            ->orderByDesc('fecha')
            ->paginate(31);

        return view('asistencias.mias', compact('empleado', 'asistencias'));
    }

    /**
     * Traduce el resultado del servicio a la respuesta que espera la pantalla.
     */
    private function respuestaKiosco(ResultadoMarcaje $resultado)
    {
        return redirect()
            ->route('asistencias.kiosco', $this->parametrosKiosco())
            ->with($resultado->nivel, $resultado->mensaje)
            ->with('tipo', $resultado->tipo);
    }

    private function parametrosKiosco(): array
    {
        $token = config('asistencia.token');

        return $token ? ['token' => $token] : [];
    }

    private function verificarTokenKiosco(Request $request): void
    {
        $token = config('asistencia.token');

        if ($token && ! hash_equals($token, (string) $request->input('token'))) {
            throw new AccessDeniedHttpException('Kiosco no autorizado en este dispositivo.');
        }
    }
}
