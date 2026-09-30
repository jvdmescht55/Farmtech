@extends('layouts.rfid')
@section('title', 'Overview')
@section('actions')
    <a href="{{ route('rfid.readers.index') }}" class="btn-secondary hidden sm:inline-flex">Upload scans</a>
    <a href="{{ route('rfid.animals.create') }}" class="btn-primary">+ Animal</a>
@endsection
@section('content')
@if ($herdCount === 0)
    <div class="app-card p-8 mb-8 bg-gradient-to-br from-white to-mint/5">
        <h2 class="font-display text-2xl font-semibold">Let's get your herd in</h2>
        <p class="text-ink-secondary mt-2 max-w-2xl">Three ways to start — use whichever matches what you have on hand.</p>
        <div class="grid md:grid-cols-3 gap-4 mt-6">
            <a href="{{ route('rfid.import.create') }}" class="rounded-xl border border-border bg-white p-5 hover:border-brand-900 transition">
                <div class="font-semibold">1 · Import your stud register</div>
                <p class="text-sm text-ink-secondary mt-1">CSV from Logix or a spreadsheet — IDs, pedigree, EBVs.</p>
            </a>
            <a href="{{ route('rfid.readers.index') }}" class="rounded-xl border border-border bg-white p-5 hover:border-brand-900 transition">
                <div class="font-semibold">2 · Sync your reader</div>
                <p class="text-sm text-ink-secondary mt-1">Upload the reader's session file or connect it with its sync token.</p>
            </a>
            <a href="{{ route('rfid.animals.create') }}" class="rounded-xl border border-border bg-white p-5 hover:border-brand-900 transition">
                <div class="font-semibold">3 · Add animals by hand</div>
                <p class="text-sm text-ink-secondary mt-1">Good for a handful of rams or a new purchase.</p>
            </a>
        </div>
    </div>
@endif

