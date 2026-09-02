<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Recibos de Pago') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <!-- Ficha del empleado -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 flex flex-wrap gap-6 justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500">Empleado</p>
                    <p class="text-lg font-bold text-gray-800">{{ $empleado->nombre_completo }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $empleado->departamento->nombre ?? 'Sin departamento' }} · DUI {{ $empleado->dui }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Salario base</p>
                    <p class="text-lg font-bold text-gray-800">${{ number_format($empleado->salario_base, 2) }}</p>
                    <a href="{{ route('asistencias.mias') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                        Ver mi asistencia →
                    </a>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    @if ($planillas->isEmpty())
                        <p class="text-center text-gray-500 py-10">Aún no tienes recibos generados.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Período</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Devengado</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-red-500 uppercase">Deducciones</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-green-600 uppercase">Líquido</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Recibo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($planillas as $planilla)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-semibold text-gray-700">{{ $planilla->periodo }}</td>
                                        <td class="px-4 py-3 text-right">${{ number_format($planilla->total_devengado, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-red-500">-${{ number_format($planilla->total_deducciones, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-green-600">${{ number_format($planilla->salario_liquido, 2) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('planillas.pdf', $planilla) }}"
                                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700">
                                                Descargar PDF
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="mt-6">{{ $planillas->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
