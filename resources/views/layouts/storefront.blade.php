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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-charcoal antialiased flex flex-col min-h-screen font-body">

    {{-- Utility bar — slides down once per tab session on first load --}}
    <div :class="$store.intro.alreadyPlayed ? '' : 'animate-slide-down-in'"
         class="bg-brand-900 text-white/90 text-[11px] sm:text-xs text-center py-2 px-4 font-medium tracking-wide">
        <span class="hidden sm:inline">🇿🇦 South African delivery &amp; support&nbsp;·&nbsp;Prices in ZAR&nbsp;·&nbsp;VAT included&nbsp;·&nbsp;</span><a href="{{ route('track.index') }}" class="underline decoration-white/30 hover:decoration-white transition">Track order</a>
    </div>

    <header
        x-data="{
            categoriesOpen: false,
            supportOpen: false,
            q: '{{ addslashes(request('q', '')) }}',
            products: [], categories: [], applications: [],
            loading: false,
            get hasResults() { return this.products.length + this.categories.length + this.applications.length > 0; },
            async suggest() {
                if (this.q.length < 2) { this.products = []; this.categories = []; this.applications = []; return; }
                this.loading = true;
                try {
                    const res = await fetch('{{ route('search.suggest') }}?q=' + encodeURIComponent(this.q));
                    const data = await res.json();
                    this.products = data.products; this.categories = data.categories; this.applications = data.applications;
                } finally {
                    this.loading = false;
                }
            },
        }"
        class="glass-header sticky top-0 z-40 bg-white md:h-[76px] md:flex md:items-center">
        <div class="max-w-7xl mx-auto px-4 w-full">
            <div class="flex items-center gap-2 py-3 md:py-0">
                <a href="{{ route('home') }}" class="font-display font-extrabold text-xl tracking-tight text-brand-900 flex-shrink-0 mr-2">
                    Farm<span class="text-mint">tech</span>
                </a>

                <div class="relative hidden md:block">
                    <button type="button" @click="categoriesOpen = !categoriesOpen" @click.outside="categoriesOpen = false"
                            class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-slate-100 transition">
                        Equipment
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" :class="categoriesOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="categoriesOpen" x-transition x-cloak
                         class="absolute left-0 top-full mt-2 w-[640px] max-w-[90vw] card !rounded-xl shadow-xl p-4 grid grid-cols-2 sm:grid-cols-4 gap-x-2 gap-y-1">
                        @foreach (\App\Enums\Industry::cases() as $navIndustry)
                            <div>
                                <a href="{{ route('industry.show', $navIndustry) }}" @click="categoriesOpen = false"
                                   class="block px-3 pt-1 pb-2 text-[10px] font-mono uppercase tracking-widest text-slate-400 hover:text-mint-dark transition">{{ $navIndustry->label() }}</a>
                                @foreach ($navIndustry->categories() as $navCategory)
                                    <a href="{{ route('category.show', $navCategory) }}" @click="categoriesOpen = false"
                                       class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-mint/10 text-slate-700 hover:text-brand-900 transition group">
                                        <span class="w-7 h-7 rounded-lg bg-brand-900/5 text-brand-900 flex items-center justify-center group-hover:bg-mint/15 group-hover:text-mint-dark transition flex-shrink-0">
                                            <x-category-icon :icon="$navCategory->icon()" class="w-3.5 h-3.5" />
                                        </span>
                                        <span class="text-[13px] font-medium leading-tight">{{ $navCategory->shortLabel() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <a href="{{ route('home') }}#shop-by-application" class="hidden md:inline-block text-sm font-semibold text-slate-700 hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-slate-100 transition">Solutions</a>
                <a href="{{ route('how-it-works') }}" class="hidden md:inline-block text-sm font-semibold text-slate-700 hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-slate-100 transition">How it works</a>

                <div class="relative hidden md:block">
                    <button type="button" @click="supportOpen = !supportOpen" @click.outside="supportOpen = false"
                            class="flex items-center gap-1.5 text-sm font-semibold text-slate-700 hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-slate-100 transition">
                        Support
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" :class="supportOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="supportOpen" x-transition x-cloak
                         class="absolute left-0 top-full mt-2 w-56 card !rounded-xl shadow-xl p-1.5">
                        <a href="{{ route('track.index') }}" class="block px-3 py-2.5 rounded-lg hover:bg-mint/10 text-sm text-slate-700 hover:text-brand-900 transition">Track your order</a>
                        <a href="mailto:support@farmtech.co.za" class="block px-3 py-2.5 rounded-lg hover:bg-mint/10 text-sm text-slate-700 hover:text-brand-900 transition">Email support</a>
                        @if ($whatsappUrl ?? null)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="block px-3 py-2.5 rounded-lg hover:bg-mint/10 text-sm text-slate-700 hover:text-brand-900 transition">WhatsApp us</a>
                        @endif
                    </div>
                </div>

                {{-- Large, centered search — see item 5/7 of the spec --}}
                <div class="flex-1 max-w-2xl mx-auto relative hidden sm:block" @click.outside="products = []; categories = []; applications = []">
                    <form action="{{ route('search.index') }}" method="GET">
                        <div class="relative w-full">
                            <input type="search" name="q" x-model="q" @input.debounce.300ms="suggest()" autocomplete="off"
                                   placeholder="Search equipment, models &amp; specs…"
                                   class="w-full border border-border bg-white rounded-full pl-5 pr-11 py-2.5 text-sm text-center focus:text-left focus:outline-none focus:ring-2 focus:ring-mint/40 focus:border-mint transition">
                            <button type="submit" aria-label="Search" class="absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center text-slate-500 hover:text-mint-dark hover:bg-mint/10 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </button>
                        </div>
                    </form>

                    {{-- Grouped dropdown: Products / Categories / Applications --}}
                    <div x-show="hasResults" x-transition x-cloak
                         class="absolute left-0 right-0 top-full mt-2 card !rounded-xl shadow-xl overflow-hidden max-h-[70vh] overflow-y-auto">
                        <template x-if="products.length > 0">
                            <div class="py-2">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-mono uppercase tracking-widest text-slate-400">Products</p>
                                <template x-for="item in products" :key="item.url">
                                    <a :href="item.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mint/5 transition">
                                        <img :src="item.image" x-show="item.image" x-on:error="item.image = null" class="w-9 h-9 rounded-lg object-cover bg-slate-100 flex-shrink-0" alt="">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-800 truncate" x-text="item.title"></p>
                                            <p class="text-xs text-slate-500" x-text="item.category + ' · R' + item.price"></p>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <template x-if="categories.length > 0">
                            <div class="py-2 border-t border-border">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-mono uppercase tracking-widest text-slate-400">Categories</p>
                                <template x-for="item in categories" :key="item.url">
                                    <a :href="item.url" class="block px-4 py-2 text-sm text-slate-700 hover:bg-mint/5 hover:text-brand-900 transition" x-text="item.label"></a>
                                </template>
                            </div>
                        </template>
                        <template x-if="applications.length > 0">
                            <div class="py-2 border-t border-border">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-mono uppercase tracking-widest text-slate-400">Applications</p>
                                <template x-for="item in applications" :key="item.url">
                                    <a :href="item.url" class="block px-4 py-2 text-sm text-slate-700 hover:bg-mint/5 hover:text-brand-900 transition" x-text="item.label"></a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <a href="{{ route('cart.index') }}" class="relative flex-shrink-0 flex items-center gap-1.5 text-sm font-semibold text-brand-900 hover:text-mint-dark transition group ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2l2.2 11.4a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 7H6"/></svg>
                    @if (($cartCount ?? 0) > 0)
                        <span class="absolute -top-2 -right-2 bg-mint text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 {{ session('status') ? 'animate-pop-in' : '' }}">{{ $cartCount }}</span>
                    @endif
                    <span class="hidden md:inline">Cart</span>
                </a>
            </div>

            <form action="{{ route('search.index') }}" method="GET" class="pb-3 sm:hidden">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search equipment, models &amp; specs…"
                       class="w-full border border-border bg-white rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-mint/40">
            </form>

            <nav class="flex md:hidden items-center gap-5 text-sm font-semibold pb-3 -mt-1 overflow-x-auto">
                @foreach (\App\Enums\Industry::cases() as $navIndustry)
                    @foreach ($navIndustry->categories() as $navCategory)
                        <a href="{{ route('category.show', $navCategory) }}" class="text-slate-600 hover:text-mint-dark transition whitespace-nowrap">{{ $navCategory->shortLabel() }}</a>
                    @endforeach
                    @if (!$loop->last)
                        <span class="text-slate-300 flex-shrink-0" aria-hidden="true">|</span>
                    @endif
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

    <x-footer />

    {{-- Shared "Farmtech Verified" explanation modal — one instance, triggered from any product card. --}}
    <div x-data x-show="$store.verifiedModal.open" x-cloak x-transition.opacity
         @keydown.escape.window="$store.verifiedModal.open = false"
         class="fixed inset-0 z-50 bg-brand-950/60 flex items-center justify-center p-4" @click="$store.verifiedModal.open = false">
        <div @click.stop class="card !rounded-xl max-w-sm w-full p-6" x-transition.scale.origin.center>
            <div class="flex items-start justify-between gap-3 mb-3">
                <h3 class="font-display font-bold text-lg text-charcoal">What does Farmtech Verified mean?</h3>
                <button type="button" @click="$store.verifiedModal.open = false" aria-label="Close" class="flex-shrink-0 text-slate-400 hover:text-charcoal transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <ul class="space-y-2 text-sm text-ink-secondary">
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Supplier reviewed</li>
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Product information checked</li>
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Specifications reviewed</li>
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Import requirements assessed where applicable</li>
            </ul>
            <p class="text-xs text-slate-400 mt-4 pt-4 border-t border-border">AI-assisted supplier and product verification, with human review before anything is approved for sale.</p>
        </div>
    </div>
</body>
</html>
