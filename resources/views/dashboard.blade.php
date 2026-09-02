<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Control de Recursos Humanos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-flash />

            <!-- Fila de Tarjetas (KPIs) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">

                <!-- Empleados activos -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex items-center p-6 border-l-4 border-indigo-500">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-500 mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Empleados activos</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $totalEmpleados }}</p>
                        <p class="text-xs text-gray-400">{{ $totalDepartamentos }} departamentos</p>
                    </div>
                </div>

                <!-- Nómina líquida -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex items-center p-6 border-l-4 border-green-500">
                    <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Nómina líquida ({{ $mesActual }})</p>
                        <p class="text-2xl font-bold text-gray-800">${{ number_format($gastoNomina, 2) }}</p>
                        <p class="text-xs text-gray-400">Renta retenida: ${{ number_format($rentaRetenida, 2) }}</p>
                    </div>
                </div>

                <!-- Costo patronal -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex items-center p-6 border-l-4 border-blue-500">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Costo real para la empresa</p>
                        <p class="text-2xl font-bold text-gray-800">${{ number_format($costoPatronal, 2) }}</p>
                        <p class="text-xs text-gray-400">Salarios + aportes patronales</p>
                    </div>
                </div>

                <!-- Asistencia de hoy -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-amber-500">
                    <p class="text-sm text-gray-500 font-medium">Asistencia de hoy</p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ $asistenciaHoy['presentes'] }}/{{ $totalEmpleados }}
                        <span class="text-sm font-normal text-gray-400">({{ $asistenciaHoy['porcentaje'] }}%)</span>
                    </p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: {{ $asistenciaHoy['porcentaje'] }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ $asistenciaHoy['tardanzas'] }} tardanzas · {{ $asistenciaHoy['ausentes'] }} sin marcar
                    </p>
                </div>
            </div>

            <!-- Sección de Gráficas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 text-center">Distribución de empleados</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="empleadosChart"></canvas>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 text-center">Nómina pagada (últimos 6 meses)</h3>
                    <div class="relative h-64 w-full">
                        <canvas id="nominaChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Script de Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new Chart(document.getElementById('empleadosChart'), {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($nombresDeptos) !!},
                    datasets: [{
                        label: 'Número de empleados',
                        data: {!! json_encode($conteosDeptos) !!},
                        backgroundColor: ['#4F46E5', '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            new Chart(document.getElementById('nominaChart'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($historicoEtiquetas) !!},
                    datasets: [{
                        label: 'Líquido pagado (USD)',
                        data: {!! json_encode($historicoTotales) !!},
                        backgroundColor: '#4F46E5',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: (valor) => '$' + valor.toLocaleString('en-US') }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>
