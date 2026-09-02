<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Calcular Liquidación / Finiquito') }}
            </h2>
            <a href="{{ route('liquidaciones.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                Ver liquidaciones registradas →
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-flash />

            <!-- Paso 1: datos de la salida -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-700 mb-4">1. Datos de la terminación</h3>

                <form action="{{ route('liquidaciones.create') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Empleado</label>
                        <select name="empleado_id" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Seleccione...</option>
                            @foreach ($empleados as $emp)
                                <option value="{{ $emp->id }}" @selected(($datos['empleado_id'] ?? null) == $emp->id)>
                                    {{ $emp->nombre_completo }} — ${{ number_format($emp->salario_base, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Fecha de salida</label>
                        <input type="date" name="fecha_salida" required
                               value="{{ $datos['fecha_salida'] ?? now()->toDateString() }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 uppercase">Motivo</label>
                        <select name="motivo" required class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            @foreach ($motivos as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(($datos['motivo'] ?? null) === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">
                            Solo el despido sin causa justificada genera indemnización.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Salarios pendientes</label>
                            <input type="number" step="0.01" min="0" name="salarios_pendientes"
                                   value="{{ $datos['salarios_pendientes'] ?? 0 }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 uppercase">Otras deducciones</label>
                            <input type="number" step="0.01" min="0" name="otras_deducciones"
                                   value="{{ $datos['otras_deducciones'] ?? 0 }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <input type="text" name="observaciones" placeholder="Observaciones (opcional)"
                               value="{{ $datos['observaciones'] ?? '' }}"
                               class="block w-full rounded-md border-gray-300 text-sm">
                    </div>

                    <div class="md:col-span-2 flex justify-end">
                        <button class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-700">
                            Calcular finiquito
                        </button>
                    </div>
                </form>
            </div>

            <!-- Paso 2: resultado del cálculo (aún sin guardar) -->
            @if ($desglose)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border-t-4 border-indigo-500">
                    <h3 class="font-semibold text-gray-700 mb-1">2. Finiquito calculado</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        {{ $empleado->nombre_completo }} · {{ $desglose->aniosServicio }} año(s) y
                        {{ $desglose->diasServicio }} días de servicio · salario diario
                        ${{ number_format($desglose->salarioDiario, 2) }}
                        @if ($desglose->indemnizacion > 0 && $desglose->salarioDiarioTopado < $desglose->salarioDiario)
                            <span class="text-amber-600">
                                (topado a ${{ number_format($desglose->salarioDiarioTopado, 2) }} para la indemnización)
                            </span>
                        @endif
                    </p>

                    <table class="min-w-full divide-y divide-gray-200 text-sm mb-6">
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-2 text-gray-700">Indemnización por despido (30 días por año)</td>
                                <td class="py-2 text-right font-medium">${{ number_format($desglose->indemnizacion, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-gray-700">Prestación por renuncia voluntaria (15 días por año)</td>
                                <td class="py-2 text-right font-medium">${{ number_format($desglose->prestacionRenuncia, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-gray-700">Vacación proporcional (incluye 30% de recargo)</td>
                                <td class="py-2 text-right font-medium">${{ number_format($desglose->vacacionProporcional, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-gray-700">Aguinaldo proporcional</td>
                                <td class="py-2 text-right font-medium">${{ number_format($desglose->aguinaldoProporcional, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-gray-700">Salarios pendientes</td>
                                <td class="py-2 text-right font-medium">${{ number_format($desglose->salariosPendientes, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-red-600">Otras deducciones</td>
                                <td class="py-2 text-right font-medium text-red-600">-${{ number_format($desglose->otrasDeducciones, 2) }}</td>
                            </tr>
                            <tr class="bg-green-50">
                                <td class="py-3 font-bold text-gray-800">TOTAL A PAGAR</td>
                                <td class="py-3 text-right font-bold text-green-700 text-lg">
                                    ${{ number_format($desglose->totalAPagar, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="bg-amber-50 border-l-4 border-amber-400 text-amber-900 p-4 rounded text-sm mb-4">
                        Al confirmar, se registra el finiquito y <strong>el empleado queda dado de baja</strong>
                        con fecha {{ \Carbon\Carbon::parse($datos['fecha_salida'])->format('d/m/Y') }}.
                        Esta acción no se puede deshacer desde la interfaz.
                    </div>

                    <form action="{{ route('liquidaciones.store') }}" method="POST"
                          onsubmit="return confirm('¿Confirmar la liquidación y dar de baja al empleado?');">
                        @csrf
                        <input type="hidden" name="empleado_id" value="{{ $datos['empleado_id'] }}">
                        <input type="hidden" name="fecha_salida" value="{{ $datos['fecha_salida'] }}">
                        <input type="hidden" name="motivo" value="{{ $datos['motivo'] }}">
                        <input type="hidden" name="salarios_pendientes" value="{{ $datos['salarios_pendientes'] ?? 0 }}">
                        <input type="hidden" name="otras_deducciones" value="{{ $datos['otras_deducciones'] ?? 0 }}">
                        <input type="hidden" name="observaciones" value="{{ $datos['observaciones'] ?? '' }}">

                        <div class="flex justify-end">
                            <button class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700">
                                Confirmar y registrar liquidación
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
