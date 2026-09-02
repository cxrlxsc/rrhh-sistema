<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap gap-3 justify-between items-center w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Nómina y Planillas') }}
            </h2>

            @can('planillas.generar')
                <form action="{{ route('planillas.generar') }}" method="POST" class="m-0 flex items-center gap-2"
                      onsubmit="return confirm('¿Generar la planilla del período seleccionado?');">
                    @csrf
                    <select name="mes" class="rounded-md border-gray-300 text-sm">
                        @foreach ($meses as $numero => $nombre)
                            <option value="{{ $numero }}" @selected($numero === ($mes ?? now()->month))>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <select name="anio" class="rounded-md border-gray-300 text-sm">
                        @foreach ($anios as $a)
                            <option value="{{ $a }}" @selected($a === $anio)>{{ $a }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Generar planilla
                    </button>
                </form>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <!-- Filtro del período consultado -->
            <form method="GET" class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Mes</label>
                    <select name="mes" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Todo el año</option>
                        @foreach ($meses as $numero => $nombre)
                            <option value="{{ $numero }}" @selected($mes === $numero)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase">Año</label>
                    <select name="anio" class="mt-1 rounded-md border-gray-300 text-sm">
                        @foreach ($anios as $a)
                            <option value="{{ $a }}" @selected($a === $anio)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">Filtrar</button>
            </form>

            <!-- Resumen del período -->
            @if ($totales && $totales->registros > 0)
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Recibos</p>
                        <p class="text-xl font-bold text-gray-800">{{ $totales->registros }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Devengado</p>
                        <p class="text-xl font-bold text-gray-800">${{ number_format($totales->devengado, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Renta retenida</p>
                        <p class="text-xl font-bold text-amber-600">${{ number_format($totales->renta, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Líquido pagado</p>
                        <p class="text-xl font-bold text-green-600">${{ number_format($totales->liquido, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4">
                        <p class="text-xs text-gray-500 uppercase">Costo patronal</p>
                        <p class="text-xl font-bold text-indigo-600">${{ number_format($totales->costo_patronal, 2) }}</p>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">

                    @if ($planillas->isEmpty())
                        <div class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-lg">No hay planillas en este período.</p>
                            <p class="text-sm">Selecciona el mes y presiona “Generar planilla”.</p>
                        </div>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Devengado</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-red-500 uppercase">ISSS</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-red-500 uppercase">AFP</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-red-500 uppercase">Renta</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tramo</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-green-600 uppercase">Líquido</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Recibo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($planillas as $planilla)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500 font-semibold">
                                            {{ $planilla->mes }} {{ $planilla->anio }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">
                                            {{ $planilla->empleado->nombre_completo }}
                                            <span class="block text-xs text-gray-400">
                                                {{ $planilla->empleado->departamento->nombre ?? 'Sin asignar' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-700">${{ number_format($planilla->total_devengado, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-red-500">-${{ number_format($planilla->descuento_isss, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-red-500">-${{ number_format($planilla->descuento_afp, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-red-500">-${{ number_format($planilla->descuento_renta, 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                                {{ $planilla->descuento_renta > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $planilla->tramo_renta }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-green-600">${{ number_format($planilla->salario_liquido, 2) }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('planillas.pdf', $planilla) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-6">
                            {{ $planillas->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
