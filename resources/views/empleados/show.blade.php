<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Ficha del Empleado') }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('empleados.credencial', $empleado) }}" class="text-sm px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Imprimir gafete
                </a>
                @can('empleados.gestionar')
                    <a href="{{ route('empleados.edit', $empleado) }}" class="text-sm px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                        Editar
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash />

            <!-- Datos generales -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap justify-between items-start gap-4">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800">{{ $empleado->nombre_completo }}</h3>
                        <p class="text-gray-500">
                            {{ $empleado->departamento->nombre ?? 'Sin departamento' }} · DUI {{ $empleado->dui }}
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        {{ $empleado->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $empleado->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                <dl class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6">
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Salario base</dt>
                        <dd class="text-lg font-bold text-gray-800">${{ number_format($empleado->salario_base, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Contratación</dt>
                        <dd class="text-lg font-semibold text-gray-700">{{ $empleado->fecha_contratacion?->translatedFormat('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Antigüedad</dt>
                        <dd class="text-lg font-semibold text-gray-700">{{ $empleado->anios_de_servicio }} año(s)</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 uppercase">Nacimiento</dt>
                        <dd class="text-lg font-semibold text-gray-700">{{ $empleado->fecha_nacimiento?->translatedFormat('d M Y') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Últimos recibos -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="font-semibold text-gray-700 mb-4">Últimos recibos</h4>
                    @if ($planillas->isEmpty())
                        <p class="text-gray-500 text-sm">Sin planillas generadas.</p>
                    @else
                        <table class="min-w-full text-sm divide-y divide-gray-200">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($planillas as $planilla)
                                    <tr>
                                        <td class="py-2 font-medium text-gray-700">{{ $planilla->periodo }}</td>
                                        <td class="py-2 text-right text-red-500">-${{ number_format($planilla->total_deducciones, 2) }}</td>
                                        <td class="py-2 text-right font-bold text-green-600">${{ number_format($planilla->salario_liquido, 2) }}</td>
                                        <td class="py-2 text-right">
                                            <a href="{{ route('planillas.pdf', $planilla) }}" class="text-blue-600 hover:text-blue-800">PDF</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <!-- Asistencia reciente -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h4 class="font-semibold text-gray-700 mb-4">Asistencia reciente</h4>
                    @if ($asistencias->isEmpty())
                        <p class="text-gray-500 text-sm">Sin marcajes registrados.</p>
                    @else
                        <table class="min-w-full text-sm divide-y divide-gray-200">
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($asistencias as $asistencia)
                                    <tr>
                                        <td class="py-2 text-gray-600">{{ $asistencia->fecha->translatedFormat('D d M') }}</td>
                                        <td class="py-2 text-center">
                                            {{ $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('h:i A') : '—' }}
                                            →
                                            {{ $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('h:i A') : '—' }}
                                        </td>
                                        <td class="py-2 text-right">
                                            @if ($asistencia->llego_tarde)
                                                <span class="text-amber-600 font-semibold">+{{ $asistencia->minutos_tarde }} min</span>
                                            @else
                                                <span class="text-green-600">Puntual</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
