<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin — Farmtech')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <div class="flex min-h-screen">
        <aside class="w-56 bg-farmtech-green-dark text-white flex-shrink-0">
            <div class="px-4 py-4 text-lg font-bold border-b border-white/10">
                Farm<span class="text-farmtech-gold">tech</span> Admin
            </div>
            @can('manage-catalog')
                <div class="px-3 pt-4">
                    <a href="{{ route('admin.source.create') }}" class="flex items-center justify-center gap-2 w-full bg-farmtech-gold hover:brightness-110 text-white text-sm font-semibold px-3 py-2.5 rounded-md transition">
                        + Source New Listing
                    </a>
                </div>
            @endcan
            <nav class="px-2 py-4 text-sm space-y-1">
                <div class="px-3 pt-1 pb-1 text-[10px] uppercase tracking-widest text-white/40">RFID platform</div>
                @can('manage-users')
                    <a href="{{ route('admin.licenses.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.licenses.*') ? 'bg-white/10 font-semibold' : '' }}">Licences &amp; Customers</a>
                @endcan
                <a href="{{ route('admin.leads.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.leads.*') ? 'bg-white/10 font-semibold' : '' }}">Leads @php($openLeads = \App\Models\Lead::whereNull('handled_at')->count())@if($openLeads)<span class="ml-1 rounded-full bg-farmtech-gold px-1.5 text-[10px]">{{ $openLeads }}</span>@endif</a>
                <a href="{{ route('rfid.dashboard') }}" class="block px-3 py-2 rounded hover:bg-white/10">Open Kuddebestuur ↗</a>
                <a href="{{ route('portal') }}" target="_blank" class="block px-3 py-2 rounded hover:bg-white/10">View public site ↗</a>
                <div class="px-3 pt-4 pb-1 text-[10px] uppercase tracking-widest text-white/40">Store</div>
                <a href="{{ route('home') }}" target="_blank" class="block px-3 py-2 rounded hover:bg-white/10">Product store (preview) ↗</a>
                @can('view-financials')
                    <a href="{{ route('admin.insights') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.insights') ? 'bg-white/10 font-semibold' : '' }}">★ Insights</a>
                    <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 font-semibold' : '' }}">Dashboard</a>
                @endcan
                @can('manage-catalog')
                    <a href="{{ route('admin.products.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.products.index') || request()->routeIs('admin.products.show') ? 'bg-white/10 font-semibold' : '' }}">Staging Queue</a>
                    <a href="{{ route('admin.products.live') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.products.live') ? 'bg-white/10 font-semibold' : '' }}">Live Products</a>
                    <a href="{{ route('admin.suppliers.outreach') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.suppliers.outreach') ? 'bg-white/10 font-semibold' : '' }}">Supplier Outreach</a>
                    <a href="{{ route('admin.outreach.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.outreach.*') ? 'bg-white/10 font-semibold' : '' }}">Video Outreach</a>
                @endcan
                <a href="{{ route('admin.orders.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.orders.*') ? 'bg-white/10 font-semibold' : '' }}">Orders</a>
                @can('manage-users')
                    <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.users.*') ? 'bg-white/10 font-semibold' : '' }}">Users</a>
                @endcan
                @can('manage-settings')
                    <a href="{{ route('admin.settings.edit') }}" class="block px-3 py-2 rounded hover:bg-white/10 {{ request()->routeIs('admin.settings.*') ? 'bg-white/10 font-semibold' : '' }}">Settings</a>
                @endcan
            </nav>
            <form action="{{ route('admin.logout') }}" method="POST" class="px-4 py-4 border-t border-white/10 mt-auto">
                @csrf
                <button type="submit" class="text-sm text-white/70 hover:text-white">Log out</button>
            </form>
        </aside>

        <div class="flex-1 flex flex-col">
            <header class="bg-white border-b px-6 py-4 flex items-center justify-between">
                <h1 class="text-lg font-semibold">@yield('heading', 'Dashboard')</h1>
                @auth
                    <a href="{{ route('admin.profile.edit') }}" class="text-sm text-gray-500 hover:text-farmtech-green transition">{{ auth()->user()->name }}</a>
                @endauth
            </header>

            <main class="flex-1 p-6">
                @if (session('status'))
                    <div class="mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md px-4 py-3 text-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if (isset($errors) && $errors->any())
                    <div class="mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md px-4 py-3 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
