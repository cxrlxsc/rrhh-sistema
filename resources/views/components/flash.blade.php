{{-- Mensajes de sesión reutilizables: <x-flash /> en cualquier vista. --}}

@if (session('success'))
    <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 mb-6 rounded shadow-sm" role="alert">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-6 rounded shadow-sm" role="alert">
        {{ session('error') }}
    </div>
@endif

@if (session('warning'))
    <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 rounded shadow-sm" role="alert">
        {{ session('warning') }}
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-6 rounded shadow-sm" role="alert">
        <p class="font-semibold mb-1">Revisa los siguientes datos:</p>
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
