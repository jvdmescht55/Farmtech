{{-- Responsive SVG line/area chart. $points: [['label' => '2026-09-28', 'value' => 42.8], ...] --}}
@props(['points' => [], 'unit' => '', 'height' => 220, 'dark' => false, 'target' => null])
@php
    $pts = collect($points)->filter(fn ($p) => $p['value'] !== null)->values();
    $W = 800; $H = $height; $padL = 44; $padR = 16; $padT = 16; $padB = 28;
    $vals = $pts->pluck('value')->push($target)->filter(fn ($v) => $v !== null);
    $min = $vals->min(); $max = $vals->max();
    if ($min === $max) { $min -= 1; $max += 1; }
    $span = $max - $min; $min -= $span * 0.12; $max += $span * 0.08;
    $x = fn ($i) => $pts->count() > 1 ? $padL + $i * ($W - $padL - $padR) / ($pts->count() - 1) : $W / 2;
    $y = fn ($v) => $padT + ($max - $v) / ($max - $min) * ($H - $padT - $padB);
    $line = $pts->map(fn ($p, $i) => round($x($i), 1).','.round($y($p['value']), 1))->implode(' L');
    $gid = 'g'.substr(md5(json_encode($points).$unit), 0, 6);
    $ticks = collect(range(0, 3))->map(fn ($k) => $min + ($max - $min) * $k / 3);
    $stroke = '#B8732E';
    $muted = $dark ? 'rgba(244,241,234,.35)' : '#918C80';
    $grid = $dark ? 'rgba(244,241,234,.08)' : '#EEE9DE';
@endphp
@if ($pts->isEmpty())
    <div class="grid place-items-center text-sm text-stone" style="height: {{ $H }}px">Not enough data yet.</div>
@else
<svg viewBox="0 0 {{ $W }} {{ $H }}" class="w-full h-auto" role="img" aria-label="Chart">
    <defs><linearGradient id="{{ $gid }}" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="{{ $stroke }}" stop-opacity=".22"/><stop offset="1" stop-color="{{ $stroke }}" stop-opacity="0"/></linearGradient></defs>
    @foreach ($ticks as $t)
        <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ round($y($t), 1) }}" y2="{{ round($y($t), 1) }}" stroke="{{ $grid }}"/>
        <text x="{{ $padL - 8 }}" y="{{ round($y($t), 1) + 4 }}" text-anchor="end" font-size="11" fill="{{ $muted }}" font-family="Geist Mono, monospace">{{ round($t) }}</text>
    @endforeach
    @if ($target)
        <line x1="{{ $padL }}" x2="{{ $W - $padR }}" y1="{{ round($y($target), 1) }}" y2="{{ round($y($target), 1) }}" stroke="#15140F" stroke-dasharray="4 5" stroke-opacity=".5"/>
        <text x="{{ $W - $padR }}" y="{{ round($y($target), 1) - 6 }}" text-anchor="end" font-size="11" fill="{{ $muted }}">target {{ $target }} {{ $unit }}</text>
    @endif
    @if ($pts->count() > 1)
        <path d="M{{ $line }} L{{ round($x($pts->count() - 1), 1) }},{{ $H - $padB }} L{{ $padL }},{{ $H - $padB }}Z" fill="url(#{{ $gid }})"/>
        <path d="M{{ $line }}" fill="none" stroke="{{ $stroke }}" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
    @endif
    @foreach ($pts as $i => $p)
        <g>
            <circle cx="{{ round($x($i), 1) }}" cy="{{ round($y($p['value']), 1) }}" r="4" fill="{{ $dark ? '#1E2A1E' : '#fff' }}" stroke="{{ $stroke }}" stroke-width="2"><title>{{ $p['label'] }}: {{ $p['value'] }} {{ $unit }}</title></circle>
        </g>
    @endforeach
    @php($labelEvery = max(1, (int) ceil($pts->count() / 7)))
    @foreach ($pts as $i => $p)
        @if ($i % $labelEvery === 0 || $i === $pts->count() - 1)
            <text x="{{ round($x($i), 1) }}" y="{{ $H - 8 }}" text-anchor="middle" font-size="11" fill="{{ $muted }}">{{ strlen($p['label']) === 10 ? \Carbon\Carbon::parse($p['label'])->format('j M') : (strlen($p['label']) === 16 ? \Carbon\Carbon::parse($p['label'])->format('j M H:i') : $p['label']) }}</text>
        @endif
    @endforeach
</svg>
@endif
