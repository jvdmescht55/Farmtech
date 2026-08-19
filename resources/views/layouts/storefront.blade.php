<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Farmtech — South African AgriTech')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-farmtech-cream text-gray-900 antialiased flex flex-col min-h-screen">

    <header class="bg-farmtech-green-dark text-white">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-xl font-bold tracking-tight">
                Farm<span class="text-farmtech-gold">tech</span>
            </a>
            <nav class="hidden md:flex gap-6 text-sm font-medium">
                <a href="{{ route('category.show', 'scales') }}" class="hover:text-farmtech-gold">Livestock Scales</a>
                <a href="{{ route('category.show', 'ultrasound') }}" class="hover:text-farmtech-gold">Ultrasound Scanners</a>
                <a href="{{ route('category.show', 'rfid') }}" class="hover:text-farmtech-gold">RFID &amp; Ear Tagging</a>
                <a href="{{ route('category.show', 'accessories') }}" class="hover:text-farmtech-gold">Probes &amp; Accessories</a>
            </nav>
            <a href="{{ route('cart.index') }}" class="flex items-center gap-1 text-sm font-semibold hover:text-farmtech-gold">
                Cart
            </a>
        </div>
    </header>

    <div class="bg-farmtech-gold/20 border-b border-farmtech-gold text-farmtech-green-dark text-sm text-center py-2 px-4">
        Direct Express Delivery (7–12 Business Days) — All Customs &amp; Duties Handled
    </div>

    @if (session('status'))
        <div class="max-w-6xl mx-auto w-full mt-4 px-4">
            <div class="bg-green-100 border border-green-300 text-green-800 rounded-md px-4 py-3 text-sm">
                {{ session('status') }}
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="max-w-6xl mx-auto w-full mt-4 px-4">
            <div class="bg-red-100 border border-red-300 text-red-800 rounded-md px-4 py-3 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <main class="flex-1 max-w-6xl mx-auto w-full px-4 py-8">
        @yield('content')
    </main>

    <footer class="bg-farmtech-green-dark text-white/70 text-sm mt-12">
        <div class="max-w-6xl mx-auto px-4 py-8 flex flex-col md:flex-row justify-between gap-4">
            <p>&copy; {{ date('Y') }} Farmtech. All prices in ZAR, inclusive of 15% VAT.</p>
            <p>Direct Express Delivery — Customs &amp; Duties Handled</p>
        </div>
    </footer>
</body>
</html>
