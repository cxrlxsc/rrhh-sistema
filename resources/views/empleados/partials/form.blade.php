{{-- Campos compartidos entre "crear" y "editar" empleado. --}}
@php($empleado = $empleado ?? null)

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label for="nombres" class="block text-sm font-medium text-gray-700">Nombres</label>
        <input type="text" name="nombres" id="nombres" required
               value="{{ old('nombres', $empleado?->nombres) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('nombres') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="apellidos" class="block text-sm font-medium text-gray-700">Apellidos</label>
        <input type="text" name="apellidos" id="apellidos" required
               value="{{ old('apellidos', $empleado?->apellidos) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('apellidos') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="dui" class="block text-sm font-medium text-gray-700">DUI (formato 00000000-0)</label>
        <input type="text" name="dui" id="dui" required placeholder="12345678-9"
               value="{{ old('dui', $empleado?->dui) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('dui') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="departamento_id" class="block text-sm font-medium text-gray-700">Departamento</label>
        <select name="departamento_id" id="departamento_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Seleccione un departamento...</option>
            @foreach ($departamentos as $depto)
                <option value="{{ $depto->id }}" @selected(old('departamento_id', $empleado?->departamento_id) == $depto->id)>
                    {{ $depto->nombre }}
                </option>
            @endforeach
        </select>
        @error('departamento_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700">Fecha de nacimiento</label>
        <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required
               value="{{ old('fecha_nacimiento', $empleado?->fecha_nacimiento?->toDateString()) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('fecha_nacimiento') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="fecha_contratacion" class="block text-sm font-medium text-gray-700">Fecha de contratación</label>
        <input type="date" name="fecha_contratacion" id="fecha_contratacion" required
               value="{{ old('fecha_contratacion', $empleado?->fecha_contratacion?->toDateString()) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('fecha_contratacion') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="salario_base" class="block text-sm font-medium text-gray-700">Salario base mensual ($)</label>
        <input type="number" step="0.01" min="365" name="salario_base" id="salario_base" required placeholder="600.00"
               value="{{ old('salario_base', $empleado?->salario_base) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <p class="text-xs text-gray-500 mt-1">Sobre este monto se calculan ISSS, AFP y la retención de renta.</p>
        @error('salario_base') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
        <p class="text-sm font-semibold text-gray-700">Identificadores previsionales y fiscales</p>
        <p class="text-xs text-gray-500">Necesarios para generar los archivos del ISSS, la AFP y Hacienda.</p>
    </div>

    <div>
        <label for="nit" class="block text-sm font-medium text-gray-700">NIT</label>
        <input type="text" name="nit" id="nit" placeholder="0614-000000-000-0"
               value="{{ old('nit', $empleado?->nit) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('nit') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="numero_isss" class="block text-sm font-medium text-gray-700">N.º de afiliación ISSS</label>
        <input type="text" name="numero_isss" id="numero_isss"
               value="{{ old('numero_isss', $empleado?->numero_isss) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('numero_isss') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="numero_afp" class="block text-sm font-medium text-gray-700">NUP / N.º de AFP</label>
        <input type="text" name="numero_afp" id="numero_afp"
               value="{{ old('numero_afp', $empleado?->numero_afp) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('numero_afp') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="afp_administradora" class="block text-sm font-medium text-gray-700">Administradora de pensiones</label>
        <input type="text" name="afp_administradora" id="afp_administradora" placeholder="Confía / Crecer"
               value="{{ old('afp_administradora', $empleado?->afp_administradora) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('afp_administradora') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
    </div>

    @isset($empleado)
        <div class="flex items-end">
            <label class="inline-flex items-center">
                <input type="hidden" name="activo" value="0">
                <input type="checkbox" name="activo" value="1" @checked(old('activo', $empleado->activo))
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ms-2 text-sm text-gray-700">Empleado activo en planilla</span>
            </label>
        </div>
    @endisset
</div>
