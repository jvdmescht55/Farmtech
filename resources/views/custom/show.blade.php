@extends('layouts.custom')
@section('title', $device->name)
@section('eyebrow'){{ $device->location ?: 'Custom device' }} · {{ $device->last_synced_at ? 'last reading '.$device->last_synced_at->diffForHumans() : 'no readings yet' }} @endsection
@section('actions')
    @foreach ([1 => 'Day', 7 => 'Week', 30 => 'Month', 90 => '3 months'] as $d => $l)
        <a href="?days={{ $d }}" class="rounded-full px-4 h-9 inline-flex items-center text-sm border {{ $days === $d ? 'bg-char text-sand border-char' : 'bg-white border-hairline text-stone hover:text-char' }}">{{ $l }}</a>
    @endforeach
@endsection

@section('content')
@if (isset($shownToken[$device->id]))
    <div class="mb-6 rounded-2xl border border-ochre/30 bg-ochre/5 p-5 text-sm">
        <div class="font-medium text-ochre-dark">Device key. Copy it now, we only show it once:</div>
        <code class="mt-2 block break-all font-num select-all">{{ $shownToken[$device->id] }}</code>
    </div>
@endif

<div class="grid md:grid-cols-2 gap-6">
    @forelse ($charts as $c)
        <div class="panel">
            <div class="panel-head">
                <div class="panel-title">{{ $c['label'] }}</div>
                <div class="text-right">
                    <span class="font-headline text-3xl num {{ $c['out'] ? 'down' : '' }}">{{ $c['last'] ? rtrim(rtrim(number_format($c['last']->value, 2, '.', ''), '0'), '.') : '—' }}</span><span class="text-sm text-stone ml-1">{{ $c['unit'] }}</span>
                </div>
            </div>
            <div class="p-6"><x-chart.line :points="$c['series']" :unit="$c['unit'] ?? ''" :height="200" /></div>
            @if ($c['min'] !== null || $c['max'] !== null)<div class="px-6 pb-5 -mt-2 text-xs text-stone">Alert below {{ $c['min'] ?? '—' }} · above {{ $c['max'] ?? '—' }}</div>@endif
        </div>
    @empty
        <div class="panel p-10 md:col-span-2 text-stone">No readings yet. Send one and it shows up here.</div>
    @endforelse
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-6">
    <form method="POST" action="{{ route('custom.update', $device) }}" class="panel p-6 space-y-4">
        @csrf @method('PUT')
        <div class="panel-title">Settings &amp; limits</div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="field-label">Name</label><input name="name" value="{{ $device->name }}" required class="field"></div>
            <div><label class="field-label">Where</label><input name="location" value="{{ $device->location }}" class="field"></div>
        </div>
        <div class="space-y-2">
            <div class="grid grid-cols-[6rem_1fr_4.5rem_4.5rem_4.5rem] gap-2 text-[11px] uppercase tracking-wider text-stone-light"><span>Key</span><span>Label</span><span>Unit</span><span>Min</span><span>Max</span></div>
            @foreach (array_merge($device->metrics ?? [], [['key' => '', 'label' => '', 'unit' => '', 'min' => null, 'max' => null]]) as $i => $m)
                <div class="grid grid-cols-[6rem_1fr_4.5rem_4.5rem_4.5rem] gap-2">
                    <input name="metrics[{{ $i }}][key]" value="{{ $m['key'] }}" placeholder="auto" class="field !h-10 text-xs font-num">
                    <input name="metrics[{{ $i }}][label]" value="{{ $m['label'] }}" placeholder="{{ $m['key'] ? '' : 'Add a reading…' }}" class="field !h-10 text-sm">
                    <input name="metrics[{{ $i }}][unit]" value="{{ $m['unit'] }}" class="field !h-10 text-sm">
                    <input name="metrics[{{ $i }}][min]" value="{{ $m['min'] }}" type="number" step="any" class="field !h-10 text-sm font-num">
                    <input name="metrics[{{ $i }}][max]" value="{{ $m['max'] }}" type="number" step="any" class="field !h-10 text-sm font-num">
                </div>
            @endforeach
        </div>
        <button class="btn-dark">Save</button>
    </form>

    <div class="rounded-[24px] bg-char text-sand p-6 sm:p-7">
        <div class="panel-title text-sand">Sending readings</div>
        <p class="text-sm text-sand/70 mt-2">Use the keys on the left as names. Unknown names get added automatically.</p>
<pre class="mt-4 overflow-x-auto rounded-xl bg-black/30 p-4 text-[11px] leading-relaxed font-num"><code>POST {{ url('/api/v1/readings') }}
Authorization: Bearer &lt;device key&gt;

{@foreach (collect($device->metrics ?? [])->take(3) as $m)"{{ $m['key'] }}": 42{{ $loop->last ? '' : ', ' }}@endforeach}

// with a time:  {"ts": 1790798104, "readings": {...}}
// reply: {"ok":true,"readings":[{"metric":…,"alert":false}]}</code></pre>
        <div class="mt-5 flex flex-wrap gap-3 text-sm">
            <form method="POST" action="{{ route('custom.token', $device) }}">@csrf<button class="btn-line-light btn-sm">New key</button></form>
            <form method="POST" action="{{ route('custom.destroy', $device) }}" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Click again to delete'; }">@csrf @method('DELETE')<button class="btn-line-light btn-sm">Delete device</button></form>
        </div>
    </div>
</div>
@endsection
