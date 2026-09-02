<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mi Asistencia · ') }} {{ ucfirst(now()->translatedFormat('F Y')) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 flex flex-wrap gap-6 justify-between items-center">
                <div>
                    <p class="text-sm text-gray-500">Empleado</p>
                    <p class="text-lg font-bold text-gray-800">{{ $empleado->nombre_completo }}</p>
                </div>
                <div class="flex gap-8 text-center">
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Días marcados</p>
                        <p class="text-2xl font-bold text-indigo-600">{{ $asistencias->total() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase">Tardanzas</p>
                        <p class="text-2xl font-bold text-amber-600">
                            {{ $asistencias->getCollection()->where('estado_entrada', \App\Models\Asistencia::ESTADO_TARDE)->count() }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('planillas.mios') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                    Ver mis recibos →
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    @if ($asistencias->isEmpty())
                        <p class="text-center text-gray-500 py-10">Aún no tienes marcajes este mes.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Entrada</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Salida</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jornada</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($asistencias as $asistencia)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-600">{{ $asistencia->fecha->translatedFormat('D d M') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            {{ $asistencia->hora_entrada ? \Carbon\Carbon::parse($asistencia->hora_entrada)->format('h:i A') : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            {{ $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('h:i A') : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-600">{{ $asistencia->jornada }}</td>
                                        <td class="px-4 py-3 text-center">
                                            @if ($asistencia->llego_tarde)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Tardía</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Puntual</span>
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
