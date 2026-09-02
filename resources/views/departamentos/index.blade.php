<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de Departamentos') }}
            </h2>
            @can('departamentos.gestionar')
                <a href="{{ route('departamentos.create') }}" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                    + Nuevo departamento
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Empleados activos</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($departamentos as $depto)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $depto->nombre }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $depto->descripcion }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                            {{ $depto->empleados_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        @can('departamentos.gestionar')
                                            <div class="flex gap-3 justify-end">
                                                <a href="{{ route('departamentos.edit', $depto) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>

                                                <form action="{{ route('departamentos.destroy', $depto) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('¿Eliminar este departamento?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800">Eliminar</button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($departamentos->isEmpty())
                        <p class="text-center text-gray-500 mt-6">No hay departamentos registrados aún.</p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
