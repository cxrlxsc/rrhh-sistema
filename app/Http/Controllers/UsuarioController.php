<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Administración de cuentas y roles.
 *
 * Aquí se decide "quién es quién" en el sistema: se crea la cuenta, se le
 * asigna un rol (admin / rrhh / empleado) y, si corresponde, se enlaza con
 * su ficha de empleado para habilitar el autoservicio de recibos.
 */
class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with(['roles', 'empleado'])->orderBy('name')->paginate(20);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create', $this->datosFormulario());
    }

    public function store(Request $request)
    {
        $datos = $request->validate($this->reglas());

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
            'empleado_id' => $datos['empleado_id'] ?? null,
            'activo' => true,
        ]);

        $usuario->syncRoles([$datos['rol']]);

        return redirect()->route('usuarios.index')
            ->with('success', "Cuenta creada para {$usuario->name} con rol {$datos['rol']}.");
    }

    public function edit(User $usuario)
    {
        return view('usuarios.edit', $this->datosFormulario() + ['usuario' => $usuario]);
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $request->validate($this->reglas($usuario));

        $usuario->update([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'empleado_id' => $datos['empleado_id'] ?? null,
            'activo' => $request->boolean('activo'),
        ]);

        if (! empty($datos['password'])) {
            $usuario->update(['password' => Hash::make($datos['password'])]);
        }

        // Un administrador no puede quitarse a sí mismo el rol de admin:
        // dejaría al sistema sin nadie capaz de gestionar usuarios.
        if ($usuario->is($request->user()) && $datos['rol'] !== User::ROL_ADMIN) {
            return back()->with('error', 'No puedes cambiar tu propio rol de administrador.');
        }

        $usuario->syncRoles([$datos['rol']]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    private function reglas(?User $usuario = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario)],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'rol' => ['required', Rule::exists('roles', 'name')],
            'empleado_id' => [
                'nullable',
                'exists:empleados,id',
                // Una ficha de empleado solo puede tener una cuenta de acceso.
                Rule::unique('users', 'empleado_id')->ignore($usuario),
            ],
        ];
    }

    private function datosFormulario(): array
    {
        return [
            'roles' => Role::orderBy('name')->pluck('name'),
            'empleados' => Empleado::activos()->orderBy('apellidos')->get(),
        ];
    }
}
