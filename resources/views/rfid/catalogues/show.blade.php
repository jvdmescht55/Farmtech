@extends('layouts.rfid')
@section('title', $catalogue->title)
@section('eyebrow')Veilingkatalogus @endsection
@section('actions')
    <a href="{{ route('rfid.catalogues.export', $catalogue) }}" class="btn-secondary hidden sm:inline-flex">CSV</a>
    <a href="{{ route('rfid.catalogues.print', $catalogue) }}" target="_blank" class="btn-primary">Druk katalogus</a>
@endsection
@section('content')
<div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="app-card overflow-hidden">
            <div class="px-6 py-4 border-b border-hairline flex flex-wrap items-center gap-4">
                <h2 class="font-medium">Lotte <span class="text-stone-light font-normal">({{ $lots->count() }})</span></h2>
                <form method="POST" action="{{ route('rfid.catalogues.number', $catalogue) }}" class="ml-auto flex flex-wrap items-end gap-2 text-sm">
                    @csrf
                    <div><label class="app-label !mb-0.5">Begin by</label><input type="number" name="start" value="{{ (int) ($lots->first()?->lot_number ?: 1) }}" min="1" class="app-input w-20 !py-1.5"></div>
                    <div><label class="app-label !mb-0.5">Per lot</label>
                        <select name="per_lot" class="app-input !py-1.5">@foreach ([1 => '1 (66, 67…)', 2 => '2 (A–B)', 3 => '3 (A–C)', 4 => '4 (A–D)', 5 => '5 (A–E)', 6 => '6 (A–F)'] as $n => $l)<option value="{{ $n }}" @selected($n===4)>{{ $l }}</option>@endforeach</select></div>
                    <div><label class="app-label !mb-0.5">Volgorde</label>
                        <select name="order" class="app-input !py-1.5"><option value="current">Huidige volgorde</option><option value="visual_id">Dier-ID</option><option value="birth_date">Oudste eerste</option></select></div>
                    <button class="btn-secondary !py-1.5">Nommer lotte</button>
                </form>
            </div>
            @if ($lots->isEmpty())
                <p class="px-6 py-12 text-center text-stone">Nog geen diere nie — kies hulle regs uit die kudde.</p>
            @else
                <form method="POST" action="{{ route('rfid.catalogues.lots.update', $catalogue) }}">
                    @csrf @method('PUT')
                    <div class="overflow-x-auto">
                        <table class="app-table">
                            <thead><tr><th class="w-16">#</th><th class="w-24">Lot</th><th>Dier</th><th>Vlak</th><th>Gebore</th><th>Vaar × Moer</th><th>Opmerking</th><th></th></tr></thead>
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
                                    <td><button form="remove-{{ $lot->id }}" class="text-stone-light hover:text-[#B0452F] text-lg leading-none" title="Haal uit katalogus">&times;</button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-4 bg-sand-light"><button class="btn-primary">Stoor lotte</button></div>
                </form>
                @foreach ($lots as $lot)
                    <form id="remove-{{ $lot->id }}" method="POST" action="{{ route('rfid.catalogues.lots.remove', [$catalogue, $lot]) }}" class="hidden">@csrf @method('DELETE')</form>
                @endforeach
            @endif
        </div>

        <details class="app-card p-6">
            <summary class="cursor-pointer font-medium">Katalogus-besonderhede</summary>
            <form method="POST" action="{{ route('rfid.catalogues.update', $catalogue) }}" class="mt-5 space-y-4">
                @csrf @method('PUT')
                @include('rfid.catalogues._details', ['c' => $catalogue])
                <button class="btn-primary">Stoor</button>
            </form>
            <form method="POST" action="{{ route('rfid.catalogues.destroy', $catalogue) }}" class="mt-6 pt-6 border-t border-hairline" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Klik weer om die katalogus te verwyder'; }">
                @csrf @method('DELETE')
                <button class="btn-danger">Verwyder katalogus</button>
            </form>
        </details>
    </div>

    <div class="app-card overflow-hidden self-start" x-data="{ all: false }">
        <div class="px-5 py-4 border-b border-hairline">
            <h2 class="font-medium">Voeg by uit die kudde</h2>
            <form method="GET" class="mt-3 flex gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="Soek ID / EID" class="app-input !py-1.5">
                <select name="sex" class="app-input !py-1.5 w-24"><option value="">Almal</option><option value="F" @selected(request('sex')==='F')>Vroulik</option><option value="M" @selected(request('sex')==='M')>Manlik</option></select>
                <button class="btn-secondary !py-1.5">Soek</button>
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
                    <p class="px-5 py-8 text-sm text-stone text-center">Geen aktiewe diere oor om by te voeg nie.</p>
                @endforelse
            </div>
            @if ($candidates->isNotEmpty())
                <div class="px-5 py-3 border-t border-hairline flex items-center justify-between bg-sand-light">
                    <label class="text-xs flex items-center gap-2"><input type="checkbox" x-model="all" class="rounded border-hairline"> Kies almal{{ $candidates->count() > 300 ? ' (eerste 300)' : '' }}</label>
                    <button class="btn-primary !py-1.5">Voeg gekose by</button>
                </div>
            @endif
        </form>
    </div>
</div>
@endsection
