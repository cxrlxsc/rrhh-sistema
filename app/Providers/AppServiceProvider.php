<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Nombres de meses y días en español ("Agosto", no "August").
        Carbon::setLocale(config('app.locale', 'es'));

        // El administrador no necesita que se le asigne cada permiso a mano.
        Gate::before(fn (User $user) => $user->hasRole(User::ROL_ADMIN) ? true : null);

        $this->configurarLimitesDeAcceso();
    }

    /**
     * Límite de peticiones del kiosco.
     *
     * El endpoint de marcaje es público (una tablet sin sesión), así que se
     * acota por dispositivo/IP para frenar ráfagas automatizadas contra la
     * base de empleados.
     */
    private function configurarLimitesDeAcceso(): void
    {
        RateLimiter::for('kiosco', function (Request $request) {
            $intentos = (int) config('asistencia.rate_limit_intentos', 20);
            $ventana = (int) config('asistencia.rate_limit_ventana_min', 1);

            return Limit::perMinutes($ventana, $intentos)
                ->by($request->ip())
                ->response(fn () => redirect()
                    ->route('asistencias.kiosco')
                    ->with('error', 'Demasiados intentos seguidos. Espera un momento antes de volver a escanear.'));
        });
    }
}
