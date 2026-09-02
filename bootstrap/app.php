<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias de spatie/laravel-permission para usarlos en las rutas:
        // ->middleware('role:admin'), ->middleware('permission:planillas.generar')
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Un 403 dentro del sistema no debe mostrar la pantalla cruda de error:
        // se devuelve al usuario a su panel con un aviso claro.
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes permisos para esta acción.'], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'No tienes permisos para acceder a esa sección.');
        });
    })->create();
