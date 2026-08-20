@props(['products', 'sticky' => false])

@php
    // Never show a bare "No image" placeholder — same category-icon-on-a-
    // clean-background treatment as <x-product-image-fallback>, just built
    // as a lookup table since these cards render client-side from a JSON
    // array, not server-rendered Blade per product.
    $iconSvgs = $products->pluck('category')->unique(fn ($c) => $c->icon())
        ->mapWithKeys(fn ($c) => [$c->icon() => (string) view('components.category-icon', ['icon' => $c->icon(), 'class' => 'w-6 h-6'])]);
@endphp

@if ($products->isNotEmpty())
    <div
        x-data="{
            collapsed: false,
            quickView: null,
            iconSvgs: {{ Illuminate\Support\Js::from($iconSvgs) }},
            products: {{ Illuminate\Support\Js::from($products->map(fn ($p) => [
                'title' => $p->title,
                'category' => $p->category->industry()->badgeLabel(),
                'price' => number_format((float) $p->retail_price_zar, 2),
                'image' => $p->thumbnail?->url,
                'icon' => $p->category->icon(),
                'stock_status' => $p->stock_status,
                'is_low_stock' => $p->isLowStock(),
                'url' => route('products.show', $p),
            ])) }},
        }"
        {{ $attributes->merge(['class' => 'bg-slate-100/70 border-y border-slate-200 '.($sticky ? 'sticky top-[76px] z-30' : '')]) }}>
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between py-2.5">
                <p class="text-[11px] font-mono uppercase tracking-[0.15em] text-brand-900/70 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    Top 5 Trending Commercial Hardware
                </p>
                @if ($sticky)
                    <button type="button" @click="collapsed = !collapsed" class="text-slate-500 hover:text-brand-900 text-xs font-semibold flex items-center gap-1 transition">
                        <span x-text="collapsed ? 'Show' : 'Hide'"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 transition-transform" :class="collapsed && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                    </button>
                @endif
            </div>

            <div x-show="!collapsed" x-transition class="flex gap-3 overflow-x-auto pb-3 -mx-1 px-1">
                <template x-for="(product, i) in products" :key="product.url">
                    <button type="button" @click="quickView = product" class="flex-shrink-0 w-56 text-left bg-white border border-slate-200 rounded-xl p-3 hover:border-mint/40 hover:shadow-sm hover:-translate-y-0.5 transition-all group">
                        <div class="flex items-start gap-2.5">
                            <div class="w-14 h-14 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0 relative">
                                <img :src="product.image" x-show="product.image" x-on:error="product.image = null" class="w-full h-full object-cover" alt="">
                                <div x-show="!product.image" class="absolute inset-0 flex items-center justify-center bg-slate-50 text-slate-400" x-html="iconSvgs[product.icon]"></div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="inline-block text-[9px] font-semibold uppercase tracking-wide text-mint-dark bg-mint/10 rounded px-1.5 py-0.5" x-text="product.category"></span>
                                <p class="text-xs font-medium text-brand-900 mt-1 line-clamp-2 leading-snug" x-text="product.title"></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2.5 pt-2.5 border-t border-slate-100">
                            <span class="font-mono text-sm font-bold text-brand-900" x-text="'R' + product.price"></span>
                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold" :class="product.stock_status === 'in_stock' ? 'text-emerald-700' : 'text-precision-dark'">
                                <span class="relative flex w-1.5 h-1.5">
                                    <span class="animate-ping absolute inline-flex w-full h-full rounded-full opacity-75" :class="product.stock_status === 'in_stock' ? 'bg-mint' : 'bg-precision'"></span>
                                    <span class="relative inline-flex rounded-full w-1.5 h-1.5" :class="product.stock_status === 'in_stock' ? 'bg-mint' : 'bg-precision'"></span>
                                </span>
                                <span x-text="product.stock_status === 'in_stock' ? 'In Stock' : 'Express Air Import'"></span>
                            </span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        {{-- Quick View modal --}}
        <div x-show="quickView" x-cloak x-transition.opacity @keydown.escape.window="quickView = null"
             class="fixed inset-0 z-50 bg-brand-950/60 flex items-center justify-center p-4" @click="quickView = null">
            <div x-show="quickView" @click.stop class="bg-white border border-slate-200 rounded-2xl max-w-md w-full overflow-hidden" x-transition.scale.origin.center>
                <template x-if="quickView">
                    <div>
                        <div class="aspect-video bg-slate-100 relative">
                            <img :src="quickView.image" x-show="quickView.image" x-on:error="quickView.image = null" class="w-full h-full object-cover" alt="">
                            <div x-show="!quickView.image" class="absolute inset-0 flex items-center justify-center bg-slate-50 text-slate-400" x-html="iconSvgs[quickView.icon]"></div>
                            <button type="button" @click="quickView = null" class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:text-slate-900 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <div class="p-5">
                            <span class="inline-block text-[10px] font-semibold uppercase tracking-wide text-mint-dark bg-mint/10 rounded px-2 py-0.5" x-text="quickView.category"></span>
                            <p class="font-display font-bold text-lg text-brand-900 mt-2" x-text="quickView.title"></p>
                            <p class="font-mono text-2xl font-bold text-brand-900 mt-2" x-text="'R' + quickView.price"></p>
                            <p class="text-xs text-slate-500 mt-1">incl. duty &amp; 15% VAT — nothing extra on delivery</p>
                            <a :href="quickView.url" class="mt-4 flex items-center justify-center gap-2 bg-mint hover:bg-mint-dark text-white font-semibold px-6 py-3 rounded-full transition">
                                View Full Details &amp; Specifications
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endif
