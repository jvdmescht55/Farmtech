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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-canvas text-charcoal antialiased flex flex-col min-h-screen font-body">

    {{-- Utility bar — slides down once per tab session on first load. Real trust
         signals about the direct-procurement model (see storefront.about),
         not a generic retail ribbon. --}}
    <div :class="$store.intro.alreadyPlayed ? '' : 'animate-slide-down-in'"
         class="bg-brand-900 text-white/90 text-[11px] sm:text-xs text-center py-2 px-4 font-medium tracking-wide">
        <span class="hidden sm:inline">🇿🇦 Field-Tested Hardware&nbsp;|&nbsp;Door-to-Farm Express Delivery&nbsp;|&nbsp;1-Year Local Replacement Warranty&nbsp;|&nbsp;@if ($whatsappUrl)<a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="underline decoration-white/30 hover:decoration-white transition">Direct WhatsApp Tech Support</a>@else Direct WhatsApp Tech Support @endif&nbsp;·&nbsp;</span><a href="{{ route('track.index') }}" class="underline decoration-white/30 hover:decoration-white transition">Track order</a>
    </div>

    <header
        x-data="{
            categoriesOpen: false,
            supportOpen: false,
            mobileMenuOpen: false,
            mobileAccordion: null,
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
        @keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key === 'k') { $event.preventDefault(); $refs.searchInput.focus(); }"
        class="glass-header sticky top-0 z-40 bg-white md:h-[76px] md:flex md:items-center">
        <div class="max-w-7xl mx-auto px-4 w-full">
            <div class="flex items-center gap-2 py-3 md:py-0">
                <button type="button" @click="mobileMenuOpen = true" aria-label="Open menu"
                        class="md:hidden flex-shrink-0 w-9 h-9 -ml-1.5 flex items-center justify-center text-charcoal rounded-lg hover:bg-canvas transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5.5 h-5.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>
                </button>

                <a href="{{ route('home') }}" class="font-display font-semibold text-xl tracking-tight text-brand-900 flex-shrink-0 mr-2">
                    Farm<span class="text-mint">tech</span>
                </a>

                <div class="relative hidden md:block">
                    <button type="button" @click="categoriesOpen = !categoriesOpen" @click.outside="categoriesOpen = false"
                            class="flex items-center gap-1.5 text-sm font-semibold text-charcoal hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-canvas transition">
                        Equipment
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" :class="categoriesOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="categoriesOpen" x-transition x-cloak
                         class="absolute left-0 top-full mt-2 w-[640px] max-w-[90vw] card !rounded-xl shadow-xl p-4 grid grid-cols-2 sm:grid-cols-4 gap-x-2 gap-y-1">
                        @foreach (\App\Enums\Industry::activeCases() as $navIndustry)
                            <div>
                                <a href="{{ route('domain.'.$navIndustry->domainSlug()) }}" @click="categoriesOpen = false"
                                   class="block px-3 pt-1 pb-2 text-[10px] font-mono uppercase tracking-widest text-ink-muted hover:text-mint-dark transition">{{ $navIndustry->label() }}</a>
                                @foreach ($navIndustry->categories() as $navCategory)
                                    <a href="{{ route('category.show', $navCategory) }}" @click="categoriesOpen = false"
                                       class="flex items-center gap-2.5 px-3 py-2 rounded-xl hover:bg-mint/10 text-charcoal hover:text-brand-900 transition group">
                                        <span class="w-7 h-7 rounded-lg bg-brand-900/5 text-brand-900 flex items-center justify-center group-hover:bg-mint/15 group-hover:text-mint-dark transition flex-shrink-0">
                                            <x-category-icon :icon="$navCategory->icon()" class="w-3.5 h-3.5" />
                                        </span>
                                        <span class="text-[13px] font-medium leading-tight">{{ $navCategory->shortLabel() }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endforeach
                        <div class="col-span-2 sm:col-span-4 border-t border-border mt-2 pt-2 flex items-center justify-between">
                            <a href="{{ route('equipment.index') }}" @click="categoriesOpen = false" class="text-sm font-semibold text-mint-dark hover:text-mint-darker transition">Browse full catalogue &rarr;</a>
                            <a href="{{ route('finder.index') }}" @click="categoriesOpen = false" class="text-sm font-semibold text-charcoal hover:text-brand-900 transition">Not sure? Try the Equipment Finder</a>
                        </div>
                    </div>
                </div>

                <a href="{{ route('home') }}#shop-by-application" class="hidden md:inline-block text-sm font-semibold text-charcoal hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-canvas transition border-b-2 border-transparent">Solutions</a>
                <a href="{{ route('login') }}" class="hidden md:inline-block text-sm font-semibold px-3 py-2 rounded-lg hover:bg-canvas transition text-mint-dark border-b-2 border-transparent">Herd Management</a>
                <a href="{{ route('how-it-works') }}" class="hidden md:inline-block text-sm font-semibold px-3 py-2 rounded-lg hover:bg-canvas transition border-b-2 {{ request()->routeIs('how-it-works') ? 'text-brand-900 border-mint' : 'text-charcoal hover:text-brand-900 border-transparent' }}">How it works</a>
                <a href="{{ route('about.index') }}" class="hidden md:inline-block text-sm font-semibold px-3 py-2 rounded-lg hover:bg-canvas transition border-b-2 {{ request()->routeIs('about.index') ? 'text-brand-900 border-mint' : 'text-charcoal hover:text-brand-900 border-transparent' }}">Our Promise</a>

                <div class="relative hidden md:block">
                    <button type="button" @click="supportOpen = !supportOpen" @click.outside="supportOpen = false"
                            class="flex items-center gap-1.5 text-sm font-semibold text-charcoal hover:text-brand-900 px-3 py-2 rounded-lg hover:bg-canvas transition">
                        Support
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" :class="supportOpen && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="supportOpen" x-transition x-cloak
                         class="absolute left-0 top-full mt-2 w-56 card !rounded-xl shadow-xl p-1.5">
                        <a href="{{ route('support.index') }}" class="block px-3 py-2.5 rounded-lg hover:bg-mint/10 text-sm text-charcoal hover:text-brand-900 transition">Help &amp; FAQ</a>
                        <a href="{{ route('track.index') }}" class="block px-3 py-2.5 rounded-lg hover:bg-mint/10 text-sm text-charcoal hover:text-brand-900 transition">Track your order</a>
                        <a href="mailto:support@farmtech.co.za" class="block px-3 py-2.5 rounded-lg hover:bg-mint/10 text-sm text-charcoal hover:text-brand-900 transition">Email support</a>
                        @if ($whatsappUrl ?? null)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="block px-3 py-2.5 rounded-lg hover:bg-mint/10 text-sm text-charcoal hover:text-brand-900 transition">WhatsApp us</a>
                        @endif
                    </div>
                </div>

                {{-- Large, centered search — see item 5/7 of the spec --}}
                <div class="flex-1 max-w-2xl mx-auto relative hidden sm:block" @click.outside="products = []; categories = []; applications = []">
                    <form action="{{ route('search.index') }}" method="GET">
                        <div class="relative w-full">
                            <input type="search" name="q" x-model="q" x-ref="searchInput" @input.debounce.300ms="suggest()" autocomplete="off"
                                   placeholder="Search equipment, models &amp; specs…"
                                   class="w-full border border-border bg-white rounded-full pl-5 pr-20 py-2.5 text-sm text-center focus:text-left focus:outline-none focus:ring-2 focus:ring-mint/40 focus:border-mint transition">
                            <kbd x-show="!q" x-cloak class="hidden lg:flex absolute right-11 top-1/2 -translate-y-1/2 items-center gap-0.5 text-[10px] font-mono text-ink-muted bg-canvas border border-border rounded px-1.5 py-0.5 pointer-events-none">Ctrl K</kbd>
                            <button type="submit" aria-label="Search" class="absolute right-1 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full flex items-center justify-center text-ink-secondary hover:text-mint-dark hover:bg-mint/10 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </button>
                        </div>
                    </form>

                    {{-- Grouped dropdown: Products / Categories / Applications --}}
                    <div x-show="hasResults" x-transition x-cloak
                         class="absolute left-0 right-0 top-full mt-2 card !rounded-xl shadow-xl overflow-hidden max-h-[70vh] overflow-y-auto">
                        <template x-if="products.length > 0">
                            <div class="py-2">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-mono uppercase tracking-widest text-ink-muted">Products</p>
                                <template x-for="item in products" :key="item.url">
                                    <a :href="item.url" class="flex items-center gap-3 px-4 py-2.5 hover:bg-mint/5 transition">
                                        <img :src="item.image" x-show="item.image" x-on:error="item.image = null" class="w-9 h-9 rounded-lg object-cover bg-canvas flex-shrink-0" alt="">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-charcoal truncate" x-text="item.title"></p>
                                            <p class="text-xs text-ink-secondary" x-text="item.category + ' · R' + item.price"></p>
                                        </div>
                                    </a>
                                </template>
                            </div>
                        </template>
                        <template x-if="categories.length > 0">
                            <div class="py-2 border-t border-border">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-mono uppercase tracking-widest text-ink-muted">Categories</p>
                                <template x-for="item in categories" :key="item.url">
                                    <a :href="item.url" class="block px-4 py-2 text-sm text-charcoal hover:bg-mint/5 hover:text-brand-900 transition" x-text="item.label"></a>
                                </template>
                            </div>
                        </template>
                        <template x-if="applications.length > 0">
                            <div class="py-2 border-t border-border">
                                <p class="px-4 pt-1 pb-1.5 text-[10px] font-mono uppercase tracking-widest text-ink-muted">Applications</p>
                                <template x-for="item in applications" :key="item.url">
                                    <a :href="item.url" class="block px-4 py-2 text-sm text-charcoal hover:bg-mint/5 hover:text-brand-900 transition" x-text="item.label"></a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <span class="hidden lg:inline-flex items-center gap-1 text-xs font-medium text-ink-muted flex-shrink-0 ml-2" title="Farmtech ships within South Africa, priced in South African Rand">🇿🇦 ZAR (R)</span>

                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                       class="hidden lg:inline-flex items-center gap-1.5 flex-shrink-0 ml-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-3.5 py-2 rounded-full transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
                        Talk to a Technical Specialist
                    </a>
                @endif

                <button type="button" x-data @click="$store.cart.open = true"
                        class="relative flex-shrink-0 flex items-center gap-1.5 text-sm font-semibold text-brand-900 hover:text-mint-dark transition group ml-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2l2.2 11.4a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 2-1.6L21 7H6"/></svg>
                    <span x-show="$store.cart.count > 0" x-cloak
                          class="absolute -top-2 -right-2 bg-mint text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"
                          x-text="$store.cart.count"></span>
                    <span class="hidden md:inline">Cart</span>
                </button>
            </div>

            <form action="{{ route('search.index') }}" method="GET" class="pb-3 sm:hidden">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search equipment, models &amp; specs…"
                       class="w-full border border-border bg-white rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-mint/40">
            </form>

        </div>

        {{-- Mobile off-canvas menu — replaces the old horizontal category-scroll strip with
             room to breathe: same real Industry/ProductCategory data as the desktop mega-menu,
             as an accordion rather than a 4-column grid. --}}
        <div x-show="mobileMenuOpen" x-cloak x-transition.opacity
             @keydown.escape.window="mobileMenuOpen = false"
             class="md:hidden fixed inset-0 z-50 bg-brand-950/60" @click="mobileMenuOpen = false">
            <div @click.stop x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 class="fixed inset-y-0 left-0 z-50 w-[86vw] max-w-sm bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-border flex-shrink-0">
                    <span class="font-display font-semibold text-lg text-brand-900">Farm<span class="text-mint">tech</span></span>
                    <button type="button" @click="mobileMenuOpen = false" aria-label="Close menu" class="w-8 h-8 rounded-full flex items-center justify-center text-ink-secondary hover:text-charcoal hover:bg-canvas transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto py-2">
                    @foreach (\App\Enums\Industry::activeCases() as $navIndustry)
                        <div class="border-b border-border">
                            <button type="button" @click="mobileAccordion = mobileAccordion === '{{ $navIndustry->value }}' ? null : '{{ $navIndustry->value }}'"
                                    class="w-full flex items-center justify-between px-5 py-3.5 text-left">
                                <span class="text-sm font-semibold text-charcoal">{{ $navIndustry->label() }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-ink-muted transition-transform flex-shrink-0" :class="mobileAccordion === '{{ $navIndustry->value }}' && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div x-show="mobileAccordion === '{{ $navIndustry->value }}'" x-transition x-cloak class="pb-2">
                                @foreach ($navIndustry->categories() as $navCategory)
                                    <a href="{{ route('category.show', $navCategory) }}" @click="mobileMenuOpen = false"
                                       class="flex items-center gap-3 px-5 py-2.5 text-sm text-ink-secondary hover:text-brand-900 hover:bg-canvas transition">
                                        <span class="w-6 h-6 rounded-lg bg-brand-900/5 text-brand-900 flex items-center justify-center flex-shrink-0">
                                            <x-category-icon :icon="$navCategory->icon()" class="w-3 h-3" />
                                        </span>
                                        {{ $navCategory->shortLabel() }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <a href="{{ route('equipment.index') }}" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-mint-dark border-b border-border">Browse full catalogue</a>
                    <a href="{{ route('finder.index') }}" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-charcoal border-b border-border">Equipment Finder</a>
                    <a href="{{ route('home') }}#shop-by-application" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-charcoal border-b border-border">Solutions</a>
                    <a href="{{ route('login') }}" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-mint-dark border-b border-border">Herd Management</a>
                    <a href="{{ route('how-it-works') }}" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-charcoal border-b border-border">How it works</a>
                    <a href="{{ route('about.index') }}" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-charcoal border-b border-border">Our Promise</a>
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" @click="mobileMenuOpen = false" class="flex items-center gap-2 px-5 py-3.5 text-sm font-semibold text-emerald-700 border-b border-border">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
                            Talk to a Technical Specialist
                        </a>
                    @endif
                    <a href="{{ route('support.index') }}" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-charcoal border-b border-border">Help &amp; FAQ</a>
                    <a href="{{ route('track.index') }}" @click="mobileMenuOpen = false" class="block px-5 py-3.5 text-sm font-semibold text-charcoal border-b border-border">Track your order</a>
                </div>
            </div>
        </div>
    </header>

    {{-- Cart bootstrap — hydrates $store.cart on first paint with the real session cart, no extra fetch. --}}
    <script type="application/json" id="cart-bootstrap">{!! json_encode($cartSummary, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script>

    {{-- Floating toast stack — session-flash statuses (full-page redirects like checkout) feed the same
         $store.toast queue that AJAX cart actions use, so there's exactly one toast implementation. --}}
    @if (session('status'))
        <div x-data x-init="$store.toast.push(@js(session('status')))"></div>
    @endif
    <div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 flex flex-col items-center gap-2 pointer-events-none" x-data>
        <template x-for="toast in $store.toast.items" :key="toast.id">
            <div x-transition:enter="animate-toast-in" x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0"
                 class="rounded-full shadow-xl px-5 py-2.5 text-sm font-medium flex items-center gap-2 pointer-events-auto"
                 :class="toast.tone === 'error' ? 'bg-error text-white' : 'bg-brand-900/95 text-white'">
                <svg x-show="toast.tone !== 'error'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-mint-light flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <svg x-show="toast.tone === 'error'" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

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
                <button type="button" @click="$store.verifiedModal.open = false" aria-label="Close" class="flex-shrink-0 text-ink-muted hover:text-charcoal transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <ul class="space-y-2 text-sm text-ink-secondary">
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Supplier reviewed</li>
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Product information checked</li>
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Specifications reviewed</li>
                <li class="flex gap-2"><span class="text-mint-dark flex-shrink-0">✓</span> Import requirements assessed where applicable</li>
            </ul>
            <p class="text-xs text-ink-muted mt-4 pt-4 border-t border-border">AI-assisted supplier and product verification, with human review before anything is approved for sale.</p>
        </div>
    </div>

    {{-- Quick View — one shared modal, opened from any catalog card with its own already-rendered data. --}}
    <div x-data x-show="$store.quickView.open" x-cloak x-transition.opacity
         @keydown.escape.window="$store.quickView.close()"
         class="fixed inset-0 z-50 bg-brand-950/60 flex items-center justify-center p-4" @click="$store.quickView.close()">
        <template x-if="$store.quickView.product">
            <div @click.stop class="card !rounded-xl max-w-lg w-full overflow-hidden" x-transition.scale.origin.center>
                <div class="relative aspect-[16/10] bg-canvas">
                    <img :src="$store.quickView.product.image" x-show="$store.quickView.product.image"
                         x-on:error="$store.quickView.product.image = null" class="w-full h-full object-contain p-6" alt="">
                    <div x-show="!$store.quickView.product.image" class="absolute inset-0 flex items-center justify-center text-ink-muted text-sm">
                        No image available
                    </div>
                    <button type="button" @click="$store.quickView.close()" aria-label="Close"
                            class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white border border-border flex items-center justify-center text-ink-secondary hover:text-charcoal transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="p-5">
                    <span class="inline-block text-[10px] font-semibold uppercase tracking-wide text-mint-dark bg-mint/10 rounded px-2 py-0.5" x-text="$store.quickView.product.category"></span>
                    <p class="font-display font-bold text-lg text-charcoal mt-2" x-text="$store.quickView.product.title"></p>
                    <p x-show="$store.quickView.product.keySpec" class="text-sm font-mono text-ink-secondary mt-1" x-text="$store.quickView.product.keySpec"></p>
                    <p class="font-mono text-2xl font-bold text-charcoal mt-3" x-text="'R' + $store.quickView.product.price"></p>
                    <p class="text-xs text-ink-muted">VAT included &middot; Delivered to South Africa</p>
                    <div class="mt-4 flex gap-2">
                        <a :href="$store.quickView.product.url" class="flex-1 text-center bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-2.5 rounded-full transition">View full details</a>
                        <button type="button" @click="$store.compare.toggle($store.quickView.product)"
                                :class="$store.compare.has($store.quickView.product.id) ? 'bg-mint/10 border-mint text-mint-dark' : 'border-border text-ink-secondary hover:border-mint/40'"
                                class="border px-4 py-2.5 rounded-full text-sm font-semibold transition whitespace-nowrap">
                            <span x-text="$store.compare.has($store.quickView.product.id) ? 'Added ✓' : 'Compare'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Floating compare bar — appears once 1+ products are selected, persists across pages. --}}
    <div x-data x-show="$store.compare.items.length > 0" x-cloak x-transition
         class="fixed bottom-20 lg:bottom-4 left-1/2 -translate-x-1/2 z-40 bg-white border border-border rounded-full shadow-lg pl-2 pr-1.5 py-1.5 flex items-center gap-3 max-w-[92vw]">
        <div class="flex items-center -space-x-2 flex-shrink-0">
            <template x-for="item in $store.compare.items" :key="item.id">
                <div class="w-9 h-9 rounded-full border-2 border-white bg-canvas overflow-hidden flex-shrink-0">
                    <img :src="item.image" x-show="item.image" x-on:error="item.image = null" class="w-full h-full object-cover" alt="">
                </div>
            </template>
        </div>
        <span class="text-sm font-semibold text-charcoal whitespace-nowrap" x-text="$store.compare.items.length + ' selected'"></span>
        <button type="button" @click="$store.compare.openDrawer()"
                :disabled="$store.compare.items.length < 2"
                :class="$store.compare.items.length < 2 ? 'bg-mint/40 cursor-not-allowed' : 'bg-mint hover:bg-mint-dark'"
                class="text-white text-sm font-semibold px-4 py-2 rounded-full transition whitespace-nowrap">
            Compare now
        </button>
        <button type="button" @click="$store.compare.clear()" aria-label="Clear compare selection"
                class="w-8 h-8 rounded-full flex items-center justify-center text-ink-muted hover:text-charcoal hover:bg-canvas transition flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
    </div>

    {{-- Compare drawer — slides in from the right, fetches real spec data for the selected products when opened. --}}
    <div x-data x-show="$store.compare.open" x-cloak x-transition.opacity
         @keydown.escape.window="$store.compare.closeDrawer()"
         class="fixed inset-0 z-50 bg-brand-950/60" @click="$store.compare.closeDrawer()">
        <div @click.stop x-show="$store.compare.open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             class="fixed inset-y-0 right-0 z-50 w-full sm:w-[90vw] lg:w-[75vw] max-w-4xl bg-white shadow-2xl overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-border px-5 py-4 flex items-center justify-between z-10">
                <h2 class="font-display font-bold text-lg text-charcoal">Compare equipment</h2>
                <button type="button" @click="$store.compare.closeDrawer()" aria-label="Close" class="w-8 h-8 rounded-full flex items-center justify-center text-ink-secondary hover:text-charcoal hover:bg-canvas transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="p-5">
                <div x-show="$store.compare.loading" class="py-16 text-center text-ink-muted text-sm">Loading comparison…</div>

                <div x-show="!$store.compare.loading && $store.compare.items.length < 2" class="py-16 text-center text-ink-muted text-sm">
                    Add at least 2 items to compare — use the compare button on any product card.
                </div>

                <div x-show="!$store.compare.loading && $store.compare.items.length >= 2 && $store.compare.data && $store.compare.data.products.length < 2" class="py-16 text-center text-ink-muted text-sm">
                    One or more selected items are no longer available to compare.
                </div>

                <div x-show="!$store.compare.loading && $store.compare.data && $store.compare.data.products.length >= 2" class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left align-bottom pb-3 pr-4 w-40 flex-shrink-0"></th>
                                <template x-for="product in $store.compare.data?.products ?? []" :key="product.id">
                                    <th class="align-bottom pb-3 px-3 min-w-[180px] text-left">
                                        <div class="w-full aspect-square bg-canvas border border-border rounded-lg overflow-hidden mb-2">
                                            <img :src="product.image" x-show="product.image" x-on:error="product.image = null" class="w-full h-full object-contain p-3" alt="">
                                        </div>
                                        <a :href="product.url" class="font-semibold text-charcoal hover:text-mint-dark transition line-clamp-2" x-text="product.title"></a>
                                        <p class="font-mono font-bold text-charcoal mt-1" x-text="'R' + product.price"></p>
                                        <button type="button" @click="$store.compare.remove(product.id)" class="text-xs text-ink-muted hover:text-error transition mt-1">Remove</button>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="key in $store.compare.data?.specKeys ?? []" :key="key">
                                <tr class="border-t border-border">
                                    <td class="py-2.5 pr-4 font-medium text-ink-secondary align-top" x-text="key"></td>
                                    <template x-for="product in $store.compare.data?.products ?? []" :key="product.id + key">
                                        <td class="py-2.5 px-3 font-mono text-charcoal align-top" x-text="product.specs[key] ?? '—'"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Cart drawer — slides in from the right on every add/update; state mirrors $store.cart, the
         session cart's real server-computed truth (see Cart::summary()), never computed client-side. --}}
    <div x-data x-show="$store.cart.open" x-cloak x-transition.opacity
         @keydown.escape.window="$store.cart.open = false"
         class="fixed inset-0 z-50 bg-brand-950/60" @click="$store.cart.open = false">
        <div @click.stop x-show="$store.cart.open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             class="fixed inset-y-0 right-0 z-50 w-full sm:w-[420px] bg-white shadow-2xl flex flex-col">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-border flex-shrink-0">
                <h2 class="font-display font-semibold text-xl text-charcoal">Your Cart
                    <span class="text-sm font-body font-normal text-ink-muted" x-show="$store.cart.count > 0" x-text="'(' + $store.cart.count + ')'"></span>
                </h2>
                <button type="button" @click="$store.cart.open = false" aria-label="Close cart" class="w-8 h-8 rounded-full flex items-center justify-center text-ink-secondary hover:text-charcoal hover:bg-canvas transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4 relative">
                <div x-show="$store.cart.loading" class="absolute inset-0 bg-white/60 z-10 flex items-start justify-center pt-24" x-cloak>
                    <div class="w-5 h-5 border-2 border-mint border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div x-show="$store.cart.items.length === 0" class="py-20 text-center">
                    <p class="text-ink-secondary text-sm mb-4">Your cart is empty.</p>
                    <a href="{{ route('equipment.index') }}" @click="$store.cart.open = false" class="inline-block bg-mint hover:bg-mint-dark text-white font-semibold text-sm px-5 py-2.5 rounded-full transition">Browse equipment</a>
                </div>

                <ul class="space-y-4" x-show="$store.cart.items.length > 0">
                    <template x-for="item in $store.cart.items" :key="item.line_key">
                        <li class="flex gap-3">
                            <a :href="item.url" @click="$store.cart.open = false" class="w-16 h-16 rounded-lg bg-canvas border border-border overflow-hidden flex-shrink-0">
                                <img :src="item.image" x-show="item.image" x-on:error="item.image = null" class="w-full h-full object-contain p-1.5" alt="">
                            </a>
                            <div class="min-w-0 flex-1">
                                <a :href="item.url" @click="$store.cart.open = false" class="text-sm font-medium text-charcoal hover:text-mint-dark transition line-clamp-2" x-text="item.title"></a>
                                <p x-show="item.variant_label" class="text-xs text-ink-muted mt-0.5" x-text="item.variant_label"></p>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center border border-border rounded-full">
                                        <button type="button" @click="$store.cart.updateQuantity(item, item.quantity - 1)" aria-label="Decrease quantity" class="w-7 h-7 flex items-center justify-center text-ink-secondary hover:text-charcoal transition">&minus;</button>
                                        <span class="w-6 text-center text-xs font-mono font-semibold text-charcoal" x-text="item.quantity"></span>
                                        <button type="button" @click="$store.cart.updateQuantity(item, item.quantity + 1)" aria-label="Increase quantity" class="w-7 h-7 flex items-center justify-center text-ink-secondary hover:text-charcoal transition">+</button>
                                    </div>
                                    <span class="font-mono text-sm font-semibold text-charcoal" x-text="'R' + item.line_total.toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                                </div>
                            </div>
                            <button type="button" @click="$store.cart.removeItem(item)" aria-label="Remove item" class="flex-shrink-0 text-ink-muted hover:text-error transition self-start mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>

            <div x-show="$store.cart.items.length > 0" class="flex-shrink-0 border-t border-border px-5 py-4 space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-ink-secondary">Subtotal</span>
                    <span class="font-mono text-base font-bold text-charcoal" x-text="'R' + $store.cart.subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})"></span>
                </div>
                <p class="text-xs text-ink-muted">VAT included &middot; shipping calculated at checkout</p>
                <a href="{{ route('checkout.index') }}" class="block text-center bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-3 rounded-full transition">Checkout</a>
                <a href="{{ route('cart.index') }}" @click="$store.cart.open = false" class="block text-center text-sm font-semibold text-charcoal hover:text-brand-900 transition">View full cart</a>
            </div>
        </div>
    </div>
</body>
</html>
