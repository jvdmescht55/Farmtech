@extends('layouts.rfid')
@section('title', 'Readers & sync')
@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-3 space-y-6">
        <div class="app-card p-6">
            <h2 class="font-semibold">Upload a session file</h2>
            <p class="text-sm text-ink-secondary mt-1">Export the session from your reader (or its PC/phone app) as CSV and drop it here. Tags are matched to animals by EID, then visual ID; unknown tags are added to the herd.</p>
            <form method="POST" action="{{ route('rfid.sync.upload') }}" enctype="multipart/form-data" class="mt-5 grid sm:grid-cols-2 gap-4">
                @csrf
                <div class="sm:col-span-2"><label class="app-label">File (.csv / .txt)</label><input type="file" name="file" accept=".csv,.txt,.tsv" required class="app-input file:mr-3 file:rounded-md file:border-0 file:bg-canvas file:px-3 file:py-1 file:text-sm file:font-semibold"></div>
                <div><label class="app-label">Reader</label>
                    <select name="reader_id" class="app-input"><option value="">— not specified —</option>@foreach ($readers as $r)<option value="{{ $r->id }}">{{ $r->name }}{{ $r->serial ? ' · '.$r->serial : '' }}</option>@endforeach</select></div>
                <div><label class="app-label">Weigh type (if the file has weights)</label>
                    <select name="weigh_type" class="app-input"><option value="">Routine / from file</option>@foreach (\App\Models\Scan::WEIGH_TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div class="sm:col-span-2 flex items-center gap-4">
                    <button class="btn-primary">Import scans</button>
                    <span class="text-xs text-ink-muted">Recognised columns: EID / Tag, Visual ID / VID / Animal ID, Weight / kg, Date.</span>
                </div>
            </form>
        </div>

        <div class="app-card overflow-hidden">
            <div class="px-6 py-4 border-b border-border"><h2 class="font-semibold">Sync history</h2></div>
            <div class="overflow-x-auto">
                <table class="app-table">
                    <thead><tr><th>When</th><th>Reader</th><th>Source</th><th class="text-right">Scans</th><th class="text-right">Matched</th><th class="text-right">New</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($syncs as $s)
                        <tr>
                            <td class="whitespace-nowrap">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $s->reader?->name ?? '—' }}</td>
                            <td class="text-xs uppercase text-ink-secondary">{{ $s->source }}{{ $s->filename ? ' · '.$s->filename : '' }}</td>
                            <td class="text-right font-mono">{{ $s->scan_count }}</td>
                            <td class="text-right font-mono">{{ $s->matched_count }}</td>
                            <td class="text-right font-mono {{ $s->new_count ? 'text-alert-dark' : '' }}">{{ $s->new_count }}</td>
                            <td class="text-right"><a href="{{ route('rfid.sync.show', $s) }}" class="text-brand-900 font-semibold text-sm hover:underline">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-8 text-ink-secondary">No syncs yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{ $syncs->links() }}
    </div>

    <div class="xl:col-span-2 space-y-6">
        <div class="app-card p-6">
            <h2 class="font-semibold">Your readers</h2>
            <ul class="mt-4 divide-y divide-border">
                @forelse ($readers as $r)
                    <li class="py-4" x-data="{ show: {{ isset($shownToken[$r->id]) ? 'true' : 'false' }} }">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold">{{ $r->name }}</div>
                                <div class="text-xs text-ink-muted font-mono">{{ $r->model ?? 'Model not set' }} · {{ $r->serial ?? 'no serial' }}</div>
                                <div class="text-xs text-ink-secondary mt-1">{{ $r->syncs_count }} syncs · last {{ $r->last_synced_at?->diffForHumans() ?? 'never' }}</div>
                            </div>
                            <button type="button" @click="show = !show" class="text-xs font-semibold text-brand-900">Sync token</button>
                        </div>
                        <div x-show="show" x-cloak class="mt-3 rounded-lg bg-canvas p-3 text-xs space-y-2">
                            @if (isset($shownToken[$r->id]))
                                <div class="font-semibold text-alert-dark">Copy this now — it won't be shown again.</div>
                                <code class="block break-all font-mono bg-white border border-border rounded px-2 py-1.5">{{ $shownToken[$r->id] }}</code>
                            @else
                                <p class="text-ink-secondary">Tokens are only shown once. Issue a new one to connect this reader again.</p>
                            @endif
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('rfid.readers.token', $r) }}">@csrf<button class="btn-secondary !px-3 !py-1.5 !text-xs">Issue new token</button></form>
                                <form method="POST" action="{{ route('rfid.readers.destroy', $r) }}" x-data @submit="if (!$el.dataset.ok) { $event.preventDefault(); $el.dataset.ok = 1; $el.querySelector('button').textContent = 'Click again to remove'; }">@csrf @method('DELETE')<button class="btn-danger">Remove</button></form>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="py-4 text-sm text-ink-secondary">No readers yet.</li>
                @endforelse
            </ul>
            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-semibold text-brand-900">+ Add another reader</summary>
                <form method="POST" action="{{ route('rfid.readers.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <div><label class="app-label">Name</label><input name="name" required placeholder="Kraal stick reader" class="app-input"></div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="app-label">Model</label><input name="model" class="app-input"></div>
                        <div><label class="app-label">Serial</label><input name="serial" class="app-input font-mono"></div>
                    </div>
                    <button class="btn-primary">Add reader</button>
                </form>
            </details>
        </div>

        <div class="app-card p-6 text-sm">
            <h2 class="font-semibold">Direct sync (API)</h2>
            <p class="text-ink-secondary mt-1">Readers and apps that support HTTP upload can post scans straight in with the reader's token.</p>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-brand-975 text-white/85 p-4 text-[11px] leading-relaxed"><code>POST {{ url('/api/reader/sync') }}
Authorization: Bearer &lt;reader token&gt;
Content-Type: application/json

{"scans": [
  {"eid": "982000123456789",
   "weight": 42.5,
   "scanned_at": "2026-09-30 08:15"}
]}</code></pre>
        </div>
    </div>
</div>
@endsection
