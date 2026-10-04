@extends('layouts.watch')
@section('title', 'Animals at the water')
@section('eyebrow')Seen by a Watch in the last 2 weeks @endsection
@section('content')
<div class="inline-flex rounded-full bg-white border border-hairline p-1 mb-6">
    <a href="{{ route('watch.animals') }}" class="rounded-full px-5 h-9 inline-flex items-center text-sm {{ request('show') !== 'missed' ? 'bg-char text-sand' : 'text-stone hover:text-char' }}">All</a>
    <a href="{{ route('watch.animals', ['show' => 'missed']) }}" class="rounded-full px-5 h-9 inline-flex items-center text-sm {{ request('show') === 'missed' ? 'bg-char text-sand' : 'text-stone hover:text-char' }}">Missed a drink</a>
</div>
<div class="panel overflow-hidden">
    <div class="overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Animal</th><th>Sex</th><th>Last drink</th><th>Where</th><th class="text-right">Visits (7 days)</th><th class="text-right">Per day</th><th></th></tr></thead>
            <tbody>
            @forelse ($animals as $w)
                <tr>
                    <td><a href="{{ route('rfid.animals.show', $w->animal) }}" class="font-num font-medium link-u">{{ $w->animal->visual_id }}</a></td>
                    <td class="text-stone">{{ $w->animal->sexLabel() }}</td>
                    <td class="whitespace-nowrap">{{ $w->last_at->diffForHumans() }}</td>
                    <td class="text-stone">{{ $w->last_point }}</td>
                    <td class="text-right num">{{ $w->visits_7d }}</td>
                    <td class="text-right num">{{ $w->avg_per_day }}</td>
                    <td class="text-right">@if ($w->missed)<span class="chip bg-[#B0452F]/10 text-[#B0452F]">Over {{ $w->limit }} h</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-16 text-center text-stone">{{ request('show') === 'missed' ? 'Nobody\'s missed a drink. Lekker.' : 'No visits yet. Once a Watch is up, animals show here as they come to drink.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
