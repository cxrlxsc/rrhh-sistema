<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Vacaciones') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash />

            <div class="bg-indigo-50 border-l-4 border-indigo-400 text-indigo-900 p-4 rounded text-sm">
                <p class="font-semibold mb-1">Regla aplicada (Código de Trabajo, Art. 177)</p>
                <p>
                    Tras cada año continuo de trabajo el empleado gana
                    <strong>{{ config('prestaciones.vacaciones.dias_por_anio') }} días</strong> remunerados,
                    que se pagan con un recargo del
                    <strong>{{ (int) (config('prestaciones.vacaciones.recargo') * 100) }}%</strong>
                    y deben cancelarse antes de que inicie el goce.
                </p>
            </div>

            @can('prestaciones.gestionar')
                <!-- Registrar período -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-700 mb-4">Registrar período de vacaciones</h3>

                    <form action="{{ route('vacaciones.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        @csrf
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-500 uppercase">Empleado</label>
                            <select name="empleado_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                                <option value="">Seleccione...</option>
                                @foreach ($empleados as $empleado)
                                    <option value="{{ $empleado->id }}" @selected(old('empleado_id') == $empleado->id)>
                                        {{ $empleado->nombre_completo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Inicio</label>
                            <input type="date" name="fecha_inicio" required value="{{ old('fecha_inicio') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Días</label>
                            <input type="number" name="dias" min="1" max="30" required value="{{ old('dias', config('prestaciones.vacaciones.dias_por_anio')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <button class="w-full px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                Registrar
                            </button>
                        </div>
                        <div class="md:col-span-5">
                            <input type="text" name="observaciones" placeholder="Observaciones (opcional)" value="{{ old('observaciones') }}"
                                   class="block w-full rounded-md border-gray-300 text-sm">
                        </div>
                    </form>
                </div>
            @endcan

            <!-- Saldos por empleado -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold text-gray-700 mb-4">Saldo de días por empleado</h3>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Antigüedad</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ganados</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tomados</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Disponibles</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($saldos as $saldo)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $saldo['empleado']->nombre_completo }}</td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ $saldo['empleado']->anios_de_servicio }} año(s)</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $saldo['ganados'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $saldo['tomados'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                        {{ $saldo['disponibles'] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $saldo['disponibles'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Períodos registrados -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                <h3 class="font-semibold text-gray-700 mb-4">Períodos registrados</h3>

                @if ($vacaciones->isEmpty())
                    <p class="text-center text-gray-500 py-8">Aún no hay vacaciones registradas.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Período</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Días</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Salario</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Recargo 30%</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-green-600 uppercase">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($vacaciones as $vacacion)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $vacacion->empleado->nombre_completo }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600">
                                        {{ $vacacion->fecha_inicio->format('d/m/Y') }} — {{ $vacacion->fecha_fin->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ $vacacion->dias }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($vacacion->monto_base, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-indigo-600">+${{ number_format($vacacion->recargo, 2) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-green-600">${{ number_format($vacacion->total_pagado, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @can('prestaciones.gestionar')
                                            <form action="{{ route('vacaciones.update', $vacacion) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <select name="estado" onchange="this.form.submit()"
                                                        class="text-xs rounded-md border-gray-300 py-1">
                                                    @foreach ($estados as $valor => $etiqueta)
                                                        <option value="{{ $valor }}" @selected($vacacion->estado === $valor)>{{ $etiqueta }}</option>
                                                    @endforeach
                                                </select>
                                            </form>
                                        @else
                                            {{ $estados[$vacacion->estado] ?? $vacacion->estado }}
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-6">{{ $vacaciones->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
