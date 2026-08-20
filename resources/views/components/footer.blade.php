@php
    $whatsapp = \App\Models\Setting::get('support_whatsapp', '');
    $whatsappUrl = $whatsapp ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $whatsapp) : null;
@endphp

<footer class="bg-brand-950 text-slate-300 mt-20">
    <div class="max-w-7xl mx-auto px-4 py-14 grid gap-10 md:grid-cols-4">
        {{-- Column 1: Brand & Assurance --}}
        <div>
            <p class="font-display font-bold text-lg text-white mb-3">Farm<span class="text-mint">tech</span></p>
            <p class="text-sm leading-relaxed">AI-vetted commercial and industrial technology, sourced from verified overseas suppliers and cleared for South African buyers.</p>
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 mt-4 bg-white/10 border border-white/15 hover:bg-white/15 rounded-full px-3.5 py-2 text-xs font-semibold text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2z"/></svg>
                    Chat on WhatsApp
                </a>
            @endif
        </div>

        {{-- Column 2: Direct Equipment Catalogs --}}
        <div>
            <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Equipment Catalogs</p>
            <ul class="text-sm space-y-1.5 leading-relaxed columns-2 md:columns-1">
                @foreach (\App\Enums\ProductCategory::cases() as $footerCategory)
                    <li><a href="{{ route('category.show', $footerCategory) }}" class="hover:text-mint-light transition">{{ $footerCategory->shortLabel() }}</a></li>
                @endforeach
            </ul>
        </div>

        {{-- Column 3: Import, Customs & Tracking --}}
        <div>
            <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Import, Customs &amp; Tracking</p>
            <ul class="text-sm space-y-2 leading-relaxed">
                <li><a href="{{ route('track.index') }}" class="hover:text-mint-light transition">Track Your Order</a></li>
                <li><a href="{{ route('policies.terms') }}#import" class="hover:text-mint-light transition">How Direct Air Import Works</a></li>
                <li><a href="{{ route('policies.terms') }}#import" class="hover:text-mint-light transition">SARS VAT &amp; Duty Clearance</a></li>
                <li><a href="{{ route('policies.terms') }}#delivery" class="hover:text-mint-light transition">Courier Delivery Timeline</a></li>
            </ul>
        </div>

        {{-- Column 4: Legal & Compliance --}}
        <div>
            <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Legal &amp; Compliance</p>
            <ul class="text-sm space-y-2 leading-relaxed">
                <li><a href="{{ route('policies.returns') }}" class="hover:text-mint-light transition">Returns &amp; Warranty</a></li>
                <li><a href="{{ route('policies.terms') }}" class="hover:text-mint-light transition">Terms of Sale</a></li>
                <li><a href="{{ route('policies.icasa') }}" class="hover:text-mint-light transition">ICASA &amp; ISO Compliance</a></li>
                <li><a href="mailto:support@farmtech.co.za" class="hover:text-mint-light transition">support@farmtech.co.za</a></li>
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
