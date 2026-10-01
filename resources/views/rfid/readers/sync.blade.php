@extends('layouts.rfid')
@section('title', 'Sync #'.$sync->id)
@section('actions')<a href="{{ route('rfid.readers.index') }}" class="btn-secondary">← Devices</a>@endsection
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="app-card p-5"><div class="app-label">When</div><div class="font-medium">{{ $sync->created_at->format('d/m/Y H:i') }}</div><div class="text-xs text-stone-light">{{ strtoupper($sync->source) }} · {{ $sync->reader?->name ?? 'no device' }}</div></div>
    <div class="app-card p-5"><div class="app-label">Reads</div><div class="stat-num">{{ $sync->scan_count }}</div></div>
    <div class="app-card p-5"><div class="app-label">Known animals</div><div class="stat-num">{{ $sync->matched_count }}</div></div>
    <div class="app-card p-5"><div class="app-label">New animals</div><div class="stat-num {{ $sync->new_count ? 'text-ochre-dark' : '' }}">{{ $sync->new_count }}</div><div class="text-xs text-stone-light">fill in their details</div></div>
</div>
<div class="app-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead><tr><th>Time</th><th>Tag</th><th>Animal</th><th class="text-right">Weight</th><th>Type</th></tr></thead>
            <tbody>
            @foreach ($scans as $s)
                <tr>
                    <td class="whitespace-nowrap">{{ $s->scanned_at->format('d/m/Y H:i') }}</td>
                    <td class="font-mono text-xs">{{ $s->eid ?? '—' }}</td>
                    <td>@if($s->animal)<a href="{{ route('rfid.animals.show', $s->animal) }}" class="font-mono font-medium text-char hover:underline">{{ $s->animal->visual_id }}</a>@endif</td>
                    <td class="text-right font-mono">{{ $s->weight_kg ? $s->weight_kg.' kg' : '—' }}</td>
                    <td class="text-stone">{{ \App\Models\Scan::WEIGH_TYPES[$s->weigh_type] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-6">{{ $scans->links() }}</div>
@endsection
