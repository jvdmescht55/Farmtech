@extends('layouts.storefront')

@section('title', 'Our Promise — Direct Farm-Tech Procurement | Farmtech')
@section('meta_description', 'No co-op markups. Farmtech sources and vets farm technology directly from manufacturers, and acts as your local warranty partner.')

@php
    $whatsapp = \App\Models\Setting::get('support_whatsapp', '');
    $whatsappUrl = $whatsapp ? 'https://wa.me/'.preg_replace('/[^0-9]/', '', $whatsapp) : null;
@endphp

@section('content')
    {{-- Hero — the core positioning statement, stated plainly rather than
         buried in marketing copy. --}}
    <div class="bg-canvas border-b border-border">
        <div class="max-w-4xl mx-auto px-4 py-16 text-center">
            <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">Our Promise</p>
            <h1 class="font-display font-bold text-3xl sm:text-4xl text-charcoal leading-tight mb-4">
                No Co-Op Markups.<br class="hidden sm:block"><span class="italic font-medium text-brand-900">Vetted Farm Technology, Direct.</span>
            </h1>
            <p class="text-ink-secondary text-lg leading-relaxed max-w-2xl mx-auto">
                Farmtech isn't another reseller — we're your technical procurement agent. We source, vet, and land
                high-precision farm and industrial equipment directly from Tier-1 manufacturers, and act as your
                local warranty partner long after the truck leaves your farm gate.
            </p>
        </div>
    </div>

    {{-- Why direct procurement — the honest economics behind the model. --}}
    <div class="max-w-4xl mx-auto px-4 py-20">
        <div class="max-w-2xl mb-10">
            <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">Why Direct?</p>
            <h2 class="font-display font-bold text-2xl sm:text-3xl text-charcoal">Why traditional agri-tech carries a 100%&ndash;300% markup</h2>
        </div>
        <div class="prose-none space-y-5 text-ink-secondary leading-relaxed">
            <p>
                Equipment that lands on a local co-op or agri-dealer shelf has usually passed through three or four
                middlemen — a manufacturer, an international wholesaler, a local distributor, and then the co-op
                itself — before it ever reaches a farmer's invoice. Every link adds its own margin, often without
                adding any technical value for the farmer who ultimately pays for it.
            </p>
            <p>
                Farmtech cuts that chain short. We source directly from the manufacturer or an authorized factory
                distributor at wholesale-tier pricing, and add only one margin — our own, which covers our technical
                vetting, customs clearance, local warranty support, and delivery to your farm gate. The result: the
                same — or better — equipment, without the stacked co-op markup.
            </p>
        </div>
    </div>

    {{-- Vetting process — the concrete, technical part of the "trust us" claim. --}}
    <div class="bg-brand-950 text-white">
        <div class="max-w-5xl mx-auto px-4 py-20">
            <div class="max-w-2xl mb-12">
                <p class="text-xs uppercase tracking-[0.2em] text-mint-light font-semibold mb-3">The Vetting Process</p>
                <h2 class="font-display font-bold text-2xl sm:text-3xl text-white">What "vetted" actually means</h2>
                <p class="text-white/70 mt-3 leading-relaxed">
                    Every item listed on Farmtech goes through a fixed set of technical checks before it's approved
                    — not a marketing claim, a process.
                </p>
            </div>
            <div class="grid sm:grid-cols-2 gap-x-10 gap-y-10">
                @foreach ([
                    ['n' => '01', 'title' => 'Sensor & Measurement Accuracy', 'body' => 'Where applicable, published sensor specifications (weighing accuracy, RFID read range, ultrasonic precision) are checked against the manufacturer\'s own datasheet and real user feedback before a listing goes live.'],
                    ['n' => '02', 'title' => 'IP68 & Waterproofing Rating', 'body' => 'Outdoor equipment intended for field use must carry a real, verified IP rating — not a vague "water resistant" claim. The rating shown on every product page comes directly from the manufacturer\'s own certification.'],
                    ['n' => '03', 'title' => 'Electronic Build Quality', 'body' => 'Power supply, frequency compliance (ICASA where applicable), and battery transport certification are checked per item, so equipment that arrives in South Africa can actually work here and be legally imported.'],
                    ['n' => '04', 'title' => 'Supplier Reliability', 'body' => 'We work only with manufacturers and distributors with a proven trading history and trade assurance — not one-off or unverified sellers on general wholesale marketplaces.'],
                ] as $item)
                    <div>
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-mint/15 border border-mint/30 text-mint-light font-mono text-sm font-bold mb-3">{{ $item['n'] }}</span>
                        <h3 class="font-display font-bold text-lg text-white mb-1.5">{{ $item['title'] }}</h3>
                        <p class="text-white/70 leading-relaxed text-sm">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Farmtech as procurement + warranty partner — the reassurance close. --}}
    <div class="max-w-4xl mx-auto px-4 py-20">
        <div class="max-w-2xl mb-10">
            <p class="text-xs uppercase tracking-[0.2em] text-mint-dark font-semibold mb-3">After The Purchase</p>
            <h2 class="font-display font-bold text-2xl sm:text-3xl text-charcoal">Your technical procurement agent — and your local warranty partner</h2>
        </div>
        <div class="grid sm:grid-cols-2 gap-x-10 gap-y-8">
            <div class="flex gap-4">
                <span class="text-mint-dark flex-shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <div>
                    <h3 class="font-display font-bold text-charcoal mb-1">We handle the import process</h3>
                    <p class="text-ink-secondary text-sm leading-relaxed">SARS customs clearance, import duties, and courier delivery to your farm gate are already included in the price and handled by us — no separate invoice or surprise at delivery.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <span class="text-mint-dark flex-shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <div>
                    <h3 class="font-display font-bold text-charcoal mb-1">Local warranty, not overseas hassle</h3>
                    <p class="text-ink-secondary text-sm leading-relaxed">If something fails within the warranty period, we replace it locally — you never ship equipment overseas for repair or replacement.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <span class="text-mint-dark flex-shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <div>
                    <h3 class="font-display font-bold text-charcoal mb-1">Dedicated technical support</h3>
                    <p class="text-ink-secondary text-sm leading-relaxed">Setup, calibration, and technical questions are handled directly via WhatsApp and phone, by people who know the equipment — not a generic call centre.</p>
                </div>
            </div>
            <div class="flex gap-4">
                <span class="text-mint-dark flex-shrink-0 mt-0.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <div>
                    <h3 class="font-display font-bold text-charcoal mb-1">A direct relationship, no middleman</h3>
                    <p class="text-ink-secondary text-sm leading-relaxed">You buy from Farmtech, and Farmtech is accountable for the full experience — from manufacturer to your farm gate, and every step after.</p>
                </div>
            </div>
        </div>

        <div class="mt-14 flex flex-wrap items-center gap-4">
            <a href="{{ route('equipment.index') }}" class="inline-flex items-center gap-2 bg-mint hover:bg-mint-dark text-white text-sm font-semibold px-7 py-3.5 rounded-full transition-all hover:gap-3">
                Browse Vetted Equipment
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 border border-border hover:border-emerald-300 hover:bg-emerald-50/50 text-charcoal text-sm font-semibold px-7 py-3.5 rounded-full transition">
                    Talk to Technical Service
                </a>
            @endif
        </div>
    </div>
@endsection
