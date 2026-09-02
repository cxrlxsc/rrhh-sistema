<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Archivos para Instituciones') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash />

            <div class="bg-amber-50 border-l-4 border-amber-400 text-amber-900 p-4 rounded text-sm">
                <p class="font-semibold mb-1">Antes de subir los archivos</p>
                <p>
                    Se generan en CSV (UTF-8) con los campos que solicitan el ISSS, las AFP y el
                    Ministerio de Hacienda. Cada institución publica su propio instructivo y lo
                    actualiza de vez en cuando: <strong>confirma el orden y el formato de columnas
                    contra el instructivo vigente</strong> antes de cargarlos en el portal.
                </p>
            </div>

            @if ($periodos->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-10 text-center text-gray-500">
                    Todavía no hay planillas generadas, así que no hay nada que exportar.
                </div>
            @else
                <!-- Planillas previsionales -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-700 mb-1">Planillas previsionales (ISSS y AFP)</h3>
                    <p class="text-sm text-gray-500 mb-4">Se generan por período mensual.</p>

                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Empleados</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Descargar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($periodos as $periodo)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $periodo->mes }} {{ $periodo->anio }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $periodo->registros }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex gap-2 justify-end">
                                            @foreach (['isss' => 'ISSS', 'afp' => 'AFP'] as $tipo => $etiqueta)
                                                <form action="{{ route('exportaciones.descargar') }}" method="POST" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="tipo" value="{{ $tipo }}">
                                                    <input type="hidden" name="anio" value="{{ $periodo->anio }}">
                                                    <input type="hidden" name="mes" value="{{ $periodo->mes_numero }}">
                                                    <button class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700">
                                                        {{ $etiqueta }} (CSV)
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Informe anual de renta -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-700 mb-1">Informe anual de retenciones de renta</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Consolidado por empleado de todo el año, base para el informe que se presenta
                        al Ministerio de Hacienda.
                    </p>

                    <form action="{{ route('exportaciones.descargar') }}" method="POST" class="flex flex-wrap gap-3 items-end">
                        @csrf
                        <input type="hidden" name="tipo" value="renta">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Año</label>
                            <select name="anio" class="mt-1 rounded-md border-gray-300 text-sm">
                                @foreach ($anios as $a)
                                    <option value="{{ $a }}">{{ $a }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                            Descargar informe (CSV)
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
