<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Farmtech — South African AgriTech')</title>
    <meta name="description" content="@yield('meta_description', 'AI-vetted agricultural technology, imported and priced for South African farms — all-in pricing, no surprise customs bill.')">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased flex flex-col min-h-screen font-body">

    {{-- Rotating honest-claims strip --}}
    <div x-data="{ i: 0, msgs: [
            'All prices include SA import duty &amp; 15% VAT — nothing extra on delivery',
            'Direct Express Delivery, 7–12 business days, tracked door to door',
            'Every listing is AI-checked against ISO 11784/11785, ICASA &amp; SARS requirements',
        ] }"
         x-init="setInterval(() => i = (i + 1) % msgs.length, 4500)"
         class="bg-brand-900 text-white text-xs sm:text-sm text-center py-2 px-4 font-medium tracking-wide">
        <span x-text="msgs[i]" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"></span>
    </div>

    <header
        x-data="{
            scrolled: false,
            categoriesOpen: false,
            searchOpen: false,
            q: '{{ addslashes(request('q', '')) }}',
            results: [],
            loading: false,
            async suggest() {
                if (this.q.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const res = await fetch('{{ route('search.suggest') }}?q=' + encodeURIComponent(this.q));
                    const data = await res.json();
                    this.results = data.results;
                } finally {
                    this.loading = false;
                }
            },
        }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 12; }, { passive: true })"
        :class="scrolled ? 'bg-white/90 border-slate-200/80 shadow-sm' : 'bg-white/60 border-transparent'"
        class="glass-header sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center gap-4 py-3">
                <a href="{{ route('home') }}" class="font-display font-extrabold text-xl tracking-tight text-brand-900 flex-shrink-0">
                    Farm<span class="text-mint">tech</span>
                </a>

                <div class="relative hidden md:block">
                    <button type="button" @click="categoriesOpen = !categoriesOpen" @click.outside="categoriesOpen = false"
                            class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-slate-100 transition">
                        Categories
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" :class="categoriesOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="categoriesOpen" x-transition x-cloak
                         class="absolute left-0 top-full mt-2 w-72 glass-card bg-white/95 rounded-2xl shadow-xl p-2 grid gap-1">
                        @foreach (\App\Enums\ProductCategory::cases() as $navCategory)
                            <a href="{{ route('category.show', $navCategory) }}" @click="categoriesOpen = false"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-mint/10 text-slate-700 hover:text-brand-900 transition group">
                                <span class="w-9 h-9 rounded-lg bg-brand-900/5 text-brand-900 flex items-center justify-center group-hover:bg-mint/15 group-hover:text-mint-dark transition">
                                    <x-category-icon :icon="$navCategory->icon()" class="w-4.5 h-4.5" />
                                </span>
                                <span class="text-sm font-medium">{{ $navCategory->shortLabel() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="flex-1 max-w-xl relative hidden sm:block" @click.outside="results = []">
                    <form action="{{ route('search.index') }}" method="GET">
                        <div class="relative w-full">
                            <input type="search" name="q" x-model="q" @input.debounce.300ms="suggest()" autocomplete="off"
                                   placeholder="Search products, categories, specs…"
                                   class="w-full border border-slate-200 bg-white rounded-full pl-4 pr-11 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-mint/40 focus:border-mint transition">
                            <button type="submit" aria-label="Search" class="absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:text-mint-dark hover:bg-mint/10 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </button>
                        </div>
                    </form>

                    <div x-show="results.length > 0" x-transition x-cloak
                         class="absolute left-0 right-0 top-full mt-2 glass-card bg-white/95 rounded-2xl shadow-xl overflow-hidden divide-y divide-slate-100">
                        <template x-for="item in results" :key="item.url">
                            <a :href="item.url" class="flex items-center gap-3 px-4 py-3 hover:bg-mint/5 transition">
                                <img :src="item.image" x-show="item.image" class="w-10 h-10 rounded-lg object-cover bg-slate-100 flex-shrink-0" alt="">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800 truncate" x-text="item.title"></p>
                                    <p class="text-xs text-slate-500" x-text="item.category + ' · R' + item.price"></p>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                <a href="{{ route('cart.index') }}" class="relative flex-shrink-0 flex items-center gap-1.5 text-sm font-semibold text-brand-900 hover:text-mint-dark transition group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2l2.2 11.4a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 7H6"/></svg>
                    @if (($cartCount ?? 0) > 0)
                        <span class="absolute -top-2 -right-2 bg-mint text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 {{ session('status') ? 'animate-pop-in' : '' }}">{{ $cartCount }}</span>
                    @endif
                    <span class="hidden md:inline">Cart</span>
                </a>
            </div>

            <form action="{{ route('search.index') }}" method="GET" class="pb-3 sm:hidden">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search products…"
                       class="w-full border border-slate-200 bg-white rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-mint/40">
            </form>

            <nav class="flex md:hidden gap-5 text-sm font-semibold pb-3 -mt-1 overflow-x-auto">
                @foreach (\App\Enums\ProductCategory::cases() as $navCategory)
                    <a href="{{ route('category.show', $navCategory) }}" class="text-slate-600 hover:text-mint-dark transition whitespace-nowrap">{{ $navCategory->shortLabel() }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    {{-- Floating toast for cart/status feedback — driven by the real session flash, not simulated. --}}
    @if (session('status'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3200)"
             x-show="show" x-transition:enter="animate-toast-in" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0"
             class="fixed top-20 left-1/2 -translate-x-1/2 z-50 glass-card bg-brand-900/95 text-white rounded-full shadow-xl px-5 py-2.5 text-sm font-medium flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-mint-light flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="max-w-7xl mx-auto w-full mt-4 px-4">
            <div class="bg-red-50 border border-red-300 text-red-900 rounded-lg px-4 py-3 text-sm animate-reveal-up">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <footer class="bg-brand-950 text-slate-300 mt-20">
        <div class="max-w-7xl mx-auto px-4 py-14 grid gap-10 md:grid-cols-4">
            <div>
                <p class="font-display font-bold text-lg text-white mb-3">Farm<span class="text-mint">tech</span></p>
                <p class="text-sm leading-relaxed">AI-vetted agricultural technology, sourced from verified overseas suppliers and cleared for South African farms.</p>
            </div>
            <div>
                <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Shipping &amp; Import</p>
                <ul class="text-sm space-y-2 leading-relaxed">
                    <li>Direct Express air freight, 7–12 business days</li>
                    <li>SA import duty &amp; 15% VAT already included in price</li>
                    <li>Customs clearance handled for you — no paperwork, no surprise bill</li>
                </ul>
            </div>
            <div>
                <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Payments</p>
                <ul class="text-sm space-y-2 leading-relaxed">
                    <li>PayFast, Ozow (Instant EFT) &amp; Yoco accepted</li>
                    <li>Card details are handled by the payment provider — Farmtech never stores them</li>
                </ul>
            </div>
            <div>
                <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Get in Touch</p>
                <ul class="text-sm space-y-2 leading-relaxed">
                    <li><a href="mailto:support@farmtech.co.za" class="hover:text-mint-light transition">support@farmtech.co.za</a></li>
                    <li>Order queries answered within 1 business day</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-4 py-5 flex flex-col md:flex-row justify-between gap-2 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} Farmtech. All prices in ZAR, inclusive of 15% VAT and import duty.</p>
                <p>Every product page shows exactly what was checked before it was listed.</p>
            </div>
            <div class="max-w-7xl mx-auto px-4 pb-5">
                <details class="text-xs text-slate-500">
                    <summary class="cursor-pointer hover:text-slate-300 transition">Photo credits</summary>
                    <ul class="mt-2 grid sm:grid-cols-2 gap-x-6 gap-y-1">
                        @foreach (\App\Enums\ProductCategory::cases() as $creditCategory)
                            @php($img = $creditCategory->image())
                            <li>{{ $creditCategory->shortLabel() }}: <a href="{{ $img['source_url'] }}" target="_blank" rel="noopener" class="hover:text-mint-light transition">{{ $img['credit'] }}</a>, {{ $img['license'] }}</li>
                        @endforeach
                    </ul>
                </details>
            </div>
        </div>
    </footer>
</body>
</html>
