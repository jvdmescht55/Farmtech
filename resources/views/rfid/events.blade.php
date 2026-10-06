@extends('layouts.rfid')
@section('title', 'Records')
@section('eyebrow')Treatments, matings, births, sales @endsection
@section('actions')<a href="{{ route('rfid.data', ['export' => 'events']) }}" class="btn-secondary">CSV</a>@endsection

@section('content')
<div class="grid xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 panel overflow-hidden">
        <div class="panel-head">
            <div class="panel-title">History</div>
            <form method="GET"><select name="type" class="field !h-9 !text-sm w-52" onchange="this.form.submit()"><option value="">Everything</option>@foreach (config('herd.event_types') as $k => $t)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $t['label'] }}</option>@endforeach</select></form>
        </div>
        <ul class="sm:hidden divide-y divide-hairline">
            @forelse ($events as $e)
                <li class="px-4 py-3.5 flex items-start gap-3">
                    <span class="w-14 shrink-0 text-xs text-stone num pt-0.5">{{ $e->date->format('j M') }}<br>{{ $e->date->format('Y') }}</span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm"><span class="font-medium">{{ $e->label() }}</span> · <a href="{{ route('rfid.animals.show', $e->animal) }}" class="font-num link-u">{{ $e->animal->visual_id }}</a></span>
                        <span class="block text-xs text-stone mt-0.5">{{ collect([$e->product, $e->dose, $e->mate ? 'with '.$e->mate->visual_id : null, $e->count !== null ? $e->count.' born' : null, $e->result ? (config("herd.event_types.pregnancy_scan.result.{$e->result}") ?? $e->result) : null, $e->notes])->filter()->implode(' · ') }}</span>
                        @if ($e->withdrawal_until && $e->withdrawal_until->isFuture())<span class="block text-xs text-ochre-dark mt-0.5">Withdrawal until {{ $e->withdrawal_until->format('d/m/Y') }}</span>@endif
                    </span>
                </li>
            @empty
                <li class="py-14 text-center text-stone text-sm px-6">Nothing recorded yet. Use “Record something” above.</li>
            @endforelse
        </ul>
        <div class="hidden sm:block overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Date</th><th>Animal</th><th>What</th><th>Details</th><th>Withdrawal</th><th></th></tr></thead>
                <tbody>
                @forelse ($events as $e)
                    <tr>
                        <td class="num whitespace-nowrap">{{ $e->date->format('d/m/Y') }}</td>
                        <td><a href="{{ route('rfid.animals.show', $e->animal) }}" class="font-num font-medium link-u">{{ $e->animal->visual_id }}</a></td>
                        <td>{{ $e->label() }}</td>
                        <td class="text-stone text-sm">{{ collect([$e->product, $e->dose, $e->mate ? 'with '.$e->mate->visual_id : null, $e->count !== null ? $e->count.' born' : null, $e->result ? (config("herd.event_types.pregnancy_scan.result.{$e->result}") ?? $e->result) : null, $e->notes])->filter()->implode(' · ') }}</td>
                        <td class="num text-sm {{ $e->withdrawal_until && $e->withdrawal_until->isFuture() ? 'text-ochre-dark' : 'text-stone-light' }}">{{ $e->withdrawal_until?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-right"><form method="POST" action="{{ route('rfid.events.destroy', $e) }}" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Sure?'; }">@csrf @method('DELETE')<button class="text-xs text-stone hover:text-[#B0452F]">Remove</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-16 text-center text-stone">Nothing recorded yet. Use “Record something”, or bring a CSV in under Import & export.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $events->links() }}</div>
    </div>

    <div class="panel self-start order-first xl:order-none" x-data="{ type: '{{ old('type', 'treatment') }}', q: '' }">
        <div class="panel-head"><div class="panel-title">Record something</div></div>
        <form method="POST" action="{{ route('rfid.events.store') }}" class="p-6 space-y-4">
            @csrf
            <div><label class="field-label">What happened</label>
                <select name="type" x-model="type" class="field">@foreach (config('herd.event_types') as $k => $t)<option value="{{ $k }}">{{ $t['label'] }}</option>@endforeach</select></div>
            <div><label class="field-label">Date</label><input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="field"></div>
            <div>
                <label class="field-label">Animals <span class="text-stone-light font-normal">(tick one or many)</span></label>
                <input x-model="q" placeholder="Search ID…" class="field !h-9 mb-2 font-num text-sm">
                <div class="max-h-52 overflow-y-auto rounded-xl border border-hairline divide-y divide-hairline">
                    @foreach ($animals as $a)
                        <label class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-sand-light cursor-pointer" x-show="!q || '{{ strtolower($a->visual_id) }}'.includes(q.toLowerCase())">
                            <input type="checkbox" name="animal_ids[]" value="{{ $a->id }}" class="rounded border-hairline text-char" @checked((int) request('animal') === $a->id || in_array($a->id, old('animal_ids', [request('animal')])))>
                            <span class="font-num">{{ $a->visual_id }}</span><span class="text-xs text-stone">{{ $a->sexLabel() }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <template x-if="['treatment','vaccination','dosing'].includes(type)">
                <div class="grid grid-cols-2 gap-3">
                    <div class="col-span-2"><label class="field-label">Product</label><input name="product" class="field" placeholder="e.g. Closantel"></div>
                    <div><label class="field-label">Dose</label><input name="dose" class="field" placeholder="5 ml"></div>
                    <div><label class="field-label">Withdrawal (days)</label><input type="number" name="withdrawal_days" min="0" class="field font-num" placeholder="28"></div>
                </div>
            </template>
            <template x-if="type === 'mating'">
                <div><label class="field-label">Ram / bull</label><select name="mate_id" class="field"><option value="">—</option>@foreach ($males as $m)<option value="{{ $m->id }}">{{ $m->visual_id }}</option>@endforeach</select></div>
            </template>
            <template x-if="type === 'pregnancy_scan'">
                <div><label class="field-label">Result</label><select name="result" class="field">@foreach (config('herd.event_types.pregnancy_scan.result') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
            </template>
            <template x-if="type === 'birth'">
                <div><label class="field-label">How many born</label><input type="number" name="count" min="0" max="10" value="1" class="field font-num"></div>
            </template>
            <div><label class="field-label">Notes</label><textarea name="notes" rows="2" class="field"></textarea></div>
            <p class="text-xs text-stone" x-show="['sale','death','cull'].includes(type)">The animal's status updates by itself.</p>
            <button class="btn-dark w-full">Save</button>
        </form>
    </div>
</div>
@endsection
