@extends('layouts.rfid')
@section('title', 'Waarskuwings')
@section('eyebrow')Die stelsel hou jou kudde dop — dag en nag @endsection
@section('actions')<a href="{{ route('rfid.alerts.export') }}" class="btn-secondary">CSV</a>@endsection

@php
    $sev = [
        'critical' => ['Dringend', 'bg-[#B0452F]', 'bg-[#B0452F]/8 border-[#B0452F]/25', 'text-[#B0452F]'],
        'warning' => ['Hierdie week', 'bg-ochre', 'bg-ochre/8 border-ochre/25', 'text-ochre-dark'],
        'info' => ['Om te weet', 'bg-stone-light', 'bg-white border-hairline', 'text-stone'],
    ];
@endphp

@section('content')
<div class="grid sm:grid-cols-3 gap-4">
    @foreach ($sev as $k => [$label, $dot])
        <a href="{{ route('rfid.alerts', request('severity') === $k ? [] : ['severity' => $k]) }}" class="panel p-6 flex items-center justify-between transition hover:border-char {{ request('severity') === $k ? 'border-char' : '' }}">
            <div><div class="kpi-label flex items-center gap-2"><span class="w-2 h-2 rounded-full {{ $dot }}"></span>{{ $label }}</div><div class="kpi-num mt-3">{{ $counts[$k] ?? 0 }}</div></div>
            <span class="text-stone-light">→</span>
        </a>
    @endforeach
</div>

<div class="mt-6 flex flex-wrap gap-2">
    <a href="{{ route('rfid.alerts', array_filter(['severity' => request('severity')])) }}" class="chip h-9 px-4 border {{ ! request('category') ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char' }}">Alles</a>
    @foreach (\App\Services\Herd\HerdAlerts::CATEGORIES as $k => $l)
        @if ($byCategory[$k] ?? 0)
            <a href="{{ route('rfid.alerts', array_filter(['severity' => request('severity'), 'category' => $k])) }}" class="chip h-9 px-4 border {{ request('category') === $k ? 'bg-char text-sand border-char' : 'bg-white border-hairline hover:border-char' }}">{{ $l }} <span class="opacity-60">{{ $byCategory[$k] }}</span></a>
        @endif
    @endforeach
    <a href="{{ route('rfid.alerts', ['dismissed' => request()->boolean('dismissed') ? null : 1]) }}" class="chip h-9 px-4 ml-auto text-stone hover:text-char">{{ request()->boolean('dismissed') ? 'Versteek weggestekte' : 'Wys weggestekte' }}</a>
</div>

<div class="mt-6 space-y-3">
    @forelse ($alerts as $a)
        <div class="rounded-2xl border {{ $sev[$a['severity']][2] }} p-5 sm:p-6 grid sm:grid-cols-[1fr_auto] gap-4 items-start {{ $a['dismissed'] ? 'opacity-50' : '' }}">
            <div class="flex gap-4">
                <span class="mt-2 w-2.5 h-2.5 shrink-0 rounded-full {{ $sev[$a['severity']][1] }}"></span>
                <div>
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="font-medium text-lg">{{ $a['title'] }}</span>
                        @if ($a['animal'])<a href="{{ route('rfid.animals.show', $a['animal']) }}" class="font-num text-sm link-u">{{ $a['animal']->visual_id }}</a>@endif
                        <span class="text-xs {{ $sev[$a['severity']][3] }}">{{ \App\Services\Herd\HerdAlerts::CATEGORIES[$a['category']] }}</span>
                    </div>
                    <p class="mt-1 text-stone">{{ $a['detail'] }}</p>
                    @if ($a['action'])<p class="mt-2 text-sm"><span class="text-stone-light">Wat om te doen:</span> {{ $a['action'] }}</p>@endif
                </div>
            </div>
            <div class="flex gap-2 sm:justify-end">
                @if ($a['dismissed'])
                    <form method="POST" action="{{ route('rfid.alerts.restore') }}">@csrf<input type="hidden" name="key" value="{{ $a['key'] }}"><button class="btn-line btn-sm">Wys weer</button></form>
                @else
                    <form method="POST" action="{{ route('rfid.alerts.dismiss') }}">@csrf<input type="hidden" name="key" value="{{ $a['key'] }}"><input type="hidden" name="days" value="7"><button class="btn-line btn-sm" title="Versteek vir 7 dae">Later</button></form>
                    <form method="POST" action="{{ route('rfid.alerts.dismiss') }}">@csrf<input type="hidden" name="key" value="{{ $a['key'] }}"><button class="btn-dark btn-sm">Hanteer ✓</button></form>
                @endif
            </div>
        </div>
    @empty
        <div class="panel p-16 text-center">
            <div class="h-display text-5xl">Alles lekker.</div>
            <p class="mt-3 text-stone">Geen waarskuwings nie. Die kudde lyk gesond — hou so aan.</p>
        </div>
    @endforelse
</div>

<details class="mt-10 panel p-6 text-sm text-stone">
    <summary class="cursor-pointer text-char font-medium">Waarna kyk die stelsel?</summary>
    <ul class="mt-4 grid md:grid-cols-2 gap-x-10 gap-y-2 leading-relaxed">
        <li><strong class="text-char">Gewigsverlies</strong> — enige verlies sedert die vorige weging; ≥5% is dringend. Gewigsverlies is die vroegste teken van siekte.</li>
        <li><strong class="text-char">Groei sukkel</strong> — groei minder as die helfte van diere van dieselfde spesie en ouderdom.</li>
        <li><strong class="text-char">Lae geboortegewig</strong> — lammers onder 3 kg (skape), 2.5 kg (bokke) of kalwers onder 25 kg.</li>
        <li><strong class="text-char">Tweelinge &amp; drielinge</strong> — ~15% en ~33% sterfte teenoor ~10% vir enkelinge.</li>
        <li><strong class="text-char">Moet lam / oor tyd</strong> — uit paringsdatums (147 dae skape, 150 bokke, 283 beeste).</li>
        <li><strong class="text-char">Dragtigheidskanderings</strong> — tweeling, drieling en leë ooie.</li>
        <li><strong class="text-char">Inteling</strong> — ouers wat nabye familie is volgens die stamboom.</li>
        <li><strong class="text-char">Onttrekkingstydperk</strong> — moenie verkoop of slag voor die datum nie.</li>
        <li><strong class="text-char">Lanklaas gesien</strong> — 60+ dae nie geskandeer nie; 120+ dae is 'n waarskuwing.</li>
        <li><strong class="text-char">Geskandeer ná dood/verkoop</strong>, <strong class="text-char">dubbele EID's</strong> en <strong class="text-char">ongewone gewigsprong</strong> (moontlike foutlesing).</li>
        <li><strong class="text-char">Speen agterstallig</strong>, <strong class="text-char">lam verloor</strong> en <strong class="text-char">ou ooie</strong> vir uitskot.</li>
    </ul>
    <p class="mt-4">Drempels kan per spesie aangepas word. Die stelsel is 'n hulpmiddel — nie 'n veearts nie.</p>
</details>
@endsection
