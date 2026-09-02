<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Control de Asistencia') }}
            </h2>
            <a href="{{ route('asistencias.kiosco') }}" target="_blank"
               class="text-sm px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Abrir kiosco de marcaje ↗
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <!-- Resumen del rango -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-5 border-l-4 border-indigo-500">
                    <p class="text-xs text-gray-500 uppercase">Marcajes en el rango</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $resumen['registros'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5 border-l-4 border-amber-500">
                    <p class="text-xs text-gray-500 uppercase">Llegadas tardías</p>
                    <p class="text-2xl font-bold text-amber-600">{{ $resumen['tardanzas'] }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5 border-l-4 border-red-500">
                    <p class="text-xs text-gray-500 uppercase">Jornadas sin cerrar</p>
                    <p class="text-2xl font-bold text-red-600">{{ $resumen['sin_salida'] }}</p>
                </div>
            </div>

            <!-- Filtros -->
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Desde</label>
                    <input type="date" name="desde" value="{{ $desde->toDateString() }}" class="mt-1 rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Hasta</label>
                    <input type="date" name="hasta" value="{{ $hasta->toDateString() }}" class="mt-1 rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Empleado</label>
                    <select name="empleado_id" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Todos</option>
                        @foreach ($empleados as $empleado)
                            <option value="{{ $empleado->id }}" @selected(request('empleado_id') == $empleado->id)>
                                {{ $empleado->nombre_completo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Filtrar</button>
                <a href="{{ route('asistencias.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Limpiar</a>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    @if ($asistencias->isEmpty())
                        <p class="text-center text-gray-500 py-10">No hay marcajes en el rango seleccionado.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Entrada</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Salida</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jornada</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($asistencias as $asistencia)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                            {{ $asistencia->fecha->translatedFormat('D d M Y') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                            {{ $asistencia->empleado->nombre_completo }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            {{ $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('h:i A') : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            {{ $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('h:i A') : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-600">{{ $asistencia->jornada }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($asistencia->llego_tarde)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                    Tardía (+{{ $asistencia->minutos_tarde }} min)
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                    Puntual
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-6">{{ $asistencias->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
