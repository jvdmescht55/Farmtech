@extends('layouts.rfid')
@section('title', 'Logboek')
@section('eyebrow')Behandelings, paring, geboortes, verkope @endsection
@section('actions')<a href="{{ route('rfid.data', ['export' => 'events']) }}" class="btn-secondary">CSV</a>@endsection

@section('content')
<div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 panel overflow-hidden">
        <div class="panel-head">
            <div class="panel-title">Inskrywings</div>
            <form method="GET"><select name="type" class="field !h-9 !text-sm w-52" onchange="this.form.submit()"><option value="">Alle tipes</option>@foreach (config('herd.event_types') as $k => $t)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $t['label'] }}</option>@endforeach</select></form>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Datum</th><th>Dier</th><th>Tipe</th><th>Besonderhede</th><th>Onttrekking</th><th></th></tr></thead>
                <tbody>
                @forelse ($events as $e)
                    <tr>
                        <td class="num whitespace-nowrap">{{ $e->date->format('d/m/Y') }}</td>
                        <td><a href="{{ route('rfid.animals.show', $e->animal) }}" class="font-num font-medium link-u">{{ $e->animal->visual_id }}</a></td>
                        <td>{{ $e->label() }}</td>
                        <td class="text-stone text-sm">{{ collect([$e->product, $e->dose, $e->mate ? 'met '.$e->mate->visual_id : null, $e->count !== null ? $e->count.' gebore' : null, $e->result ? (config("herd.event_types.pregnancy_scan.result.{$e->result}") ?? $e->result) : null, $e->notes])->filter()->implode(' · ') }}</td>
                        <td class="num text-sm {{ $e->withdrawal_until && $e->withdrawal_until->isFuture() ? 'text-ochre-dark' : 'text-stone-light' }}">{{ $e->withdrawal_until?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-right"><form method="POST" action="{{ route('rfid.events.destroy', $e) }}" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Seker?'; }">@csrf @method('DELETE')<button class="text-xs text-stone hover:text-[#B0452F]">Verwyder</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-16 text-center text-stone">Nog niks aangeteken nie. Begin regs — of voer 'n CSV in onder Data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $events->links() }}</div>
    </div>

    <div class="panel self-start" x-data="{ type: '{{ old('type', 'treatment') }}', q: '' }">
        <div class="panel-head"><div class="panel-title">Nuwe inskrywing</div></div>
        <form method="POST" action="{{ route('rfid.events.store') }}" class="p-6 space-y-4">
            @csrf
            <div><label class="field-label">Tipe</label>
                <select name="type" x-model="type" class="field">@foreach (config('herd.event_types') as $k => $t)<option value="{{ $k }}">{{ $t['label'] }}</option>@endforeach</select></div>
            <div><label class="field-label">Datum</label><input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="field"></div>
            <div>
                <label class="field-label">Diere <span class="text-stone-light font-normal">(kies een of meer)</span></label>
                <input x-model="q" placeholder="Soek ID…" class="field !h-9 mb-2 font-num text-sm">
                <div class="max-h-52 overflow-y-auto rounded-xl border border-hairline divide-y divide-hairline">
                    @foreach ($animals as $a)
                        <label class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-sand-light cursor-pointer" x-show="!q || '{{ strtolower($a->visual_id) }}'.includes(q.toLowerCase())">
                            <input type="checkbox" name="animal_ids[]" value="{{ $a->id }}" class="rounded border-hairline text-char" @checked(in_array($a->id, old('animal_ids', [request('animal')])))>
                            <span class="font-num">{{ $a->visual_id }}</span><span class="text-xs text-stone">{{ $a->sexLabel() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <template x-if="['treatment','vaccination','dosing'].includes(type)">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2"><label class="field-label">Produk</label><input name="product" class="field" placeholder="bv. Closantel"></div>
                    <div><label class="field-label">Dosis</label><input name="dose" class="field" placeholder="5 ml"></div>
                    <div><label class="field-label">Onttrekking (dae)</label><input type="number" name="withdrawal_days" min="0" class="field font-num" placeholder="28"></div>
                </div>
            </template>
            <template x-if="type === 'mating'">
                <div><label class="field-label">Ram / bul</label><select name="mate_id" class="field"><option value="">—</option>@foreach ($males as $m)<option value="{{ $m->id }}">{{ $m->visual_id }}</option>@endforeach</select></div>
            </template>
            <template x-if="type === 'pregnancy_scan'">
                <div><label class="field-label">Uitslag</label><select name="result" class="field">@foreach (config('herd.event_types.pregnancy_scan.result') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            </template>
            <template x-if="type === 'birth'">
                <div><label class="field-label">Aantal gebore</label><input type="number" name="count" min="0" max="10" value="1" class="field font-num"></div>
            </template>
            <div><label class="field-label">Notas</label><textarea name="notes" rows="2" class="field"></textarea></div>
            <p class="text-xs text-stone" x-show="['sale','death','cull'].includes(type)">Die dier se status word outomaties opgedateer.</p>
            <button class="btn-dark w-full">Teken aan</button>
        </form>
    </div>
</div>
@endsection
