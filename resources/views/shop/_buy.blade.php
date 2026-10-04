{{-- Add-to-cart / reserve button for a store listing. $l = listing, $qty = show qty picker, $class = extra button classes --}}
@php($canAct = $l->buyable() || $l->reservable())
@if ($canAct)
    <form method="POST" action="{{ route('shop.add', $l) }}" class="flex items-stretch gap-2 {{ $wrap ?? '' }}" data-no-busy>
        @csrf
        @if ($qty ?? false)
            <label class="sr-only" for="qty-{{ $l->id }}">Quantity</label>
            <select id="qty-{{ $l->id }}" name="qty" class="field !w-20 !h-12">@for ($i = 1; $i <= min(10, $l->maxQty()); $i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select>
        @endif
        <button class="btn-dark {{ $class ?? '' }}">
            {{ $l->buyable() ? 'Add to cart' : 'Reserve one' }}
        </button>
    </form>
@endif
