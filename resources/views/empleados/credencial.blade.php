<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Credencial de Identificación') }}
            </h2>
            <button onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                Imprimir Gafete
            </button>
        </div>
    </x-slot>

    <div class="py-12 flex justify-center">
        <!-- Diseño del Gafete -->
        <div class="bg-white w-80 rounded-xl shadow-2xl overflow-hidden border-2 border-gray-100 relative print:shadow-none print:border-gray-300">
            
            <!-- Encabezado del Gafete -->
            <div class="bg-indigo-600 h-24 flex items-center justify-center text-white">
                <h3 class="text-xl font-black tracking-widest uppercase">Empresa Demo</h3>
            </div>

            <!-- Foto de Perfil (Placeholder) -->
            <div class="flex justify-center -mt-12">
                <div class="w-24 h-24 bg-gray-200 rounded-full border-4 border-white flex items-center justify-center overflow-hidden shadow-inner">
                    <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>

            <!-- Datos del Empleado -->
            <div class="text-center px-6 py-4">
                <h2 class="text-2xl font-bold text-gray-800 leading-tight">{{ $empleado->nombres }}</h2>
                <h2 class="text-xl text-gray-600 mb-2">{{ $empleado->apellidos }}</h2>
                
                <span class="inline-block bg-indigo-100 text-indigo-800 text-xs px-3 py-1 rounded-full font-semibold uppercase tracking-wide mb-4">
                    {{ $empleado->departamento->nombre ?? 'Staff' }}
                </span>
                
                <p class="text-sm text-gray-500 font-mono">DUI: {{ $empleado->dui }}</p>
            </div>

            <!-- Código QR -->
            <div class="bg-gray-50 flex flex-col items-center justify-center py-6 border-t">
                <div class="p-2 bg-white rounded-lg shadow-sm">
                    <!-- Aquí imprimimos el código QR generado en el backend -->
                    {!! $qr !!}
                </div>
                <p class="text-xs text-gray-400 mt-2">Escanea para marcar asistencia</p>
            </div>

        </div>
    </div>

    <!-- Estilos específicos para impresión -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .py-12, .py-12 * {
                visibility: visible;
            }
            .py-12 {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</x-app-layout>