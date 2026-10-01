@extends('layouts.rfid')
@section('title', $catalogue->title)
@section('eyebrow')Auction book @endsection
@section('actions')
    <a href="{{ route('rfid.catalogues.export', $catalogue) }}" class="btn-secondary hidden sm:inline-flex">CSV</a>
    <a href="{{ route('rfid.catalogues.print', $catalogue) }}" target="_blank" class="btn-primary">Print</a>
@endsection
@section('content')
@php
    $numbered = $lots->isNotEmpty() && $lots->every(fn ($l) => filled($l->lot_number));
    $step = $lots->isEmpty() ? 1 : ($numbered ? 3 : 2);
@endphp
<ol class="grid sm:grid-cols-3 gap-3 mb-8">
    @foreach ([1 => ['Pick the animals', 'Tick them on the right'], 2 => ['Number the lots', '66A, 66B… in one click'], 3 => ['Print it', 'Logix layout, ready for the sale']] as $n => [$t, $d])
        <li class="rounded-2xl border p-4 flex items-center gap-4 {{ $step === $n ? 'border-char bg-white' : ($step > $n ? 'border-hairline bg-white' : 'border-hairline') }}">
            <span class="w-9 h-9 shrink-0 rounded-full grid place-items-center text-sm font-semibold {{ $step > $n ? 'bg-[#3F7A3A] text-white' : ($step === $n ? 'bg-char text-sand' : 'bg-sand-deep text-stone') }}">{{ $step > $n ? '✓' : $n }}</span>
            <div><div class="font-medium">{{ $t }}</div><div class="text-xs text-stone">{{ $d }}</div></div>
            @if ($n === 3 && $step === 3)<a href="{{ route('rfid.catalogues.print', $catalogue) }}" target="_blank" class="ml-auto btn-dark btn-sm">Print</a>@endif
        </li>
    @endforeach
