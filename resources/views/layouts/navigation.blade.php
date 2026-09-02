@php
    // Menú construido según los permisos del usuario: cada quien ve solo lo suyo.
    $enlaces = collect([
        ['ruta' => 'dashboard', 'texto' => 'Panel', 'patron' => 'dashboard', 'visible' => auth()->user()->esPersonalRrhh()],
        ['ruta' => 'empleados.index', 'texto' => 'Empleados', 'patron' => 'empleados.*', 'visible' => auth()->user()->can('empleados.ver')],
        ['ruta' => 'departamentos.index', 'texto' => 'Departamentos', 'patron' => 'departamentos.*', 'visible' => auth()->user()->can('empleados.ver')],
        ['ruta' => 'planillas.index', 'texto' => 'Planillas', 'patron' => 'planillas.index', 'visible' => auth()->user()->can('planillas.ver')],
        ['ruta' => 'asistencias.index', 'texto' => 'Asistencia', 'patron' => 'asistencias.index', 'visible' => auth()->user()->can('asistencias.ver')],
        ['ruta' => 'usuarios.index', 'texto' => 'Usuarios', 'patron' => 'usuarios.*', 'visible' => auth()->user()->can('usuarios.gestionar')],
        ['ruta' => 'planillas.mios', 'texto' => 'Mis recibos', 'patron' => 'planillas.mios', 'visible' => auth()->user()->empleado_id !== null],
        ['ruta' => 'asistencias.mias', 'texto' => 'Mi asistencia', 'patron' => 'asistencias.mias', 'visible' => auth()->user()->empleado_id !== null],
    ])->where('visible', true);
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    @foreach ($enlaces as $enlace)
                        <x-nav-link :href="route($enlace['ruta'])" :active="request()->routeIs($enlace['patron'])">
                            {{ __($enlace['texto']) }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @can('asistencias.ver')
                    <a href="{{ route('asistencias.kiosco') }}" target="_blank"
                       class="me-4 text-xs font-semibold text-indigo-600 hover:text-indigo-800 uppercase tracking-wide">
                        Kiosco ↗
                    </a>
                @endcan

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="text-left">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="text-xs text-gray-400">{{ Auth::user()->roles->first()?->name ?? 'sin rol' }}</div>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($enlaces as $enlace)
                <x-responsive-nav-link :href="route($enlace['ruta'])" :active="request()->routeIs($enlace['patron'])">
                    {{ __($enlace['texto']) }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
