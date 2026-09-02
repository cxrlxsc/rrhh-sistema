<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Nuevo Departamento') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form action="{{ route('departamentos.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Departamento</label>
                            <input type="text" name="nombre" id="nombre" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required placeholder="Ej: Recursos Humanos, IT, Ventas...">
                        </div>

                        <div class="mb-4">
                            <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción (Opcional)</label>
                            <textarea name="descripcion" id="descripcion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Breve descripción de las funciones del área..."></textarea>
                        </div>

                        <div class="flex justify-end items-center">
                            <a href="{{ route('departamentos.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">Cancelar</a>
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                                Guardar Departamento
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>