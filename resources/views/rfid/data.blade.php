@extends('layouts.rfid')
@section('title', 'Import & export')
@section('eyebrow')Throw in any CSV — we sort out where it goes @endsection
@section('actions')<a href="{{ route('rfid.data.backup') }}" class="btn-primary">Full backup (.zip)</a>@endsection

@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-3 space-y-6">
        @if ($preview)
            @php
                [$kindLabel, $kindHelp] = $kinds[$preview['kind']];
            @endphp
            <div class="panel overflow-hidden">
                <div class="px-6 sm:px-8 pt-7 pb-6 border-b border-hairline">
                    <p class="eyebrow">Preview · {{ $preview['name'] }}</p>
                    <h2 class="font-headline text-4xl mt-3">Looks like <em>{{ strtolower($kindLabel) }}</em>.</h2>
                    <p class="text-stone mt-2">{{ number_format($preview['count']) }} rows. {{ $kindHelp }}</p>
                    <div class="mt-4 flex flex-wrap gap-1.5">
                        @foreach ($preview['headers'] as $h)
                            <span class="chip {{ in_array($h, $preview['recognised'], true) ? 'bg-char text-sand' : 'bg-sand-deep text-stone line-through' }}" title="{{ in_array($h, $preview['recognised'], true) ? 'Recognised' : 'Ignored' }}">{{ $h }}</span>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-stone">Dark = we know this column. Crossed out = ignored.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead><tr>@foreach (array_slice($preview['headers'], 0, 10) as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                        <tbody>
                        @foreach ($preview['rows'] as $r)
                            <tr>@foreach (array_slice($preview['headers'], 0, 10) as $h)<td class="num text-xs whitespace-nowrap">{{ \Illuminate\Support\Str::limit($r[$h] ?? '', 24) }}</td>@endforeach</tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('rfid.data.import') }}" class="px-6 sm:px-8 py-6 bg-sand-light flex flex-wrap items-end gap-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $preview['token'] }}">
                    <input type="hidden" name="name" value="{{ $preview['name'] }}">
                    <div><label class="field-label">Bring it in as</label>
                        <select name="kind" class="field w-64">@foreach ($kinds as $k => [$l])<option value="{{ $k }}" @selected($preview['kind'] === $k)>{{ $l }}</option>@endforeach</select></div>
                    <button class="btn-dark">Import {{ number_format($preview['count']) }} rows</button>
                    <a href="{{ route('rfid.data') }}" class="btn-line">Cancel</a>
                </form>
            </div>
        @endif

        <div class="panel p-6 sm:p-8">
            <h2 class="font-headline text-3xl">Bring data in</h2>
            <p class="text-stone mt-2">Upload a CSV — from Logix, a spreadsheet or your KraalTrac. We look at the columns and show you what we found before anything is saved.</p>
            <form method="POST" action="{{ route('rfid.data.preview') }}" enctype="multipart/form-data" class="mt-6" x-data="{ name: '' }">
                @csrf
                <label class="block rounded-2xl border-2 border-dashed border-hairline hover:border-char transition p-10 text-center cursor-pointer">
                    <input type="file" name="file" accept=".csv,.txt,.tsv" required class="sr-only" @change="name = $event.target.files[0]?.name || ''; $nextTick(() => $el.closest('form').requestSubmit())">
                    <div class="font-headline text-3xl" x-text="name || 'Choose or drop a file'"></div>
                    <div class="text-sm text-stone mt-2">.csv · .txt · .tsv — up to 20 MB. Excel? "Save as → CSV".</div>
                </label>
            </form>
            <div class="mt-6 grid sm:grid-cols-3 gap-3 text-sm">
                @foreach ($kinds as $k => [$l, $d])
                    <a href="{{ route('rfid.data.template', $k) }}" class="rounded-xl border border-hairline p-4 hover:border-char transition">
                        <div class="font-medium">{{ $l }}</div>
                        <div class="text-xs text-stone mt-1">Download a template ↓</div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="xl:col-span-2 space-y-6">
        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Take data out</div><span class="text-xs text-stone">CSV · opens in Excel</span></div>
            <ul class="divide-y divide-hairline">
                @foreach ($exports as $k => [$l, $d])
                    <li><a href="{{ route('rfid.data', ['export' => $k]) }}" class="flex items-center gap-4 px-6 py-4 hover:bg-sand-light group">
                        <div class="flex-1"><div class="font-medium">{{ $l }}</div><div class="text-sm text-stone">{{ $d }}</div></div>
                        <span class="text-stone-light group-hover:text-char">↓</span>
                    </a></li>
                @endforeach
                <li><a href="{{ route('rfid.catalogues.index') }}" class="flex items-center gap-4 px-6 py-4 hover:bg-sand-light group">
                    <div class="flex-1"><div class="font-medium">Sale catalogues</div><div class="text-sm text-stone">Each catalogue has its own CSV and print view.</div></div>
                    <span class="text-stone-light group-hover:text-char">→</span>
                </a></li>
            </ul>
        </div>
        <div class="panel p-6 text-sm text-stone leading-relaxed">
            <div class="panel-title text-char mb-2">How we sort it</div>
            <p>Columns like <span class="font-num text-char">sire, dam, birth_date</span> → herd book. <span class="font-num text-char">weight/kg</span> with a tag or ID → weighings. <span class="font-num text-char">type + date</span> → records. You can always change it before importing.</p>
            <p class="mt-3">The herd book export uses exactly the import columns — open it in Excel, edit, and bring it back in.</p><p class="mt-3">Your data is yours: export it any time. <a href="{{ route('legal.show', 'data') }}" class="link-u text-char">How we handle it</a>.</p>
        </div>
    </div>
</div>
@endsection
