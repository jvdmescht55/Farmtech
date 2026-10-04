@extends('layouts.admin')
@section('heading', $listing->exists ? 'Edit · '.$listing->name : 'New listing')
@section('content')
@php
    $lines = fn ($rows, $a, $b) => collect($rows ?? [])->map(fn ($r) => $r[$a].($r[$b] !== '' && $r[$b] !== null ? ' | '.$r[$b] : ''))->implode("\n");
@endphp
<div class="grid xl:grid-cols-3 gap-6">
    <form method="POST" action="{{ $listing->exists ? route('admin.listings.update', $listing) : route('admin.listings.store') }}" class="xl:col-span-2 panel p-6 sm:p-8 space-y-5">
        @csrf @if ($listing->exists) @method('PUT') @endif
        <div class="grid sm:grid-cols-2 gap-4">
            <div><label class="field-label">Name *</label><input name="name" value="{{ old('name', $listing->name) }}" required class="field"></div>
            <div><label class="field-label">Web address</label><div class="flex items-center gap-1 text-sm text-stone">/store/<input name="slug" value="{{ old('slug', $listing->slug) }}" placeholder="auto" class="field font-num"></div></div>
            <div class="sm:col-span-2"><label class="field-label">Tagline</label><input name="tagline" value="{{ old('tagline', $listing->tagline) }}" class="field"></div>
            <div><label class="field-label">Price (R, incl. VAT)</label><input name="price" type="number" step="0.01" value="{{ old('price', $listing->price_cents !== null ? $listing->price_cents / 100 : '') }}" placeholder="leave empty = register interest" class="field font-num"></div>
            <div><label class="field-label">Availability</label><input name="availability" value="{{ old('availability', $listing->availability) }}" placeholder="e.g. Built to order" class="field"></div>
            <div><label class="field-label">Sold per (optional)</label><input name="unit_label" value="{{ old('unit_label', $listing->unit_label) }}" placeholder="e.g. Pack of 50" class="field"></div>
            <div><label class="field-label">Was-price (R, optional)</label><input name="compare_at" type="number" step="0.01" value="{{ old('compare_at', $listing->compare_at_cents !== null ? $listing->compare_at_cents / 100 : '') }}" class="field font-num"></div>
            <div><label class="field-label">Stock</label>
                <select name="stock_status" class="field">@foreach (\App\Models\StoreListing::STOCK as $k => $label)<option value="{{ $k }}" @selected(old('stock_status', $listing->stock_status ?? 'in_stock') === $k)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="field-label">Units on hand <span class="font-normal text-stone">(empty = built to order)</span></label><input name="stock_qty" type="number" min="0" value="{{ old('stock_qty', $listing->stock_qty) }}" class="field font-num"></div>
            <div class="sm:col-span-2"><label class="field-label">Next batch note <span class="font-normal text-stone">(shown when sold out / coming soon)</span></label><input name="next_batch" value="{{ old('next_batch', $listing->next_batch) }}" placeholder="e.g. Next batch ships mid-November. Reserve yours, pay when it's ready." class="field"></div>
            <div><label class="field-label">Badge (optional)</label><input name="badge" value="{{ old('badge', $listing->badge) }}" placeholder="e.g. New, Best value" class="field"></div>
            <div><label class="field-label">Max per order</label><input name="max_per_order" type="number" min="1" value="{{ old('max_per_order', $listing->max_per_order) }}" placeholder="20" class="field font-num"></div>
            <div class="sm:col-span-2"><label class="field-label">Picture until you upload photos</label>
                <select name="photo_key" class="field"><option value="">None</option>@foreach (\App\Support\SiteImages::IMAGES as $k => $img)<option value="{{ $k }}" @selected(old('photo_key', $listing->photo_key) === $k)>{{ $img['alt'] }}</option>@endforeach</select></div>
            <div><label class="field-label">Category</label><input name="category" value="{{ old('category', $listing->category) }}" class="field"></div>
            <div><label class="field-label">Unlocks software</label><select name="module" class="field"><option value="">—</option>@foreach (config('herd.modules') as $k => $m)<option value="{{ $k }}" @selected(old('module', $listing->module) === $k)>{{ $m['name'] }}</option>@endforeach</select></div>
        </div>
        <div><label class="field-label">Overview <span class="font-normal text-stone-light">(blank line = new paragraph)</span></label><textarea name="overview" rows="6" class="field">{{ old('overview', $listing->overview) }}</textarea></div>
        <div><label class="field-label">Features <span class="font-normal text-stone-light">— one per line: <span class="font-num">Title | description</span></span></label><textarea name="features" rows="7" class="field font-num text-sm">{{ old('features', $lines($listing->features, 'title', 'body')) }}</textarea></div>
        <div><label class="field-label">Specifications <span class="font-normal text-stone-light">— one per line: <span class="font-num">Label | value</span></span></label><textarea name="specs" rows="7" class="field font-num text-sm">{{ old('specs', $lines($listing->specs, 'label', 'value')) }}</textarea></div>
        <div><label class="field-label">In the box <span class="font-normal text-stone-light">— one item per line</span></label><textarea name="in_box" rows="4" class="field">{{ old('in_box', implode("\n", $listing->in_box ?? [])) }}</textarea></div>
        <div><label class="field-label">Why choose it</label><textarea name="why" rows="3" class="field">{{ old('why', $listing->why) }}</textarea></div>
        <div class="flex items-center gap-3"><label class="field-label !mb-0">Order on the store</label><input name="sort" type="number" min="0" value="{{ old('sort', $listing->sort) }}" class="field w-24 font-num"></div>
        <div class="flex gap-3"><button class="btn-dark">Save</button>@if ($listing->exists)<a href="{{ route('site.product', $listing) }}" target="_blank" class="btn-line">Preview ↗</a>@endif</div>
    </form>

    @if ($listing->exists)
        <div class="space-y-6">
            <div class="panel p-6">
                <div class="panel-title">Status</div>
                <p class="text-sm text-stone mt-2">{{ $listing->is_published ? 'Live on /store — everyone can see it.' : 'Draft — only admins can see it.' }}</p>
                <form method="POST" action="{{ route('admin.listings.toggle', $listing) }}" class="mt-4">@csrf<button class="btn w-full {{ $listing->is_published ? 'btn-line' : 'bg-[#3F7A3A] text-white hover:brightness-110' }}">{{ $listing->is_published ? 'Unpublish' : 'Publish to the store' }}</button></form>
            </div>
            <div class="panel p-6">
                <div class="panel-title">Photos</div>
                <div class="grid grid-cols-3 gap-2 mt-4">
                    @foreach ($listing->images ?? [] as $img)
                        <form method="POST" action="{{ route('admin.listings.images.destroy', $listing) }}" class="relative group">
                            @csrf @method('DELETE')<input type="hidden" name="path" value="{{ $img }}">
                            <img src="{{ asset('storage/'.$img) }}" class="aspect-square w-full object-cover rounded-lg" alt="">
                            <button class="absolute top-1 right-1 w-6 h-6 rounded-full bg-char text-sand text-xs opacity-0 group-hover:opacity-100" title="Remove">×</button>
                        </form>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('admin.listings.images.store', $listing) }}" enctype="multipart/form-data" class="mt-4 space-y-2">
                    @csrf
                    <input type="file" name="image" accept="image/*" required class="field !h-auto py-2 text-sm">
                    <button class="btn-line btn-sm w-full">Upload photo</button>
                </form>
                <p class="text-xs text-stone mt-2">The first photo is the main one. Landscape works best.</p>
            </div>
            <div class="rounded-2xl border border-ochre/30 bg-ochre/5 p-5 text-sm">
                <div class="font-medium">Before you publish a radio device</div>
                <p class="text-stone mt-1">Devices with Wi-Fi and RFID must have ICASA type approval before they're sold in SA. Add the approval number in <span class="font-num">.env</span> (<span class="font-num">LEGAL_ICASA_APPROVAL</span>) so it shows on the legal page.</p>
            </div>
        </div>
    @endif
</div>
@endsection
