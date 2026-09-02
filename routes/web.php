<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\PlanillaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Kiosco de marcaje (PÚBLICO)
|--------------------------------------------------------------------------
| Corre en una tablet de recepción sin sesión iniciada. Por eso:
|  - vive fuera del grupo 'auth' (antes estaba dentro y nadie podía marcar),
|  - el POST pasa por el rate limiter 'kiosco' (AppServiceProvider),
|  - opcionalmente se protege con KIOSCO_TOKEN en el .env.
*/
Route::get('/kiosco', [AsistenciaController::class, 'kiosco'])->name('asistencias.kiosco');
Route::post('/kiosco/marcar', [AsistenciaController::class, 'marcar'])
    ->middleware('throttle:kiosco')
    ->name('asistencias.marcar');

/*
|--------------------------------------------------------------------------
| Zona autenticada
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil de la cuenta
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Autoservicio del empleado
    |----------------------------------------------------------------------
    | Cualquier usuario autenticado entra aquí, pero solo ve SUS datos:
    | el controlador resuelve el empleado desde la sesión, nunca desde la URL.
    */
    Route::get('/mis-recibos', [PlanillaController::class, 'mios'])->name('planillas.mios');
    Route::get('/mi-asistencia', [AsistenciaController::class, 'mias'])->name('asistencias.mias');

    // El PDF lo protege PlanillaPolicy: RRHH ve todos, el empleado solo el suyo.
    Route::get('/planillas/{planilla}/pdf', [PlanillaController::class, 'descargarPdf'])->name('planillas.pdf');

    /*
    |----------------------------------------------------------------------
    | Módulo de empleados
    |----------------------------------------------------------------------
    */
    // Las rutas literales ('/empleados/crear') se registran ANTES que las
    // rutas con parámetro ('/empleados/{empleado}') para que el enlace de
    // modelo no intente resolver "crear" como un ID.
    Route::middleware('permission:empleados.gestionar')->group(function () {
        Route::get('/empleados/crear', [EmpleadoController::class, 'create'])->name('empleados.create');
        Route::post('/empleados', [EmpleadoController::class, 'store'])->name('empleados.store');
        Route::get('/empleados/{empleado}/editar', [EmpleadoController::class, 'edit'])->name('empleados.edit');
        Route::put('/empleados/{empleado}', [EmpleadoController::class, 'update'])->name('empleados.update');
        Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy'])->name('empleados.destroy');
    });

    Route::middleware('permission:empleados.ver')->group(function () {
        Route::get('/empleados', [EmpleadoController::class, 'index'])->name('empleados.index');
        Route::get('/empleados/{empleado}', [EmpleadoController::class, 'show'])->name('empleados.show');
        Route::get('/empleados/{empleado}/credencial', [EmpleadoController::class, 'credencial'])->name('empleados.credencial');
    });

    /*
    |----------------------------------------------------------------------
    | Departamentos
    |----------------------------------------------------------------------
    */
    Route::middleware('permission:departamentos.gestionar')->group(function () {
        Route::get('/departamentos/crear', [DepartamentoController::class, 'create'])->name('departamentos.create');
        Route::post('/departamentos', [DepartamentoController::class, 'store'])->name('departamentos.store');
        Route::get('/departamentos/{departamento}/editar', [DepartamentoController::class, 'edit'])->name('departamentos.edit');
        Route::put('/departamentos/{departamento}', [DepartamentoController::class, 'update'])->name('departamentos.update');
        Route::delete('/departamentos/{departamento}', [DepartamentoController::class, 'destroy'])->name('departamentos.destroy');
    });

    Route::get('/departamentos', [DepartamentoController::class, 'index'])
        ->middleware('permission:empleados.ver')
        ->name('departamentos.index');

    /*
    |----------------------------------------------------------------------
    | Nómina
    |----------------------------------------------------------------------
    */
    Route::get('/planillas', [PlanillaController::class, 'index'])
        ->middleware('permission:planillas.ver')
        ->name('planillas.index');

    Route::post('/planillas/generar', [PlanillaController::class, 'generar'])
        ->middleware('permission:planillas.generar')
        ->name('planillas.generar');

    /*
    |----------------------------------------------------------------------
    | Reporte de asistencia
    |----------------------------------------------------------------------
    */
    Route::get('/asistencias', [AsistenciaController::class, 'index'])
        ->middleware('permission:asistencias.ver')
        ->name('asistencias.index');

    /*
    |----------------------------------------------------------------------
    | Usuarios y roles (solo administrador)
    |----------------------------------------------------------------------
    */
    Route::middleware('permission:usuarios.gestionar')->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    });
});

require __DIR__.'/auth.php';
