<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva Cuenta de Acceso') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('usuarios.store') }}" method="POST">
                        @csrf

                        @include('usuarios.partials.form', ['usuario' => null])

                        <div class="flex justify-end items-center mt-6">
                            <a href="{{ route('usuarios.index') }}" class="mr-4 text-gray-600 hover:text-gray-900">Cancelar</a>
                            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                                Crear cuenta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
