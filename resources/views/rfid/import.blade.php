@extends('layouts.rfid')
@section('title', 'Voer kudde in')
@section('content')
<div class="grid lg:grid-cols-5 gap-6">
    <div class="lg:col-span-3 app-card p-6">
        <h2 class="font-medium">Import your stud / herd register</h2>
        <p class="text-sm text-stone mt-1">A CSV with one animal per row. Existing animals (same Animal ID) are updated, not duplicated, so you can re-import after every Logix run.</p>
        <form method="POST" action="{{ route('rfid.import.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
            @csrf
            <input type="file" name="file" accept=".csv,.txt,.tsv" required class="app-input file:mr-3 file:rounded-md file:border-0 file:bg-sand-light file:px-3 file:py-1 file:text-sm file:font-medium">
            <div class="flex gap-3">
                <button class="btn-primary">Import</button>
                <a href="{{ route('rfid.import.template') }}" class="btn-secondary">Download template</a>
            </div>
        </form>
    </div>
    <div class="lg:col-span-2 app-card p-6 text-sm">
        <h2 class="font-medium">Columns</h2>
        <p class="text-stone mt-1">Only <span class="font-mono">visual_id</span> is required. Dates as dd/mm/yyyy. Every EBV has a matching <span class="font-mono">_acc</span> column for accuracy.</p>
        <div class="mt-4 flex flex-wrap gap-1.5">
            @foreach ($headers as $h)<span class="font-mono text-[11px] rounded bg-sand-light border border-hairline px-1.5 py-0.5">{{ $h }}</span>@endforeach
        </div>
    </div>
</div>
@endsection