<div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
    <div class="app-card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Active herd</div>
        <div class="stat-num mt-2">{{ number_format($herdCount) }}</div>
        <div class="text-sm text-ink-secondary mt-1">{{ $ewes }} ewes · {{ $rams }} rams @if($unsexed) · {{ $unsexed }} unsexed @endif</div>
    </div>
    <div class="app-card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Scans · 30 days</div>
        <div class="stat-num mt-2">{{ number_format($scans30) }}</div>
        <div class="text-sm text-ink-secondary mt-1">{{ $weighed30 }} animals weighed</div>
    </div>
    <div class="app-card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Not scanned · 30 days</div>
        <div class="stat-num mt-2 {{ $notSeen ? 'text-alert-dark' : '' }}">{{ number_format($notSeen) }}</div>
        <div class="text-sm text-ink-secondary mt-1">Check for missing or lost tags</div>
    </div>
    <div class="app-card p-5">
        <div class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Records to complete</div>
        <div class="stat-num mt-2">{{ number_format($noPedigree + $noEid) }}</div>
        <div class="text-sm text-ink-secondary mt-1">{{ $noPedigree }} no full pedigree · {{ $noEid }} no EID</div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <div class="app-card p-6">
        <div class="flex items-baseline justify-between">
            <h2 class="font-semibold">Genetic tiers</h2>
            <span class="text-xs text-ink-muted">from pedigree</span>
        </div>
        @php($maxTier = max(1, $tierCounts->max()))
        <div class="mt-5 space-y-3">
            @foreach ($tierCounts as $t => $n)
                <a href="{{ route('rfid.animals.index', ['tier' => $t]) }}" class="flex items-center gap-3 group">
                    <x-tier :tier="$t === '?' ? null : $t" class="w-10" />
                    <div class="flex-1 h-2.5 rounded-full bg-canvas overflow-hidden">
                        <div class="h-full rounded-full {{ ['SP' => 'bg-brand-900', 'C' => 'bg-mint', 'B' => 'bg-alert', 'CC' => 'bg-slate-400'][$t] ?? 'bg-ink-muted/40' }}" style="width: {{ $n / $maxTier * 100 }}%"></div>
                    </div>
                    <span class="w-10 text-right text-sm font-mono group-hover:text-brand-900">{{ $n }}</span>
                </a>
            @endforeach
        </div>
        <p class="text-xs text-ink-muted mt-5 leading-relaxed">Offspring grade one step above their weaker parent: CC → B → C → SP. "?" means a parent or grandparent is missing from the record.</p>
    </div>

    <div class="app-card p-6 lg:col-span-2">
        <div class="flex items-baseline justify-between">
            <h2 class="font-semibold">Average weight by month</h2>
            <span class="text-xs text-ink-muted">all weighings, last 6 months</span>
        </div>
        @if ($monthly->isEmpty())
            <p class="text-sm text-ink-secondary mt-6">No weights yet. Readers paired with a scale send weights with each scan, or record one on an animal's page.</p>
        @else
            @php($maxW = max(1, $monthly->max('avg')))
            <div class="mt-6 h-48 flex items-end gap-3">
                @foreach ($monthly as $month => $m)
                    <div class="flex-1 flex flex-col items-center gap-2 h-full justify-end">
                        <div class="text-xs font-mono text-charcoal">{{ $m['avg'] }}</div>
                        <div class="w-full max-w-14 rounded-t-md bg-brand-900" style="height: {{ max(4, $m['avg'] / $maxW * 100) }}%" title="{{ $m['n'] }} weighings"></div>
                        <div class="text-[11px] text-ink-muted">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mt-6">
    <div class="app-card lg:col-span-2 overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between border-b border-border">
            <h2 class="font-semibold">Recently scanned</h2>
            <a href="{{ route('rfid.animals.index', ['sort' => 'last_seen']) }}" class="text-sm text-brand-900 font-semibold hover:underline">All animals →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead><tr><th>Animal</th><th>EID</th><th>Sex</th><th>Tier</th><th>Last seen</th></tr></thead>
                <tbody>
                @forelse ($recent as $a)
                    <tr>
                        <td><a href="{{ route('rfid.animals.show', $a) }}" class="font-semibold text-brand-900 hover:underline font-mono">{{ $a->visual_id }}</a></td>
                        <td class="font-mono text-xs text-ink-secondary">{{ $a->eid ?? '—' }}</td>
                        <td>{{ $a->sexLabel() }}</td>
                        <td><x-tier :tier="$a->tierResult()->tier" /></td>
                        <td class="text-ink-secondary whitespace-nowrap">{{ $a->last_seen_at?->diffForHumans() ?? 'Never' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-ink-secondary py-8">Nothing scanned yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="app-card p-6">
            <div class="flex items-center justify-between"><h2 class="font-semibold">Readers</h2><a href="{{ route('rfid.readers.index') }}" class="text-sm text-brand-900 font-semibold hover:underline">Manage</a></div>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($readers as $r)
                    <li class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full {{ $r->last_synced_at && $r->last_synced_at->gt(now()->subDays(7)) ? 'bg-mint' : 'bg-ink-muted/40' }}"></span>
                        <div class="min-w-0 flex-1"><div class="font-medium truncate">{{ $r->name }}</div><div class="text-xs text-ink-muted font-mono">{{ $r->serial ?? 'no serial' }}</div></div>
                        <div class="text-xs text-ink-secondary whitespace-nowrap">{{ $r->last_synced_at?->diffForHumans() ?? 'never synced' }}</div>
                    </li>
                @empty
                    <li class="text-ink-secondary">No readers linked.</li>
                @endforelse
            </ul>
        </div>
        <div class="app-card p-6">
            <div class="flex items-center justify-between"><h2 class="font-semibold">Recent syncs</h2></div>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($syncs as $s)
                    <li><a href="{{ route('rfid.sync.show', $s) }}" class="flex justify-between gap-3 hover:text-brand-900">
                        <span class="truncate">{{ $s->reader?->name ?? strtoupper($s->source) }} · {{ $s->scan_count }} scans</span>
                        <span class="text-xs text-ink-muted whitespace-nowrap">{{ $s->created_at->format('d M H:i') }}</span>
                    </a></li>
                @empty
                    <li class="text-ink-secondary">No syncs yet.</li>
                @endforelse
            </ul>
        </div>
        <div class="app-card p-6">
            <div class="flex items-center justify-between"><h2 class="font-semibold">Sale catalogues</h2><a href="{{ route('rfid.catalogues.index') }}" class="text-sm text-brand-900 font-semibold hover:underline">All</a></div>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($catalogues as $c)
                    <li><a href="{{ route('rfid.catalogues.show', $c) }}" class="flex justify-between gap-3 hover:text-brand-900">
                        <span class="truncate">{{ $c->title }}</span><span class="text-xs text-ink-muted whitespace-nowrap">{{ $c->lots_count }} lots</span>
                    </a></li>
                @empty
                    <li class="text-ink-secondary">None yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
