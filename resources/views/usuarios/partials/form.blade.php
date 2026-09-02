{{-- Campos compartidos para crear/editar cuentas de acceso. --}}
@php($usuario = $usuario ?? null)

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" name="name" id="name" required value="{{ old('name', $usuario?->name) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('name') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
        <input type="email" name="email" id="email" required value="{{ old('email', $usuario?->email) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('email') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="rol" class="block text-sm font-medium text-gray-700">Rol</label>
        <select name="rol" id="rol" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($roles as $rol)
                <option value="{{ $rol }}" @selected(old('rol', $usuario?->roles->first()?->name) === $rol)>
                    {{ ucfirst($rol) }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">
            <strong>admin</strong>: control total · <strong>rrhh</strong>: opera empleados, planillas y asistencia ·
            <strong>empleado</strong>: solo autoservicio.
        </p>
        @error('rol') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="empleado_id" class="block text-sm font-medium text-gray-700">Ficha de empleado enlazada</label>
        <select name="empleado_id" id="empleado_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Sin enlazar (usuario administrativo)</option>
            @foreach ($empleados as $empleado)
                <option value="{{ $empleado->id }}" @selected(old('empleado_id', $usuario?->empleado_id) == $empleado->id)>
                    {{ $empleado->nombre_completo }} · {{ $empleado->dui }}
                </option>
            @endforeach
        </select>
        <p class="text-xs text-gray-500 mt-1">Necesario para que el usuario vea sus propios recibos y su asistencia.</p>
        @error('empleado_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
            Contraseña @if ($usuario) <span class="text-gray-400">(dejar vacía para no cambiarla)</span> @endif
        </label>
        <input type="password" name="password" id="password" {{ $usuario ? '' : 'required' }}
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('password') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar contraseña</label>
        <input type="password" name="password_confirmation" id="password_confirmation" {{ $usuario ? '' : 'required' }}
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    @if ($usuario)
        <div class="flex items-end">
            <label class="inline-flex items-center">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo))
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ms-2 text-sm text-gray-700">Cuenta activa</span>
            </label>
        </div>
    @endif
</div>
