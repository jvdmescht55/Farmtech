@php
    $whatsapp = \App\Models\Setting::get('support_whatsapp', '');
    $whatsappUrl = $whatsapp ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $whatsapp) : null;
@endphp

<footer class="bg-brand-950 text-ink-muted">
    <div class="max-w-7xl mx-auto px-4 py-12 grid gap-8 sm:grid-cols-2 md:grid-cols-4">
        {{-- Column 1: Brand & Assurance --}}
        <div>
            <p class="font-display font-bold text-lg text-white mb-2">Farm<span class="text-mint">tech</span></p>
            <p class="text-sm leading-relaxed text-ink-muted">Professional agricultural &amp; industrial equipment, sourced directly from Tier-1 manufacturers and backed locally — built for South African farms.</p>
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 mt-4 bg-white/10 border border-white/15 hover:bg-white/15 rounded-full px-3.5 py-2 text-xs font-semibold text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-emerald-400" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2z"/></svg>
                    WhatsApp Farmtech
                </a>
            @endif

            {{-- Real configured checkout gateways only — no third-party card-network trademarks, since displaying Visa/Mastercard marks implies a brand relationship this site doesn't have. --}}
            <div class="mt-5">
                <p class="text-[10px] uppercase tracking-wide text-ink-muted/70 mb-1.5">Secure checkout via</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (['PayFast', 'Ozow', 'Yoco'] as $gateway)
                        <span class="text-[11px] font-medium text-ink-muted bg-white/5 border border-white/10 rounded px-2 py-1">{{ $gateway }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Column 2: Equipment (industries — secondary nav; the mega-menu, not the footer, is the real discovery surface) --}}
        <div>
            <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Equipment</p>
            <ul class="text-sm space-y-2 leading-relaxed">
                @foreach (\App\Enums\Industry::activeCases() as $footerIndustry)
                    <li><a href="{{ route('domain.'.$footerIndustry->domainSlug()) }}" class="hover:text-mint-light transition">{{ $footerIndustry->label() }}</a></li>
                @endforeach
                <li><a href="{{ route('equipment.index') }}" class="hover:text-mint-light transition">Shop by Equipment</a></li>
                <li><a href="{{ route('finder.index') }}" class="hover:text-mint-light transition">Equipment Finder</a></li>
            </ul>
        </div>

        {{-- Column 3: Support --}}
        <div>
            <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Support</p>
            <ul class="text-sm space-y-2 leading-relaxed">
                <li><a href="{{ route('support.index') }}" class="hover:text-mint-light transition">Help &amp; FAQ</a></li>
                <li><a href="{{ route('track.index') }}" class="hover:text-mint-light transition">Track order</a></li>
                <li><a href="{{ route('policies.shipping') }}" class="hover:text-mint-light transition">Shipping &amp; delivery</a></li>
                <li><a href="{{ route('policies.returns') }}" class="hover:text-mint-light transition">Returns &amp; warranty</a></li>
                <li><a href="{{ route('support.index') }}" class="hover:text-mint-light transition">Contact</a></li>
            </ul>
        </div>

        {{-- Column 4: Information (legal + secondary content) --}}
        <div>
            <p class="font-display font-semibold text-white text-sm uppercase tracking-wide mb-3">Information</p>
            <ul class="text-sm space-y-2 leading-relaxed">
                <li><a href="{{ route('about.index') }}" class="hover:text-mint-light transition">Our Promise</a></li>
                <li><a href="{{ route('how-it-works') }}" class="hover:text-mint-light transition">How importing works</a></li>
                <li><a href="{{ route('policies.terms') }}#import" class="hover:text-mint-light transition">VAT &amp; duty</a></li>
                <li><a href="{{ route('policies.icasa') }}" class="hover:text-mint-light transition">Compliance &amp; documentation</a></li>
                <li><a href="{{ route('policies.terms') }}" class="hover:text-mint-light transition">Terms</a></li>
                <li><a href="{{ route('policies.privacy') }}" class="hover:text-mint-light transition">Privacy</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-[12px] text-ink-muted/70">
                <span>&copy; {{ date('Y') }} Farmtech</span>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('policies.terms') }}" class="hover:text-ink-muted transition">Terms</a>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('policies.privacy') }}" class="hover:text-ink-muted transition">Privacy</a>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('policies.shipping') }}" class="hover:text-ink-muted transition">Shipping</a>
                <span aria-hidden="true">&middot;</span>
                <a href="{{ route('policies.returns') }}" class="hover:text-ink-muted transition">Returns</a>
            </div>
            <details class="text-[12px] text-ink-muted/70">
                <summary class="cursor-pointer hover:text-ink-muted transition list-none">Photo credits</summary>
                <ul class="mt-2 grid sm:grid-cols-2 gap-x-6 gap-y-1 text-left">
                    @foreach (\App\Enums\ProductCategory::cases() as $creditCategory)
                        @php($img = $creditCategory->image())
                        <li>{{ $creditCategory->shortLabel() }}: <a href="{{ $img['source_url'] }}" target="_blank" rel="noopener" class="hover:text-mint-light transition">{{ $img['credit'] }}</a>, {{ $img['license'] }}</li>
                    @endforeach
                </ul>
            </details>
        </div>
    </div>
</footer>
