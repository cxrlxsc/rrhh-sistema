<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Aguinaldos') }} {{ $anio }}
            </h2>

            @can('prestaciones.gestionar')
                <form action="{{ route('aguinaldos.generar') }}" method="POST" class="m-0 flex items-center gap-2"
                      onsubmit="return confirm('¿Calcular el aguinaldo de todos los empleados activos de {{ $anio }}?');">
                    @csrf
                    <input type="hidden" name="anio" value="{{ $anio }}">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                        Calcular aguinaldo {{ $anio }}
                    </button>
                </form>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <!-- Reglas aplicadas: que el usuario vea de dónde salen los números -->
            <div class="bg-indigo-50 border-l-4 border-indigo-400 text-indigo-900 p-4 mb-6 rounded text-sm">
                <p class="font-semibold mb-1">Reglas aplicadas (Código de Trabajo, Arts. 196-202)</p>
                <p>
                    Antigüedad medida al <strong>{{ $fechaCorte->translatedFormat('d \d\e F \d\e Y') }}</strong>:
                    1 a 3 años → 15 días · 3 a 10 años → 19 días · 10 años o más → 21 días.
                    Quien no cumple un año recibe la proporción del tiempo laborado.
                    Exento de renta hasta <strong>${{ number_format($montoExento, 2) }}</strong> (dos salarios mínimos);
                    no cotiza ISSS ni AFP.
                </p>
            </div>

            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Año</label>
                    <select name="anio" class="mt-1 rounded-md border-gray-300 text-sm">
                        @foreach ($anios as $a)
                            <option value="{{ $a }}" @selected($a === $anio)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Ver</button>
            </form>

            @if ($totales && $totales->registros > 0)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Empleados</p>
                        <p class="text-xl font-bold text-gray-800">{{ $totales->registros }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Aguinaldo bruto</p>
                        <p class="text-xl font-bold text-gray-800">${{ number_format($totales->bruto, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Renta retenida</p>
                        <p class="text-xl font-bold text-amber-600">${{ number_format($totales->renta, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Total a pagar</p>
                        <p class="text-xl font-bold text-green-600">${{ number_format($totales->neto, 2) }}</p>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    @if ($aguinaldos->isEmpty())
                        <p class="text-center text-gray-500 py-10">
                            No hay aguinaldos calculados para {{ $anio }}.
                        </p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Antigüedad</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Días</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Bruto</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Exento</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-red-500 uppercase">Renta</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-green-600 uppercase">Neto</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Comprobante</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($aguinaldos as $aguinaldo)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $aguinaldo->empleado->nombre_completo }}
                                            <span class="block text-xs text-gray-400">
                                                {{ $aguinaldo->empleado->departamento->nombre ?? 'Sin asignar' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-600">
                                            {{ $aguinaldo->anios_servicio }} año(s)
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            {{ rtrim(rtrim(number_format($aguinaldo->dias_aplicados, 2), '0'), '.') }}
                                            @if ($aguinaldo->proporcional)
                                                <span class="block text-xs text-amber-600 font-semibold">proporcional</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">${{ number_format($aguinaldo->monto_bruto, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-gray-500">${{ number_format($aguinaldo->monto_exento, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-red-500">-${{ number_format($aguinaldo->descuento_renta, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-green-600">${{ number_format($aguinaldo->monto_neto, 2) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('aguinaldos.pdf', $aguinaldo) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-6">{{ $aguinaldos->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