</ol>
<div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="app-card overflow-hidden">
            <div class="px-6 py-4 border-b border-hairline flex flex-wrap items-center gap-4">
                <h2 class="font-medium">Lots <span class="text-stone-light font-normal">({{ $lots->count() }})</span></h2>
                <form method="POST" action="{{ route('rfid.catalogues.number', $catalogue) }}" class="ml-auto flex flex-wrap items-end gap-2 text-sm">
                    @csrf
                    <div><label class="app-label !mb-0.5">Start at</label><input type="number" name="start" value="{{ (int) ($lots->first()?->lot_number ?: 1) }}" min="1" class="app-input w-20 !py-1.5"></div>
                    <div><label class="app-label !mb-0.5">Per lot</label>
                        <select name="per_lot" class="app-input !py-1.5">@foreach ([1 => '1 (66, 67…)', 2 => '2 (A–B)', 3 => '3 (A–C)', 4 => '4 (A–D)', 5 => '5 (A–E)', 6 => '6 (A–F)'] as $n => $l)<option value="{{ $n }}" @selected($n===4)>{{ $l }}</option>@endforeach</select></div>
                    <div><label class="app-label !mb-0.5">Order</label>
                        <select name="order" class="app-input !py-1.5"><option value="current">As listed</option><option value="visual_id">Animal ID</option><option value="birth_date">Oldest first</option></select></div>
                    <button class="btn-secondary !py-1.5">Number the lots</button>
                </form>
            </div>
            @if ($lots->isEmpty())
                <p class="px-6 py-12 text-center text-stone">No animals yet — pick them from the herd on the right.</p>
            @else
                <form method="POST" action="{{ route('rfid.catalogues.lots.update', $catalogue) }}">
                    @csrf @method('PUT')
                    <div class="overflow-x-auto">
                        <table class="app-table">
                            <thead><tr><th class="w-16">#</th><th class="w-24">Lot</th><th>Animal</th><th>Tier</th><th>Born</th><th>Sire × Dam</th><th>Comment</th><th></th></tr></thead>
                            <tbody>
                            @foreach ($lots as $lot)
                                @php($a = $lot->animal)
                                <tr>
                                    <td><input type="number" name="lots[{{ $lot->id }}][position]" value="{{ $lot->position }}" class="app-input !px-2 !py-1 w-16 font-mono text-xs"></td>
                                    <td><input name="lots[{{ $lot->id }}][lot_number]" value="{{ $lot->lot_number }}" class="app-input !px-2 !py-1 w-20 font-mono font-bold"></td>
                                    <td class="whitespace-nowrap"><a href="{{ route('rfid.animals.show', $a) }}" class="font-mono font-medium text-char hover:underline">{{ $a->visual_id }}</a><div class="text-[11px] text-stone-light">{{ $a->sexLabel() }}{{ $a->registered ? ' · REG' : '' }}</div></td>
                                    <td><x-tier :tier="$tiers->resolve($a)->tier" /></td>
                                    <td class="whitespace-nowrap text-xs">{{ $a->birth_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="font-mono text-[11px] whitespace-nowrap">{{ $a->sire?->visual_id ?? '?' }}<br>{{ $a->dam?->visual_id ?? '?' }}</td>
                                    <td><input name="lots[{{ $lot->id }}][comment]" value="{{ $lot->comment }}" class="app-input !py-1 text-xs min-w-[14rem]"></td>
                                    <td><button form="remove-{{ $lot->id }}" class="text-stone-light hover:text-[#B0452F] text-lg leading-none" title="Take out of the book">&times;</button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 bg-sand-light"><button class="btn-primary">Save lots</button></div>
                </form>
                @foreach ($lots as $lot)
                    <form id="remove-{{ $lot->id }}" method="POST" action="{{ route('rfid.catalogues.lots.remove', [$catalogue, $lot]) }}" class="hidden">@csrf @method('DELETE')</form>
                @endforeach
            @endif
        </div>

        <details class="app-card p-6">
            <summary class="cursor-pointer font-medium">Book details</summary>
            <form method="POST" action="{{ route('rfid.catalogues.update', $catalogue) }}" class="mt-5 space-y-4">
                @csrf @method('PUT')
                @include('rfid.catalogues._details', ['c' => $catalogue])
                <button class="btn-primary">Save</button>
            </form>
            <form method="POST" action="{{ route('rfid.catalogues.destroy', $catalogue) }}" class="mt-6 pt-6 border-t border-hairline" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Click again to delete it'; }">
                @csrf @method('DELETE')
                <button class="btn-danger">Delete this book</button>
            </form>
        </details>
    </div>

    <div class="app-card overflow-hidden self-start" x-data="{ all: false }">
        <div class="px-5 py-4 border-b border-hairline">
            <h2 class="font-medium">Add from the herd</h2>
            <form method="GET" class="mt-3 flex gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="Search ID / tag" class="app-input !py-1.5">
                <select name="sex" class="app-input !py-1.5 w-24"><option value="">All</option><option value="F" @selected(request('sex')==='F')>Female</option><option value="M" @selected(request('sex')==='M')>Male</option></select>
                <button class="btn-secondary !py-1.5">Go</button>
            </form>
        </div>
        <form method="POST" action="{{ route('rfid.catalogues.lots.add', $catalogue) }}">
            @csrf
            <div class="max-h-[32rem] overflow-y-auto divide-y divide-border">
                @forelse ($candidates->take(300) as $a)
                    <label class="flex items-center gap-3 px-5 py-2 text-sm hover:bg-sand-light cursor-pointer">
                        <input type="checkbox" name="animal_ids[]" value="{{ $a->id }}" :checked="all" class="rounded border-hairline text-char">
                        <span class="font-mono font-medium">{{ $a->visual_id }}</span>
                        <span class="text-xs text-stone-light">{{ $a->sexLabel() }} · {{ $a->birth_date?->format('m/Y') }}</span>
                        <x-tier :tier="$tiers->resolve($a)->tier" class="ml-auto" />
                    </label>
                @empty
                    <p class="px-5 py-8 text-sm text-stone text-center">No more active animals to add.</p>
                @endforelse
            </div>
            @if ($candidates->isNotEmpty())
                <div class="px-5 py-3 border-t border-hairline flex items-center justify-between bg-sand-light">
                    <label class="text-xs flex items-center gap-2"><input type="checkbox" x-model="all" class="rounded border-hairline"> Select all{{ $candidates->count() > 300 ? ' (first 300)' : '' }}</label>
                    <button class="btn-primary !py-1.5">Add selected</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection
