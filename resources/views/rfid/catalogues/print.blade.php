@php
    $ebvs = config('herd.ebvs');
    $rec = config('herd.dam_record');
    $pid = fn ($a) => $a ? $a->visual_id.($a->birth_type ? ' ('.$a->birth_type.')' : '') : '';
@endphp
<!DOCTYPE html>
<html lang="af">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $catalogue->title }} — Sales Catalogue</title>
<style>
    @page { size: A4 landscape; margin: 9mm 8mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111; background: #e9ece9; font-size: 9px; }
    .sheet { background: #fff; max-width: 297mm; margin: 16px auto; padding: 9mm 8mm; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
    .toolbar { max-width: 297mm; margin: 16px auto 0; display: flex; gap: 8px; justify-content: flex-end; padding: 0 16px; }
    .toolbar a, .toolbar button { font: 600 13px Arial, sans-serif; padding: 8px 14px; border-radius: 6px; border: 1px solid #0B4A36; background: #0B4A36; color: #fff; text-decoration: none; cursor: pointer; }
    .toolbar a { background: #fff; color: #0B4A36; }
    .head { display: grid; grid-template-columns: 70mm 1fr; gap: 6mm; align-items: start; }
    .brand { font: 700 20px Georgia, serif; letter-spacing: -.5px; }
    .brand span { color: #14A875; }
    .brand small { display: block; font: 600 8px Arial, sans-serif; letter-spacing: 2px; text-transform: uppercase; color: #666; margin-top: 2px; }
    .title { background: #bdbdbd; font-weight: 700; font-size: 15px; padding: 3px 8px; letter-spacing: .3px; }
    .compiled { font-style: italic; font-size: 8.5px; margin-top: 3px; line-height: 1.35; }
    .section { font-weight: 700; font-size: 10px; margin: 5mm 0 1.5mm; }
    .meta { font-size: 8.5px; color: #333; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; }
    th { font-weight: normal; font-size: 7.5px; text-align: center; vertical-align: bottom; padding: 2px 3px; line-height: 1.2; }
    thead tr.top th { border-top: 1px solid #555; }
    thead tr:last-child th { border-bottom: 1px solid #555; }
    th.grp { border-bottom: 1px solid #aaa; }
    td { padding: 2px 3px; text-align: center; vertical-align: top; white-space: nowrap; }
    td.l, th.l { text-align: left; }
    .breeder td { background: #9e9e9e; font-weight: 700; text-align: left; padding: 2px 6px; font-size: 9px; }
    .lot { font-weight: 700; font-size: 12px; text-align: left; }
    .aid { text-align: left; font-size: 9.5px; }
    sup { font-size: 6px; vertical-align: super; line-height: 0; margin-left: 1px; }
    .acc { font-size: 6px; vertical-align: super; line-height: 0; color: #333; margin-left: 1px; }
    .reg { font-size: 7px; display: block; }
    .ped { text-align: left; font-size: 8px; }
    .ped .gp { display: flex; gap: 10px; color: #222; font-size: 7.5px; margin-top: 1px; }
    .cmt td { text-align: left; border-bottom: 1px solid #bbb; padding-bottom: 4px; font-size: 8.5px; }
    .cmt b { font-weight: normal; display: inline-block; width: 22mm; line-height: 1.1; }
    .buyer { border-left: 1px solid #ddd; min-width: 30mm; }
    .foot { margin-top: 5mm; display: flex; justify-content: space-between; font-size: 7.5px; color: #555; border-top: 1px solid #ccc; padding-top: 2mm; }
    .legend { margin-top: 3mm; font-size: 7.5px; color: #333; line-height: 1.5; }
    @media print {
        body { background: #fff; }
        .sheet { margin: 0; padding: 0; box-shadow: none; max-width: none; }
        .toolbar { display: none; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
    }
    @media (max-width: 900px) { .sheet { overflow-x: auto; margin: 8px; } .head { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="toolbar">
    <a href="{{ route('rfid.catalogues.show', $catalogue) }}">← Edit catalogue</a>
    <button onclick="window.print()">Print / Save as PDF</button>
</div>
<div class="sheet">
    <div class="head">
        <div class="brand">Farm<span>tech</span><small>RFID Herd Manager</small></div>
        <div>
            <div class="title">Sales Catalogue / Verkoopskatalogus {{ mb_strtoupper($catalogue->breed ?? '') }}</div>
            <div class="compiled">Compiled by the breeder from Farmtech RFID herd records. Pedigree and performance data as recorded by the breeder.<br>
                Saamgestel deur die teler uit Farmtech RFID kuddeverslae. Stamboom- en prestasiedata soos deur die teler aangeteken.</div>
            <div class="meta">{{ $catalogue->title }}@if($catalogue->sale_date) · {{ $catalogue->sale_date->format('d/m/Y') }}@endif @if($catalogue->venue) · {{ $catalogue->venue }}@endif</div>
        </div>
    </div>

    <div class="section">{{ $catalogue->section }}</div>

    <table>
        <thead>
            <tr class="top">
                <th rowspan="2" class="l">LOT</th>
                <th rowspan="2" class="l">Animal ID<br>Dier ID</th>
                <th rowspan="2">Birth Date<br>Geb. Datum</th>
                <th rowspan="2">Status</th>
                <th rowspan="2">GEN</th>
                <th colspan="2" class="grp">Wean<br>Speen</th>
                <th colspan="1" class="grp">Post Wean<br>Naspeen</th>
                @foreach (array_slice(array_keys($ebvs), 3) as $k)<th rowspan="2">{{ $ebvs[$k]['label'] }}</th>@endforeach
                <th colspan="{{ count($rec) }}" class="grp">Dam Lamb Record / Ooi lamrekord</th>
                <th rowspan="2" class="l">Sire / Vaar<br>Sire / Vaar &nbsp; Dam / Moer</th>
                <th rowspan="2" class="l">Dam / Moer<br>Sire / Vaar &nbsp; Dam / Moer</th>
                <th rowspan="2" class="l buyer">Buyer comments<br>Koper opmerkings</th>
            </tr>
            <tr>
                <th>Dir<sub>Ind</sub></th><th>Mat<sub>Ind</sub></th><th>Dir<sub>Ind</sub></th>
                @foreach ($rec as $label)<th>{{ $label }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
            @if ($catalogue->breeder_line)
                <tr class="breeder"><td colspan="{{ 8 + count($ebvs) - 3 + count($rec) + 3 }}">{{ $catalogue->breeder_line }}</td></tr>
            @endif
            @forelse ($lots as $lot)
                @php($a = $lot->animal)
                <tr>
                    <td class="lot">{{ $lot->lot_number }}</td>
                    <td class="aid">{{ $a->visual_id }}</td>
                    <td>{{ $a->birth_date?->format('d/m/Y') }}@if($a->birth_type)<sup>{{ $a->birth_type }}</sup>@endif @if($a->registered)<span class="reg">REG</span>@endif</td>
                    <td><b>{{ $lot->tier }}</b></td>
                    <td>{{ $a->gen_score }}</td>
                    @foreach (array_keys($ebvs) as $k)
                        @php($e = $a->ebv($k))
                        <td>@if($e){{ rtrim(rtrim(number_format($e['v'], 2, '.', ''), '0'), '.') }}@if($e['acc'] !== null)<span class="acc">{{ $e['acc'] }}</span>@endif @endif</td>
                    @endforeach
                    @foreach (array_keys($rec) as $k)<td>{{ $a->dam_record[$k] ?? '' }}</td>@endforeach
                    <td class="ped">{{ $pid($a->sire) }}<div class="gp"><span>{{ $a->sire?->sire?->visual_id }}</span><span>{{ $a->sire?->dam?->visual_id }}</span></div></td>
                    <td class="ped">{{ $pid($a->dam) }}<div class="gp"><span>{{ $a->dam?->sire?->visual_id }}</span><span>{{ $a->dam?->dam?->visual_id }}</span></div></td>
                    <td class="buyer" rowspan="2"></td>
                </tr>
                <tr class="cmt">
                    <td colspan="{{ 8 + count($ebvs) - 3 + count($rec) + 2 }}"><b>Comment:<br>Opmerking:</b> {{ $lot->comment }}</td>
                </tr>
            @empty
                <tr><td colspan="20" style="padding: 20px">No lots in this catalogue yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="legend">
        <b>Status</b> — genetic tier from pedigree: SP = full stud; C / B = grading-up (one step above the weaker parent); CC = commercial/foundation.
        Birth type in superscript: 01 single, 02 twin, 03 triplet. EBV accuracy (%) in superscript. REG = registered.
    </div>
    <div class="foot">
        <span>{{ $user->breederLine() }}</span>
        <span>Printed {{ now()->format('d/m/Y H:i') }} · Farmtech RFID Herd Manager</span>
    </div>
</div>
</body>
</html>
