<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Liquidaciones') }}
            </h2>
            @can('prestaciones.gestionar')
                <a href="{{ route('liquidaciones.create') }}" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition text-sm">
                    + Calcular finiquito
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    @if ($liquidaciones->isEmpty())
                        <p class="text-center text-gray-500 py-10">No hay liquidaciones registradas.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Salida</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Antigüedad</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Indemnización</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Proporcionales</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-green-600 uppercase">Total</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Finiquito</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($liquidaciones as $liquidacion)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $liquidacion->empleado->nombre_completo }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $liquidacion->fecha_salida->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $liquidacion->motivo_legible }}</td>
                                        <td class="px-4 py-3 text-center text-gray-600">{{ $liquidacion->anios_servicio }} año(s)</td>
                                        <td class="px-4 py-3 text-right">
                                            ${{ number_format($liquidacion->indemnizacion + $liquidacion->prestacion_renuncia, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            ${{ number_format($liquidacion->vacacion_proporcional + $liquidacion->aguinaldo_proporcional, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-green-600">
                                            ${{ number_format($liquidacion->total_a_pagar, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('liquidaciones.pdf', $liquidacion) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-6">{{ $liquidaciones->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
