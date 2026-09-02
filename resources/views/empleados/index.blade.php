<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Directorio de Empleados') }}
            </h2>
            @can('empleados.gestionar')
                <a href="{{ route('empleados.create') }}" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                    + Nuevo empleado
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <!-- Buscador y filtros -->
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-500 uppercase">Buscar</label>
                    <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Nombre, apellido o DUI"
                           class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Departamento</label>
                    <select name="departamento_id" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Todos</option>
                        @foreach ($departamentos as $depto)
                            <option value="{{ $depto->id }}" @selected(($filtros['departamento_id'] ?? null) == $depto->id)>
                                {{ $depto->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Estado</label>
                    <select name="estado" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Todos</option>
                        <option value="activos" @selected(($filtros['estado'] ?? null) === 'activos')>Activos</option>
                        <option value="inactivos" @selected(($filtros['estado'] ?? null) === 'inactivos')>Inactivos</option>
                    </select>
                </div>
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Filtrar</button>
                <a href="{{ route('empleados.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Limpiar</a>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">

                    @if ($empleados->isEmpty())
                        <p class="text-center text-gray-500 py-10">No se encontraron empleados con esos criterios.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre completo</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">DUI</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departamento</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Salario</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($empleados as $empleado)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                            <a href="{{ route('empleados.show', $empleado) }}" class="hover:text-indigo-600">
                                                {{ $empleado->nombre_completo }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $empleado->dui }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                            {{ $empleado->departamento->nombre ?? 'Sin asignar' }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-600">${{ number_format($empleado->salario_base, 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $empleado->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $empleado->activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                            <div class="flex gap-3 justify-end items-center">
                                                <a href="{{ route('empleados.credencial', $empleado) }}" class="text-indigo-600 hover:text-indigo-900">Gafete</a>

                                                @can('empleados.gestionar')
                                                    <a href="{{ route('empleados.edit', $empleado) }}" class="text-gray-600 hover:text-gray-900">Editar</a>

                                                    <form action="{{ route('empleados.destroy', $empleado) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('{{ $empleado->activo ? '¿Dar de baja a este empleado?' : '¿Reactivar a este empleado?' }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="{{ $empleado->activo ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                                            {{ $empleado->activo ? 'Dar de baja' : 'Reactivar' }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-6">{{ $empleados->links() }}</div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
