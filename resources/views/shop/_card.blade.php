{{-- Shop product card. $l = StoreListing --}}
@php
    $stockTone = match (true) {
        $l->buyable() && $l->stock_status === 'low_stock' => 'bg-ochre text-char',
        $l->buyable() => 'bg-[#3F7A3A] text-white',
        default => 'bg-char text-sand',
    };
@endphp
<article class="group relative flex flex-col rounded-[28px] bg-white border border-hairline overflow-hidden hover:shadow-[0_30px_70px_-35px_rgba(0,0,0,.35)] transition duration-500">
    <a href="{{ route('site.product', $l) }}" class="relative block aspect-[4/3] overflow-hidden bg-sand-deep">
        @if ($src = $l->photoUrl())
            <img src="{{ $src }}" alt="{{ $l->name }}" loading="lazy" class="absolute inset-0 w-full h-full object-cover transition duration-[1.2s] group-hover:scale-[1.04] {{ $l->images || in_array($l->photo_key, ['kraaltrac-pro', 'ear-tags']) ? '' : 'img-grade' }}">
        @endif
        <div class="absolute left-4 top-4 flex flex-wrap gap-1.5">
            <span class="chip {{ $stockTone }}">{{ $l->buyable() ? ($l->stock_status === 'low_stock' ? $l->stockLabel() : 'In stock') : ($l->stock_status === 'coming_soon' ? 'Coming soon' : 'Sold out · reserve') }}</span>
            @if ($l->badge && $l->badge !== 'Coming soon')<span class="chip bg-white/90 text-char">{{ $l->badge }}</span>@endif
        </div>
    </a>
    <div class="p-6 sm:p-7 flex flex-col flex-1">
        <div class="text-sm text-stone">{{ $l->category ?: 'Farmtech' }}</div>
        <h3 class="mt-1 font-headline text-3xl leading-tight"><a href="{{ route('site.product', $l) }}" class="hover:underline decoration-1 underline-offset-4">{{ $l->name }}</a></h3>
        <p class="mt-2 text-stone text-[15px] leading-relaxed flex-1">{{ $l->tagline }}</p>
        <div class="mt-5 flex items-baseline gap-2">
            @if ($l->priceLabel())
                <span class="font-headline text-3xl">{{ $l->priceLabel() }}</span>
                @if ($l->compare_at_cents)<span class="text-stone line-through text-sm">{{ \App\Models\StoreListing::rand($l->compare_at_cents) }}</span>@endif
                @if ($l->unit_label)<span class="text-sm text-stone">· {{ $l->unit_label }}</span>@endif
            @else
                <span class="text-stone">Price confirmed before it ships</span>
            @endif
        </div>
        @if (! $l->buyable() && $l->next_batch)<p class="mt-2 text-[13px] text-stone">{{ $l->next_batch }}</p>@endif
        <div class="mt-5 flex gap-2">
            @include('shop._buy', ['l' => $l, 'class' => 'flex-1 w-full', 'wrap' => 'flex-1'])
            <a href="{{ route('site.product', $l) }}" class="btn-line px-5" aria-label="Details: {{ $l->name }}">Details</a>
        </div>
    </div>
</article>
