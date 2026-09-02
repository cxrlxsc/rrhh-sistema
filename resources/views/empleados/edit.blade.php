<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Editar Empleado') }} · {{ $empleado->nombre_completo }}
            </h2>
            <a href="{{ route('empleados.show', $empleado) }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                Ver ficha completa →
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <form action="{{ route('empleados.update', $empleado) }}" method="POST">
                        @csrf
                        @method('PUT')

                        @include('empleados.partials.form')

                        <div class="flex justify-end items-center mt-6">
                            <a href="{{ route('empleados.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">Cancelar</a>
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                                Guardar cambios
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
