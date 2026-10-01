{{-- Order / interest form. $listing (optional) = what they're asking about. --}}
@php use App\Support\SiteImages as Img; @endphp
<section id="order" class="relative overflow-hidden bg-char text-sand scroll-mt-20">
    <img src="{{ Img::url('dirt-road', true) }}" srcset="{{ Img::srcset('dirt-road') }}" sizes="100vw" alt="{{ Img::alt('dirt-road') }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover opacity-45 img-grade">
    <div class="absolute inset-0 bg-gradient-to-r from-char via-char/85 to-char/40"></div>
    <div class="relative wrap py-24 sm:py-32 grid lg:grid-cols-2 gap-16 items-center">
        <div>
            <p class="eyebrow text-sand/50">{{ $listing && $listing->price_cents ? 'Order' : 'Be first in line' }}</p>
            <h2 class="h-display mt-6 text-[clamp(2.8rem,6vw,5.5rem)]">{{ $heading ?? 'Get yours' }}<em class="text-ochre-light">.</em></h2>
            <p class="mt-8 text-lg text-sand/70 max-w-md leading-relaxed">
                @if ($listing && $listing->price_cents)
                    Leave your details and we'll confirm the total with delivery, build time and how to pay — nothing is charged until you say yes.
                @else
                    Leave your details and you'll hear first when they're ready — with early-bird pricing. No spam, promise.
                @endif
            </p>
            <p class="mt-4 text-sm text-sand/50">7-day cooling-off and a 6-month warranty on every device. <a href="{{ route('legal.show', 'returns') }}" class="underline">Details</a>.</p>
        </div>
        <div class="rounded-[28px] bg-sand text-char p-7 sm:p-10">
            @if (session('lead_ok'))
                <div class="py-10 text-center"><div class="font-headline text-5xl">Baie dankie!</div><p class="mt-4 text-stone">Got it. We'll be in touch within one working day.</p></div>
            @else
                <form method="POST" action="{{ route('site.interest') }}" class="grid sm:grid-cols-2 gap-4">
                    @csrf
                    <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
                    @if ($listing)<input type="hidden" name="listing" value="{{ $listing->id }}">@endif
                    <div class="sm:col-span-2"><label class="field-label" for="o-name">Name *</label><input id="o-name" name="name" value="{{ old('name') }}" required class="field"></div>
                    <div><label class="field-label" for="o-email">Email *</label><input id="o-email" type="email" name="email" value="{{ old('email') }}" required class="field"></div>
                    <div><label class="field-label" for="o-phone">Cellphone</label><input id="o-phone" name="phone" value="{{ old('phone') }}" class="field"></div>
                    <div><label class="field-label" for="o-farm">Farm / stud</label><input id="o-farm" name="farm_name" value="{{ old('farm_name') }}" class="field"></div>
                    <div><label class="field-label" for="o-herd">Herd size</label>
                        <select id="o-herd" name="herd_size" class="field"><option value="">—</option>@foreach (['Under 100', '100 – 500', '500 – 2 000', '2 000+'] as $o)<option @selected(old('herd_size') === $o)>{{ $o }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="field-label" for="o-prov">Province</label>
                        <select id="o-prov" name="province" class="field"><option value="">—</option>@foreach (\App\Http\Controllers\SiteController::PROVINCES as $p)<option @selected(old('province') === $p)>{{ $p }}</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="field-label" for="o-msg">Anything else? <span class="font-normal text-stone-light">(how you farm, what you need)</span></label><textarea id="o-msg" name="message" rows="2" class="field">{{ old('message') }}</textarea></div>
                    <label class="sm:col-span-2 flex gap-3 text-sm text-stone"><input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-hairline text-char"> <span>You may contact me about this. I've read the <a href="{{ route('legal.show', 'privacy') }}" class="underline text-char" target="_blank">privacy policy</a>.</span></label>
                    @if ($errors->any())<p class="sm:col-span-2 text-sm text-[#B0452F]">{{ $errors->first() }}</p>@endif
                    <button class="btn-dark sm:col-span-2 mt-1">{{ $listing && $listing->price_cents ? 'Request my order' : 'Put me on the list' }}</button>
                </form>
            @endif
        </div>
    </div>
</section>
