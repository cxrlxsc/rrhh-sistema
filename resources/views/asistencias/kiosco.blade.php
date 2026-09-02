<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Marcaje | Kiosco RRHH</title>
    <!-- Tailwind CSS (CDN para que el kiosco funcione aunque no se compilen assets) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex flex-col items-center justify-center relative overflow-hidden"
      onclick="document.getElementById('inputDUI').focus()">

    <!-- Reloj en Tiempo Real -->
    <div class="absolute top-10 text-center">
        <h2 class="text-4xl font-bold text-gray-700" id="reloj">00:00:00</h2>
        <p class="text-gray-500 text-lg" id="fechaActual">Cargando fecha...</p>
    </div>

    <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-lg text-center border-t-8 border-indigo-600">

        <!-- Icono Escáner -->
        <div class="mx-auto bg-indigo-50 w-24 h-24 rounded-full flex items-center justify-center mb-6 shadow-inner">
            <svg class="w-12 h-12 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-black text-gray-800 mb-2 uppercase tracking-wide">Punto de Marcaje</h1>
        <p class="text-gray-500 mb-8">Escanea tu gafete (QR) para registrar entrada o salida</p>

        <form action="{{ route('asistencias.marcar') }}" method="POST" id="formMarcaje">
            @csrf
            {{-- Si el kiosco está protegido con token, se reenvía en cada marcaje --}}
            @if (request('token'))
                <input type="hidden" name="token" value="{{ request('token') }}">
            @endif

            <input type="text" name="dui" id="inputDUI"
                   class="w-full text-center p-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition mb-4 opacity-5"
                   placeholder="Esperando escáner..." autofocus autocomplete="off">
            <button type="submit" class="hidden">Marcar</button>
        </form>

        <!-- Respuesta del marcaje -->
        @if (session('success'))
            <div class="mt-4 p-4 rounded-lg font-bold text-white shadow-lg
                {{ session('tipo') === 'entrada' ? 'bg-green-500' : 'bg-blue-500' }}">
                <p class="text-lg leading-snug">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Doble escaneo / jornada demasiado corta: se avisa sin corromper el registro --}}
        @if (session('warning'))
            <div class="mt-4 p-4 rounded-lg bg-yellow-400 text-yellow-900 font-bold shadow-lg">
                <p class="leading-snug">{{ session('warning') }}</p>
            </div>
        @endif

        @if (session('error') || $errors->any())
            <div class="mt-4 p-4 rounded-lg bg-red-500 text-white font-bold shadow-lg">
                <p class="leading-snug">{{ session('error') ?? $errors->first() }}</p>
            </div>
        @endif

    </div>

    <p class="absolute bottom-5 text-gray-400 text-sm">
        {{ config('nomina.empresa.nombre') }} · Sistema RRHH
    </p>

    <script>
        const input = document.getElementById('inputDUI');
        input.focus();

        // Reloj en vivo
        function actualizarReloj() {
            const ahora = new Date();
            document.getElementById('reloj').innerText = ahora.toLocaleTimeString('es-SV');
            document.getElementById('fechaActual').innerText =
                ahora.toLocaleDateString('es-SV', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        setInterval(actualizarReloj, 1000);
        actualizarReloj();

        // ANTI-SPAM EN EL CLIENTE (primera línea de defensa):
        // el lector de QR dispara "Enter" y algunos modelos lo hacen dos veces.
        // Aquí se bloquea el segundo envío del formulario; el servidor aplica
        // además su propio cooldown, que es el que realmente protege los datos.
        let enviando = false;
        document.getElementById('formMarcaje').addEventListener('submit', function (e) {
            if (enviando || input.value.trim() === '') {
                e.preventDefault();
                return;
            }
            enviando = true;
            input.readOnly = true;
        });

        // Limpiar la pantalla para el siguiente empleado
        @if (session('success') || session('error') || session('warning') || $errors->any())
            setTimeout(() => {
                window.location.href = "{{ route('asistencias.kiosco', request('token') ? ['token' => request('token')] : []) }}";
            }, {{ config('asistencia.segundos_mensaje', 4) * 1000 }});
        @endif
    </script>
</body>
</html>
