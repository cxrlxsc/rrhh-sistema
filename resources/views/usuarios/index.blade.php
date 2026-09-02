<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Usuarios y Roles') }}
            </h2>
            <a href="{{ route('usuarios.create') }}" class="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition">
                + Nueva cuenta
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correo</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rol</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Empleado enlazado</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($usuarios as $usuario)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $usuario->name }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $usuario->email }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @forelse ($usuario->roles as $rol)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                                @class([
                                                    'bg-purple-100 text-purple-800' => $rol->name === 'admin',
                                                    'bg-indigo-100 text-indigo-800' => $rol->name === 'rrhh',
                                                    'bg-gray-100 text-gray-700' => $rol->name === 'empleado',
                                                ])">
                                                {{ $rol->name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400">Sin rol</span>
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">
                                        {{ $usuario->empleado?->nombre_completo ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ $usuario->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $usuario->activo ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('usuarios.edit', $usuario) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="mt-6">{{ $usuarios->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
