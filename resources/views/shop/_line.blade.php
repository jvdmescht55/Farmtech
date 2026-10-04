{{-- One cart line. $line = ['listing','qty','reserve','unit','line'], $compact = drawer style --}}
@php($l = $line['listing'])
<div class="flex gap-4 {{ ($compact ?? false) ? '' : 'py-5 border-b border-hairline' }}">
    <a href="{{ route('site.product', $l) }}" class="shrink-0 w-20 h-20 rounded-2xl overflow-hidden bg-sand-deep">
        @if ($src = $l->photoUrl())<img src="{{ $src }}" alt="" class="w-full h-full object-cover">@endif
    </a>
    <div class="flex-1 min-w-0">
        <div class="flex justify-between gap-3">
            <a href="{{ route('site.product', $l) }}" class="font-medium leading-snug hover:underline">{{ $l->name }}@if ($l->unit_label)<span class="text-stone font-normal"> · {{ $l->unit_label }}</span>@endif</a>
            <span class="font-num text-sm shrink-0">{{ $line['line'] ? \App\Models\StoreListing::rand($line['line']) : 'TBC' }}</span>
        </div>
        @if ($line['reserve'])
            <div class="mt-1 text-xs text-ochre-dark">Reservation · {{ $l->stock_status === 'coming_soon' ? 'first batch' : 'next batch' }} · pay when it's ready</div>
        @else
            <div class="mt-1 text-xs text-[#3F7A3A]">{{ $l->stockLabel() }}</div>
        @endif
        <form method="POST" action="{{ route('shop.update', $l) }}" class="mt-3 flex items-center gap-2" x-data data-no-busy>
            @csrf @method('PATCH')
            <label class="sr-only" for="line-{{ $l->id }}{{ ($compact ?? false) ? '-d' : '' }}">Quantity</label>
            <select id="line-{{ $l->id }}{{ ($compact ?? false) ? '-d' : '' }}" name="qty" onchange="this.form.submit()" class="field !h-9 !w-20 !text-sm !px-3">
                @for ($i = 1; $i <= max($line['qty'], min(10, $l->maxQty())); $i++)<option value="{{ $i }}" @selected($i === $line['qty'])>{{ $i }}</option>@endfor
            </select>
            <button name="qty" value="0" class="text-sm text-stone underline hover:text-char">Remove</button>
        </form>
    </div>
</div>
