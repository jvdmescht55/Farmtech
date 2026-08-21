@extends('layouts.storefront')

@section('title', 'Support — Farmtech')
@section('meta_description', 'Customs clearance, delivery timelines, warranty, and how to reach a Farmtech equipment specialist.')

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-12">
        <p class="text-xs uppercase tracking-widest text-mint-dark font-semibold text-center">Help &amp; Support</p>
        <h1 class="font-display font-bold text-2xl sm:text-3xl text-brand-900 mt-1 text-center">How can we help?</h1>

        @if ($whatsappUrl ?? null)
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener"
               class="mt-6 flex items-center gap-3 border border-border bg-white rounded-xl px-4 py-3 hover:border-emerald-300 hover:bg-emerald-50/50 transition group max-w-md mx-auto">
                <span class="w-9 h-9 rounded-full bg-emerald-500 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 text-white" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2c-5.46 0-9.9 4.44-9.9 9.9 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.44 9.9-9.9 0-2.64-1.03-5.13-2.9-6.99A9.82 9.82 0 0 0 12.04 2zm0 1.67c2.1 0 4.08.82 5.57 2.31a7.85 7.85 0 0 1 2.3 5.56c0 4.34-3.53 7.87-7.87 7.87a7.9 7.9 0 0 1-4-1.09l-.29-.17-2.98.78.79-2.9-.19-.3a7.86 7.86 0 0 1-1.2-4.19c0-4.34 3.53-7.87 7.87-7.87zm-4.32 4.5c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.02s.87 2.35.99 2.51c.12.16 1.7 2.6 4.13 3.64.58.25 1.03.4 1.38.51.58.19 1.11.16 1.53.1.47-.07 1.43-.58 1.63-1.15.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28-.24-.12-1.43-.71-1.65-.79-.22-.08-.38-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.42-1.33-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.34-.76-1.83-.2-.48-.4-.42-.55-.42h-.47z"/></svg>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-brand-900 group-hover:text-emerald-700 transition">Chat with an equipment specialist</span>
                    <span class="block text-xs text-ink-secondary">Fastest way to get a technical question answered on WhatsApp</span>
                </span>
            </a>
        @endif

        {{-- FAQ accordion — real facts already established elsewhere on the site, not new claims --}}
        <div x-data="{ open: 0 }" class="mt-10 space-y-2">
            <h2 class="font-display font-semibold text-lg text-charcoal mb-2">Frequently asked questions</h2>

            @php
                $faqs = [
                    [
                        'q' => 'Do I pay customs duty or VAT separately?',
                        'a' => 'No. Every listed price already includes South African import duty (the rate varies by product/HS code) and 15% VAT — the price you see on the product page is the price you pay, with nothing extra to clear on delivery.',
                    ],
                    [
                        'q' => 'How long does delivery take?',
                        'a' => 'Typically 7–12 business days via tracked Direct Express air freight once payment clears, though exact lead time is shown on each product page and can vary by item. You can follow an order\'s real status any time on the '.'<a href="'.route('track.index').'" class="text-mint-dark hover:underline">order tracking page</a>.',
                    ],
                    [
                        'q' => 'What if the equipment is defective or not as described?',
                        'a' => 'Under section 56 of the Consumer Protection Act, you can return it within 14 days of delivery for a repair, replacement, or full refund — your choice, not ours. See the full <a href="'.route('policies.returns').'" class="text-mint-dark hover:underline">Returns &amp; Warranty Policy</a> for exactly how that works.',
                    ],
                    [
                        'q' => 'How is equipment checked before it\'s listed?',
                        'a' => 'Every listing goes through an AI-assisted compliance pass — supplier review, product information checks, specification verification, and (where applicable) import requirements like ICASA approval or ISO 11784/11785 frequency compliance — with human review before anything is approved for sale. Products that pass show a "Farmtech Verified" or "Farmtech Checked" badge on their card.',
                    ],
                    [
                        'q' => 'Is warranty included?',
                        'a' => 'Warranty terms vary by supplier and model, since Farmtech imports from multiple overseas manufacturers rather than manufacturing hardware itself. Contact support with the product\'s SKU (shown on its product page) and we\'ll confirm the specific coverage for that item.',
                    ],
                    [
                        'q' => 'Which couriers deliver Farmtech orders?',
                        'a' => 'Depending on the order, deliveries go via The Courier Guy, DHL Express South Africa, RAM, or DawnWing. Once an order ships, its real courier and tracking number appear on the order tracking page with a link to that courier\'s own tracking portal.',
                    ],
                ];
            @endphp

            @foreach ($faqs as $i => $faq)
                <div class="card !rounded-xl overflow-hidden">
                    <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}"
                            class="w-full flex items-center justify-between gap-3 px-5 py-4 text-left hover:bg-mint/5 transition">
                        <span class="font-semibold text-charcoal text-sm">{{ $faq['q'] }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-ink-muted flex-shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="open === {{ $i }}" x-transition x-cloak class="px-5 pb-4 text-sm text-ink-secondary leading-relaxed">
                        {!! $faq['a'] !!}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Contact form --}}
        <div class="mt-12 card p-6">
            <h2 class="font-display font-semibold text-lg text-charcoal mb-1">Still need help?</h2>
            <p class="text-sm text-ink-secondary mb-5">Send us a message and we'll reply by email.</p>

            @if (session('status'))
                <div class="mb-4 bg-mint/10 border border-mint/30 text-mint-dark rounded-lg px-4 py-3 text-sm">{{ session('status') }}</div>
            @endif

            <form action="{{ route('support.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Your name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                        @error('name')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                        @error('email')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Topic</label>
                    <input type="text" name="topic" value="{{ old('topic') }}" placeholder="e.g. Order status, a product question, a return" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">
                    @error('topic')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Message</label>
                    <textarea name="message" rows="4" required class="w-full border border-border rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-mint/30">{{ old('message') }}</textarea>
                    @error('message')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="bg-mint hover:bg-mint-dark text-white font-semibold px-8 py-3 rounded-full transition">Send message</button>
            </form>
        </div>
    </div>
@endsection
