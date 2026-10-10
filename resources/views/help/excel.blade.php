@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Use the spreadsheet you already have', 'body' => '<p>No template, no renaming columns. Your own Excel sheet, a Logix or stud-book export, or a scale file all work. English or Afrikaans headings ("Oornommer", "Vaar", "Moer", "Gewig", "Gebore") are fine, and so are title rows above the table.</p>'])
    @include('help._step', ['n' => 2, 'title' => 'Drop it in', 'body' => '<p>Import &amp; export → <strong>Choose or drop a file</strong>. Excel workbooks work as they are, and every sheet is read. You can also copy rows in Excel and paste them.</p>', 'cta' => [route('rfid.data'), 'Open Import & export']])
    @include('help._step', ['n' => 3, 'title' => 'We sort it for you', 'body' => '<ul class="list-disc pl-5 space-y-1"><li>Sex, birth dates, parents and grandparents → the <strong>herd book</strong>.</li><li>Weights → <strong>weighings</strong>, including "Birth weight", "Speengewig" or "Weight 01/09/2026" columns side by side.</li><li>A date with "Doseer", "Ingeënt" or "Gepaar" → <strong>records</strong> (with product, dose and withdrawal).</li><li>Anything else (camp, colour, condition) → kept in the animal\'s <strong>notes</strong>, so nothing is lost.</li></ul>'])
    @include('help._step', ['n' => 4, 'title' => 'Check, then bring it in', 'body' => '<p>You see how many animals, weighings and records we found before anything is saved. "Check the columns" lets you change where any column goes. Importing the same file twice is safe: same ID means an update, never a duplicate.</p>'])
@endsection
@section('aside')
    <div class="panel p-6 text-sm text-stone"><div class="kpi-label mb-2">Only one thing is needed</div><p>A column that says which animal each row is: an ear tag, an ID or the 15-digit electronic tag. Dates can be 30/09/2026, 2026-09-30 or 30 Sep 2026.</p></div>
@endsection
