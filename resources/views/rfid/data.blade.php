@extends('layouts.rfid')
@section('title', 'Import & export')
@section('eyebrow')Drop in any spreadsheet and we sort it. Or take everything out. @endsection
@section('actions')<a href="{{ route('rfid.data.backup') }}" class="btn-primary">Full backup (.zip)</a>@endsection

@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <div class="xl:col-span-3 space-y-6">
        @if ($preview)
            @php
                $tot = ['animals' => 0, 'weights' => 0, 'records' => 0, 'kept' => 0];
                foreach ($preview['sheets'] as $sh) {
                    $tot['animals'] += $sh['summary']['animals'];
                    $tot['weights'] += $sh['summary']['weights'] + $sh['summary']['presence'];
                    $tot['records'] += $sh['summary']['records'];
                    $tot['kept'] += count($sh['summary']['kept']);
                }
                $groups = collect($fields)->groupBy(fn ($f) => $f[1], true);
            @endphp
            <form method="POST" action="{{ route('rfid.data.import') }}" class="panel overflow-hidden" x-data="{ check: false }">
                @csrf
                <input type="hidden" name="token" value="{{ $preview['token'] }}">
                <input type="hidden" name="name" value="{{ $preview['name'] }}">
                <div class="px-6 sm:px-8 pt-7 pb-6">
                    <p class="text-sm text-stone">{{ $preview['name'] }}</p>
                    <h2 class="font-headline text-4xl mt-1">Here's what we found.</h2>
                    <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach (['animals' => 'animals', 'weights' => 'weighings', 'records' => 'records', 'kept' => 'extra columns'] as $k => $l)
                            <div class="rounded-2xl bg-sand-light px-4 py-3 {{ $tot[$k] ? '' : 'opacity-50' }}"><div class="kpi-num text-3xl">{{ number_format($tot[$k]) }}</div><div class="text-xs text-stone">{{ $l }}</div></div>
                        @endforeach
                    </div>
                    <p class="text-sm text-stone mt-4">Nothing is saved yet. Same ID = an update, never a duplicate. Columns we have no place for are kept in each animal's notes.</p>
                </div>

                <div class="divide-y divide-hairline border-t border-hairline">
                    @foreach ($preview['sheets'] as $si => $sh)
                        @php($sum = $sh['summary'])
                        <div class="px-6 sm:px-8 py-5">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="hidden" name="include[{{ $si }}]" value="0">
                                <input type="checkbox" name="include[{{ $si }}]" value="1" checked class="mt-1.5 rounded border-hairline text-char focus:ring-char">
                                <span class="flex-1">
                                    <span class="font-medium">{{ count($preview['sheets']) > 1 ? 'Sheet "'.$sh['name'].'"' : 'Your rows' }}</span>
                                    <span class="text-stone">· {{ number_format($sh['rows']) }} rows</span>
                                    <span class="block text-sm text-stone mt-1">
                                        {{ collect([
                                            $sum['herd'] ? $sum['animals'].' animal'.($sum['animals'] === 1 ? '' : 's').' into the herd book' : null,
                                            $sum['weights'] ? $sum['weights'].' weighing'.($sum['weights'] === 1 ? '' : 's') : null,
                                            $sum['presence'] ? $sum['presence'].' tag reads' : null,
                                            $sum['records'] ? $sum['records'].' record'.($sum['records'] === 1 ? '' : 's').' (treatments, matings…)' : null,
                                        ])->filter()->implode(', ') ?: 'Animals into the herd book' }}.
                                        @if ($sum['kept']) Kept in notes: {{ collect($sum['kept'])->map(fn ($i) => $sh['headers'][$i])->implode(', ') }}.@endif
                                        @if ($sum['no_id'])<span class="text-[#B0452F]">{{ $sum['no_id'] }} row{{ $sum['no_id'] === 1 ? ' has' : 's have' }} no animal ID and will be left out.</span>@endif
                                    </span>
                                </span>
                            </label>

                            <div x-show="check" x-cloak class="mt-4 overflow-x-auto rounded-2xl border border-hairline">
                                <table class="tbl text-sm min-w-[520px]">
                                    <thead><tr><th>Your column</th><th>Example</th><th>Goes to</th></tr></thead>
                                    <tbody>
                                        @foreach ($sh['headers'] as $ci => $h)
                                            @php($f = $sh['map'][$ci])
                                            @php($example = collect($sh['sample'])->pluck($ci)->first(fn ($v) => $v !== '') ?? '')
                                            <tr>
                                                <td class="font-medium">{{ $h }}</td>
                                                <td class="text-stone num text-xs">{{ \Illuminate\Support\Str::limit($example, 28) }}</td>
                                                <td>
                                                    <select name="map[{{ $si }}][{{ $ci }}]" class="field py-1.5 text-sm w-56" aria-label="Where {{ $h }} goes">
                                                        @unless (isset($fields[$f]))<option value="{{ $f }}" selected>{{ \App\Services\Herd\SmartImport::label($f) }}</option>@endunless
                                                        @foreach ($groups as $g => $opts)
                                                            <optgroup label="{{ $g }}">
                                                                @foreach ($opts as $k => [$l])<option value="{{ $k }}" @selected($f === $k)>{{ $l }}</option>@endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-6 sm:px-8 py-6 bg-sand-light flex flex-wrap items-center gap-3">
                    <button class="btn-dark">Bring it in</button>
                    <button type="button" @click="check = !check" class="btn-line" x-text="check ? 'Hide the columns' : 'Check the columns'">Check the columns</button>
                    <a href="{{ route('rfid.data') }}" class="text-sm text-stone underline ml-auto">Cancel</a>
                </div>
            </form>
        @endif

        <div class="panel p-6 sm:p-8">
            <h2 class="font-headline text-3xl">Bring data in</h2>
            <p class="text-stone mt-2">Any Excel file or CSV: your own spreadsheet, Logix, a stud book or a scale export. No template needed. We sort it into animals, weighings and records, and show you before anything is saved.</p>
            <form method="POST" action="{{ route('rfid.data.preview') }}" enctype="multipart/form-data" class="mt-6" x-data="{ name: '', over: false }">
                @csrf
                <label class="block rounded-2xl border-2 border-dashed p-10 text-center cursor-pointer transition" :class="over ? 'border-char bg-sand-light' : 'border-hairline hover:border-char'"
                       @dragover.prevent="over = true" @dragleave.prevent="over = false"
                       @drop.prevent="over = false; $refs.file.files = $event.dataTransfer.files; name = $event.dataTransfer.files[0]?.name || ''; $nextTick(() => $el.closest('form').requestSubmit())">
                    <input x-ref="file" type="file" name="file" accept=".xlsx,.xlsm,.csv,.txt,.tsv" required class="sr-only" @change="name = $event.target.files[0]?.name || ''; $nextTick(() => $el.closest('form').requestSubmit())">
                    <div class="font-headline text-3xl" x-text="name ? 'Reading ' + name + '…' : 'Choose or drop a file'">Choose or drop a file</div>
                    <div class="text-sm text-stone mt-2">Excel (.xlsx) or .csv, up to 20 MB. Every sheet in the workbook is read.</div>
                </label>
                @error('file')<p class="text-sm text-[#B0452F] mt-3">{{ $message }}</p>@enderror
            </form>
            <ul class="mt-6 grid sm:grid-cols-3 gap-3 text-sm">
                <li class="rounded-xl bg-sand-light p-4"><div class="font-medium">Any column names</div><div class="text-xs text-stone mt-1">English or Afrikaans: "Oornommer", "Vaar", "Gewig"…</div></li>
                <li class="rounded-xl bg-sand-light p-4"><div class="font-medium">Mixed sheets are fine</div><div class="text-xs text-stone mt-1">Animals, weights and treatments in one file get split up.</div></li>
                <li class="rounded-xl bg-sand-light p-4"><div class="font-medium">Nothing lost</div><div class="text-xs text-stone mt-1">Extra columns like camp or colour go into notes.</div></li>
            </ul>
        </div>

        <div id="paste" class="panel p-6 sm:p-8 scroll-mt-28" x-data="{ open: {{ $errors->has('lines') ? 'true' : 'false' }} || location.hash === '#paste' }">
            <button type="button" @click="open = !open" class="w-full flex items-center gap-4 text-left">
                <span class="w-11 h-11 shrink-0 rounded-2xl bg-ochre text-char grid place-items-center">@include('partials.icon', ['name' => 'chip', 'class' => 'w-5 h-5'])</span>
                <span class="flex-1"><span class="block font-headline text-3xl">Or paste it</span><span class="block text-sm text-stone">Copy rows straight out of Excel (with the heading row), or the lines the scale prints when you press B.</span></span>
                <span class="text-stone" x-text="open ? '−' : '+'"></span>
            </button>
            <form x-show="open" x-cloak method="POST" action="{{ route('rfid.data.paste') }}" class="mt-6 space-y-3">
                @csrf
                <textarea name="lines" rows="7" required placeholder="Oornommer&#9;Gewig&#9;Datum&#10;DVS 25 5082&#9;42.5&#9;30/09/2026&#10;…" class="field font-num text-xs">{{ old('lines') }}</textarea>
                <div class="flex flex-wrap items-center gap-3">
                    <button class="btn-dark">Read these rows</button>
                    <span class="text-xs text-stone">Pasting twice is safe. Duplicates are skipped. <a href="{{ route('help.show', 'esp32') }}" class="underline">Step by step</a></span>
                </div>
            </form>
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
                    <div class="flex-1"><div class="font-medium">Auction books</div><div class="text-sm text-stone">Each catalogue has its own CSV and print view.</div></div>
                    <span class="text-stone-light group-hover:text-char">→</span>
                </a></li>
            </ul>
        </div>
        <div class="panel p-6 text-sm text-stone leading-relaxed">
            <div class="panel-title text-char mb-2">How we sort it</div>
            <p>Parents, birth dates and sex go to the <span class="text-char">herd book</span>. Weights (also "Weight 01/09/2026" or "Speengewig" columns) become <span class="text-char">weighings</span>. A type and a date ("Doseer", "Gepaar") become <span class="text-char">records</span>. Click "Check the columns" to change anything before it's saved.</p>
            <p class="mt-3">Any export from here opens in Excel and can come straight back in.</p><p class="mt-3">Your data is yours: export it any time. <a href="{{ route('legal.show', 'data') }}" class="link-u text-char">How we handle it</a>.</p>
        </div>
    </div>
</div>
@endsection
