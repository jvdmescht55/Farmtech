@extends('layouts.admin')
@section('heading', 'Store listings')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <p class="text-stone max-w-xl">Your own devices on the public store. Write it, preview it, hit <strong>Publish</strong>. Drafts are only visible to admins.</p>
    <a href="{{ route('admin.listings.create') }}" class="btn-dark btn-sm">+ New listing</a>
</div>
<div class="grid md:grid-cols-2 gap-5">
    @foreach ($listings as $l)
        <div class="panel overflow-hidden flex flex-col">
            <div class="aspect-[16/9] bg-sand-deep relative">
                @if ($src = $l->photoUrl())<img src="{{ $src }}" alt="" class="absolute inset-0 h-full w-full object-cover">@else<div class="absolute inset-0 grid place-items-center text-stone text-sm">No photos yet</div>@endif
                <span class="absolute top-4 left-4 chip {{ $l->is_published ? 'bg-[#3F7A3A] text-white' : 'bg-char text-sand' }}">{{ $l->is_published ? '● Live on the store' : 'Draft' }}</span>
                @if (! $l->images)<span class="absolute top-4 right-4 chip bg-ochre text-char">No real photos yet</span>@elseif ($l->price_cents === null)<span class="absolute top-4 right-4 chip bg-ochre text-char">No price</span>@endif
            </div>
            <div class="p-6 flex-1 flex flex-col">
                <div class="font-headline text-3xl">{{ $l->name }}</div>
                <div class="text-stone text-sm mt-1">{{ $l->tagline }}</div>
                <div class="mt-3 flex flex-wrap gap-x-5 text-sm"><span class="font-medium">{{ $l->priceLabel() ?? 'No price yet' }}{{ $l->unit_label ? ' · '.$l->unit_label : '' }}</span><span class="text-stone">{{ $l->availability }}</span>@if ($l->module)<span class="text-stone">unlocks {{ config("herd.modules.{$l->module}.name") }}</span>@endif</div>
                {{-- Stock: one click --}}
                <div class="mt-5 rounded-xl bg-sand-light p-3">
                    <div class="text-xs text-stone mb-2">Stock: <strong class="text-char">{{ $l->stockLabel() }}</strong>{{ $l->stock_qty !== null ? ' · '.$l->stock_qty.' left' : '' }}</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach (['in_stock' => 'In stock', 'low_stock' => 'Few left', 'sold_out' => 'Sold out · take reservations', 'coming_soon' => 'Coming soon'] as $k => $label)
                            <form method="POST" action="{{ route('admin.listings.stock', $l) }}">@csrf<input type="hidden" name="stock_status" value="{{ $k }}"><button class="chip h-8 px-3 border {{ $l->stock_status === $k ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char' }}">{{ $label }}</button></form>
                        @endforeach
                    </div>
                    @php($res = \App\Models\OrderItem::where('store_listing_id', $l->id)->where('is_reservation', true)->sum('quantity'))
                    @php($sold = \App\Models\OrderItem::where('store_listing_id', $l->id)->where('is_reservation', false)->sum('quantity'))
                    <div class="mt-2 text-xs text-stone">{{ $sold }} ordered · {{ $res }} reserved for the next batch · <a href="{{ route('admin.orders.index') }}" class="underline">orders</a></div>
                </div>
                <div class="mt-auto pt-6 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.listings.toggle', $l) }}">@csrf<button class="btn btn-sm {{ $l->is_published ? 'btn-line' : 'bg-[#3F7A3A] text-white hover:brightness-110' }}">{{ $l->is_published ? 'Unpublish' : 'Publish' }}</button></form>
                    <a href="{{ route('admin.listings.edit', $l) }}" class="btn-line btn-sm">Edit</a>
                    <a href="{{ route('site.product', $l) }}" target="_blank" class="btn-line btn-sm">Preview ↗</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
