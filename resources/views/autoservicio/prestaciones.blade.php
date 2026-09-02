<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Prestaciones') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash />

            <!-- Saldo de vacaciones -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-4">Saldo de vacaciones</h3>

                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Días ganados</p>
                        <p class="text-3xl font-bold text-gray-800">{{ $saldo['ganados'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Días tomados</p>
                        <p class="text-3xl font-bold text-gray-500">{{ $saldo['tomados'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Disponibles</p>
                        <p class="text-3xl font-bold text-green-600">{{ $saldo['disponibles'] }}</p>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mt-4 text-center">
                    Se ganan {{ config('prestaciones.vacaciones.dias_por_anio') }} días por cada año continuo de trabajo,
                    pagados con un recargo del {{ (int) (config('prestaciones.vacaciones.recargo') * 100) }}%.
                </p>

                @if ($vacaciones->isNotEmpty())
                    <table class="min-w-full divide-y divide-gray-200 text-sm mt-6">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Días</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Pagado</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($vacaciones as $vacacion)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">
                                        {{ $vacacion->fecha_inicio->format('d/m/Y') }} — {{ $vacacion->fecha_fin->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-2 text-center">{{ $vacacion->dias }}</td>
                                    <td class="px-4 py-2 text-right font-medium">${{ number_format($vacacion->total_pagado, 2) }}</td>
                                    <td class="px-4 py-2 text-center capitalize text-gray-600">{{ $vacacion->estado }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <!-- Aguinaldos -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-4">Aguinaldos</h3>

                @if ($aguinaldos->isEmpty())
                    <p class="text-gray-500 text-sm">Aún no tienes aguinaldos calculados.</p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Año</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Días</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Bruto</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-red-500 uppercase">Renta</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-green-600 uppercase">Recibido</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Comprobante</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($aguinaldos as $aguinaldo)
                                <tr>
                                    <td class="px-4 py-2 font-medium text-gray-700">{{ $aguinaldo->anio }}</td>
                                    <td class="px-4 py-2 text-center">{{ rtrim(rtrim(number_format($aguinaldo->dias_aplicados, 2), '0'), '.') }}</td>
                                    <td class="px-4 py-2 text-right">${{ number_format($aguinaldo->monto_bruto, 2) }}</td>
                                    <td class="px-4 py-2 text-right text-red-500">-${{ number_format($aguinaldo->descuento_renta, 2) }}</td>
                                    <td class="px-4 py-2 text-right font-bold text-green-600">${{ number_format($aguinaldo->monto_neto, 2) }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <a href="{{ route('aguinaldos.pdf', $aguinaldo) }}" class="text-blue-600 hover:text-blue-800">PDF</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            @if ($liquidacion)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-l-4 border-gray-400">
                    <h3 class="font-semibold text-gray-700 mb-2">Liquidación</h3>
                    <p class="text-sm text-gray-600">
                        Fecha de retiro: {{ $liquidacion->fecha_salida->format('d/m/Y') }} ·
                        {{ $liquidacion->motivo_legible }} ·
                        Total: <strong class="text-green-600">${{ number_format($liquidacion->total_a_pagar, 2) }}</strong>
                    </p>
                    <a href="{{ route('liquidaciones.pdf', $liquidacion) }}" class="text-sm text-blue-600 hover:text-blue-800">
                        Descargar finiquito →
                    </a>
                </div>
            @endif

            <div class="text-center">
                <a href="{{ route('planillas.mios') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Ver mis recibos</a>
                <span class="text-gray-300 mx-2">·</span>
                <a href="{{ route('asistencias.mias') }}" class="text-sm text-indigo-600 hover:text-indigo-800">Ver mi asistencia</a>
            </div>
        </div>
    </div>
</x-app-layout>
