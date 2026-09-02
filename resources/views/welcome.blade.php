<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema RRHH · Nómina y Asistencia | El Salvador</title>
    <meta name="description" content="Sistema de gestión de recursos humanos con cálculo de planilla conforme a la legislación salvadoreña, control de asistencia por QR y prestaciones laborales.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-300 antialiased">

    {{-- ================= NAVEGACIÓN ================= --}}
    <header class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur border-b border-slate-800">
        <nav class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <span class="font-bold text-white tracking-tight">Sistema<span class="text-indigo-400">RRHH</span></span>

            <div class="flex items-center gap-6 text-sm">
                <a href="#modulos" class="hidden sm:block hover:text-white transition">Módulos</a>
                <a href="#demo" class="hidden sm:block hover:text-white transition">Probarlo</a>
                <a href="{{ route('login') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-500 transition">
                    Entrar
                </a>
            </div>
        </nav>
    </header>

    {{-- ================= PORTADA ================= --}}
    <section class="max-w-6xl mx-auto px-6 pt-20 pb-16">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <span class="inline-block px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-300 text-xs font-semibold tracking-wide uppercase mb-6">
                    Proyecto de portafolio · Laravel 12
                </span>

                <h1 class="text-4xl sm:text-5xl font-bold text-white leading-tight mb-6">
                    Sistema de Recursos Humanos y Nómina
                </h1>

                <p class="text-lg text-slate-400 mb-8 leading-relaxed">
                    Gestión de personal, control de asistencia por escaneo de QR y cálculo de planilla
                    conforme a la legislación salvadoreña: ISSS, AFP e Impuesto sobre la Renta por tramos,
                    aguinaldo, vacaciones y liquidaciones.
                </p>

                <div class="flex flex-wrap gap-3">
                    <a href="#demo" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 transition">
                        Probar el sistema
                    </a>
                    <a href="{{ route('asistencias.kiosco') }}" target="_blank"
                       class="px-6 py-3 border border-slate-700 text-slate-200 rounded-lg font-semibold hover:border-slate-500 hover:text-white transition">
                        Ver el kiosco de marcaje ↗
                    </a>
                </div>

                <div class="flex flex-wrap gap-x-8 gap-y-3 mt-10 text-sm text-slate-500">
                    <span><strong class="text-white">115</strong> pruebas automatizadas</span>
                    <span><strong class="text-white">6</strong> módulos</span>
                    <span><strong class="text-white">3</strong> niveles de acceso</span>
                </div>
            </div>

            {{-- Vista previa: panel de control --}}
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-2xl">
                <div class="flex gap-1.5 mb-5">
                    <span class="w-3 h-3 rounded-full bg-slate-700"></span>
                    <span class="w-3 h-3 rounded-full bg-slate-700"></span>
                    <span class="w-3 h-3 rounded-full bg-slate-700"></span>
                </div>

                <p class="text-xs text-slate-500 uppercase tracking-wide mb-4">Panel de control · Agosto 2026</p>

                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="bg-slate-800/50 rounded-lg p-4 border-l-2 border-indigo-500">
                        <p class="text-xs text-slate-500">Empleados activos</p>
                        <p class="text-2xl font-bold text-white">7</p>
                    </div>
                    <div class="bg-slate-800/50 rounded-lg p-4 border-l-2 border-green-500">
                        <p class="text-xs text-slate-500">Nómina líquida</p>
                        <p class="text-2xl font-bold text-white">$8,035</p>
                    </div>
                    <div class="bg-slate-800/50 rounded-lg p-4 border-l-2 border-blue-500">
                        <p class="text-xs text-slate-500">Costo patronal</p>
                        <p class="text-2xl font-bold text-white">$11,258</p>
                    </div>
                    <div class="bg-slate-800/50 rounded-lg p-4 border-l-2 border-amber-500">
                        <p class="text-xs text-slate-500">Renta retenida</p>
                        <p class="text-2xl font-bold text-white">$1,362</p>
                    </div>
                </div>

                <p class="text-xs text-slate-500 uppercase tracking-wide mb-2">Asistencia de hoy</p>
                <div class="w-full bg-slate-800 rounded-full h-2 mb-1">
                    <div class="bg-amber-500 h-2 rounded-full" style="width: 71%"></div>
                </div>
                <p class="text-xs text-slate-600">5 de 7 presentes · 1 tardanza</p>
            </div>
        </div>
    </section>

    {{-- ================= EL CÁLCULO ================= --}}
    <section class="border-y border-slate-800 bg-slate-900/30">
        <div class="max-w-6xl mx-auto px-6 py-16 grid lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-2xl font-bold text-white mb-4">El corazón: el cálculo de planilla</h2>
                <p class="text-slate-400 mb-6 leading-relaxed">
                    La retención de renta no es un porcentaje fijo. Se calcula sobre la base imponible
                    —el salario menos ISSS y AFP— aplicando la tabla de tramos del Ministerio de Hacienda:
                    una cuota fija más un porcentaje sobre el excedente.
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-slate-500 text-xs uppercase border-b border-slate-800">
                                <th class="text-left py-2">Tramo</th>
                                <th class="text-left py-2">Desde</th>
                                <th class="text-left py-2">Hasta</th>
                                <th class="text-right py-2">%</th>
                                <th class="text-right py-2">Cuota fija</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-400">
                            <tr class="border-b border-slate-800/50">
                                <td class="py-2 text-white font-medium">I</td>
                                <td>$0.01</td><td>$472.00</td>
                                <td class="text-right">Exento</td><td class="text-right">—</td>
                            </tr>
                            <tr class="border-b border-slate-800/50">
                                <td class="py-2 text-white font-medium">II</td>
                                <td>$472.01</td><td>$895.24</td>
                                <td class="text-right">10%</td><td class="text-right">$17.67</td>
                            </tr>
                            <tr class="border-b border-slate-800/50">
                                <td class="py-2 text-white font-medium">III</td>
                                <td>$895.25</td><td>$2,038.10</td>
                                <td class="text-right">20%</td><td class="text-right">$60.00</td>
                            </tr>
                            <tr>
                                <td class="py-2 text-white font-medium">IV</td>
                                <td>$2,038.11</td><td>en adelante</td>
                                <td class="text-right">30%</td><td class="text-right">$288.57</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Vista previa: planilla --}}
            <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-2xl">
                <p class="text-xs text-slate-500 uppercase tracking-wide mb-4">Planilla generada · Agosto 2026</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-slate-500 uppercase border-b border-slate-800">
                                <th class="text-left py-2">Empleado</th>
                                <th class="text-right">Base</th>
                                <th class="text-right">Renta</th>
                                <th class="text-center">Tramo</th>
                                <th class="text-right">Líquido</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-400">
                            @foreach ([
                                ['Ana Sofía Martínez', '365.00', '0.00', 'I', '342.52'],
                                ['Carlos José Hernández', '650.00', '30.29', 'II', '567.91'],
                                ['María José López', '1,200.00', '106.51', 'III', '1,021.29'],
                                ['Jorge Luis Menjívar', '2,500.00', '386.20', 'IV', '1,977.33'],
                                ['Rocío Portillo', '3,800.00', '763.64', 'IV', '2,858.04'],
                            ] as [$nombre, $base, $renta, $tramo, $liquido])
                                <tr class="border-b border-slate-800/50">
                                    <td class="py-2 text-slate-300">{{ $nombre }}</td>
                                    <td class="text-right">${{ $base }}</td>
                                    <td class="text-right text-red-400">-${{ $renta }}</td>
                                    <td class="text-center">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">{{ $tramo }}</span>
                                    </td>
                                    <td class="text-right text-green-400 font-semibold">${{ $liquido }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-600 mt-4">
                    Cada recibo guarda la base imponible y el tramo aplicado, para que la retención sea auditable.
                </p>
            </div>
        </div>
    </section>

    {{-- ================= MÓDULOS ================= --}}
    <section id="modulos" class="max-w-6xl mx-auto px-6 py-20">
        <h2 class="text-2xl font-bold text-white mb-3">Qué incluye</h2>
        <p class="text-slate-400 mb-10 max-w-2xl">
            Cubre el ciclo laboral completo: desde que se contrata a una persona hasta que se liquida.
        </p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ([
                ['Empleados', 'Directorio con búsqueda y filtros, ficha individual, gafete imprimible con código QR y baja lógica que conserva el historial legal.'],
                ['Nómina', 'Cálculo masivo por período con ISSS, AFP, renta por tramos, horas extra y aportes patronales. Recibo en PDF por empleado.'],
                ['Asistencia', 'Kiosco público de marcaje por QR con control de tardanzas, cálculo de tiempo extraordinario y protección contra doble escaneo.'],
                ['Prestaciones', 'Aguinaldo por antigüedad con su parte exenta de renta, vacaciones con saldo de días y el 30% de recargo, y liquidación con indemnización.'],
                ['Roles y permisos', 'Tres niveles de acceso con permisos granulares. El empleado solo ve sus propios recibos y su propia asistencia.'],
                ['Exportaciones', 'Archivos CSV de planilla previsional para ISSS y AFP, e informe anual de retenciones de renta.'],
            ] as [$titulo, $desc])
                <div class="bg-slate-900 border border-slate-800 rounded-xl p-6 hover:border-slate-700 transition">
                    <h3 class="font-semibold text-white mb-2">{{ $titulo }}</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= INTEGRACIÓN ================= --}}
    <section class="border-y border-slate-800 bg-slate-900/30">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-2xl font-bold text-white mb-3">Los módulos se hablan entre sí</h2>
            <p class="text-slate-400 mb-8 max-w-2xl">
                El kiosco no es una isla. Al cerrar la jornada calcula el tiempo extraordinario, y esos
                minutos entran a la planilla del período como ingreso gravable que afecta la retención.
            </p>

            <div class="flex flex-wrap items-center gap-3 text-sm">
                @foreach ([
                    'Marcaje de salida', 'Minutos extra', 'Horas del período',
                    'Bonificación en planilla', 'Base imponible', 'Retención de renta',
                ] as $i => $paso)
                    @if ($i > 0)
                        <span class="text-slate-600">→</span>
                    @endif
                    <span class="px-3 py-2 bg-slate-900 border border-slate-800 rounded-lg text-slate-300">{{ $paso }}</span>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================= DEMO ================= --}}
    <section id="demo" class="max-w-6xl mx-auto px-6 py-20">
        <h2 class="text-2xl font-bold text-white mb-3">Pruébalo tú mismo</h2>
        <p class="text-slate-400 mb-10 max-w-2xl">
            Tres cuentas, un rol cada una. Entra con distintas y vas a ver cómo cambia
            lo que el sistema permite hacer.
        </p>

        <div class="grid md:grid-cols-3 gap-5 mb-8">
            @foreach ([
                ['Administrador', 'admin@rrhh.test', 'Control total, incluida la gestión de cuentas y roles.', 'border-purple-500'],
                ['Recursos Humanos', 'rrhh@rrhh.test', 'Empleados, planillas, asistencia y prestaciones. No administra usuarios.', 'border-indigo-500'],
                ['Empleado', 'empleado@rrhh.test', 'Solo autoservicio: sus recibos, su asistencia y sus prestaciones.', 'border-slate-600'],
            ] as [$rol, $correo, $desc, $borde])
                <div class="bg-slate-900 border border-slate-800 border-l-4 {{ $borde }} rounded-xl p-6">
                    <h3 class="font-semibold text-white mb-1">{{ $rol }}</h3>
                    <p class="text-sm text-slate-400 mb-4 leading-relaxed">{{ $desc }}</p>
                    <dl class="text-sm space-y-1">
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">Correo</dt>
                            <dd class="text-slate-300 font-mono text-xs">{{ $correo }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500">Contraseña</dt>
                            <dd class="text-slate-300 font-mono text-xs">password</dd>
                        </div>
                    </dl>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap gap-3">
            <a href="{{ route('login') }}" class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-500 transition">
                Iniciar sesión
            </a>
            <a href="{{ route('asistencias.kiosco') }}" target="_blank"
               class="px-6 py-3 border border-slate-700 text-slate-200 rounded-lg font-semibold hover:border-slate-500 hover:text-white transition">
                Abrir el kiosco ↗
            </a>
        </div>

        <div class="mt-8 bg-amber-500/5 border border-amber-500/20 rounded-xl p-5 text-sm">
            <p class="text-amber-200/90 font-medium mb-1">Sobre los datos</p>
            <p class="text-slate-400 leading-relaxed">
                Todos los empleados, salarios y marcajes son ficticios, generados para la demostración.
                En el kiosco puedes probar con el DUI <span class="font-mono text-slate-300">04567890-1</span>.
                Los porcentajes y tramos están parametrizados en configuración y deben verificarse contra
                la normativa vigente antes de usarse con datos reales.
            </p>
        </div>
    </section>

    {{-- ================= STACK ================= --}}
    <footer class="border-t border-slate-800">
        <div class="max-w-6xl mx-auto px-6 py-12">
            <div class="flex flex-wrap gap-x-10 gap-y-6 justify-between">
                <div>
                    <p class="text-xs text-slate-500 uppercase tracking-wide mb-3">Construido con</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['Laravel 12', 'PHP 8.2', 'MySQL', 'Tailwind CSS', 'Blade', 'Chart.js', 'spatie/laravel-permission', 'DomPDF'] as $tec)
                            <span class="px-3 py-1 bg-slate-900 border border-slate-800 rounded-lg text-xs text-slate-400">{{ $tec }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="text-sm">
                    <p class="text-xs text-slate-500 uppercase tracking-wide mb-3">Código</p>
                    <a href="https://github.com/cxrlxsc/rrhh-sistema" target="_blank" rel="noopener"
                       class="text-indigo-400 hover:text-indigo-300 transition">
                        github.com/cxrlxsc/rrhh-sistema ↗
                    </a>
                </div>
            </div>

            <p class="text-xs text-slate-600 mt-10">
                Sistema RRHH · Proyecto de portafolio · {{ date('Y') }}
            </p>
        </div>
    </footer>

</body>
</html>
