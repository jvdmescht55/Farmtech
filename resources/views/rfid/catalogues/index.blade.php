@extends('layouts.rfid')
@section('title', 'Sale catalogues')
@section('eyebrow')Auction-ready, with lot numbers @endsection
@section('content')
<div class="grid lg:grid-cols-5 gap-6">
    <div class="lg:col-span-3 app-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="app-table">
                <thead><tr><th>Catalogue</th><th>Section</th><th>Sale date</th><th class="text-right">Lots</th><th></th></tr></thead>
                <tbody>
                @forelse ($catalogues as $c)
                    <tr>
                        <td><a href="{{ route('rfid.catalogues.show', $c) }}" class="font-medium text-char hover:underline">{{ $c->title }}</a></td>
                        <td class="text-stone">{{ $c->section }}</td>
                        <td class="whitespace-nowrap">{{ $c->sale_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-right font-mono">{{ $c->lots_count }}</td>
                        <td class="text-right whitespace-nowrap"><a href="{{ route('rfid.catalogues.print', $c) }}" target="_blank" class="text-sm font-medium text-char hover:underline">Print</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-12 text-stone">No catalogues yet — make one on the right.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="lg:col-span-2 app-card p-6">
        <h2 class="font-headline text-3xl mb-5">New catalogue</h2>
        <form method="POST" action="{{ route('rfid.catalogues.store') }}" class="space-y-4">
            @csrf
            @include('rfid.catalogues._details', ['c' => new \App\Models\SaleCatalogue(['section' => 'Ewes / Ooie'])])
            <button class="btn-primary">Create</button>
        </form>
    </div>
</div>
@endsection
