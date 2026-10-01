@extends('layouts.rfid')
@section('title', 'Sinch #'.$sync->id)
@section('actions')<a href="{{ route('rfid.readers.index') }}" class="btn-secondary">← Toestelle</a>@endsection
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="app-card p-5"><div class="app-label">Wanneer</div><div class="font-medium">{{ $sync->created_at->format('d/m/Y H:i') }}</div><div class="text-xs text-stone-light">{{ strtoupper($sync->source) }} · {{ $sync->reader?->name ?? 'geen toestel' }}</div></div>
    <div class="app-card p-5"><div class="app-label">Lesings</div><div class="stat-num">{{ $sync->scan_count }}</div></div>
    <div class="app-card p-5"><div class="app-label">Bekend</div><div class="stat-num">{{ $sync->matched_count }}</div></div>
    <div class="app-card p-5"><div class="app-label">Nuwe diere</div><div class="stat-num {{ $sync->new_count ? 'text-ochre-dark' : '' }}">{{ $sync->new_count }}</div><div class="text-xs text-stone-light">voltooi hul rekords</div></div>
</div>
<div class="app-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="app-table">
            <thead><tr><th>Tyd</th><th>EID</th><th>Dier</th><th class="text-right">Gewig</th><th>Tipe</th></tr></thead>
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
