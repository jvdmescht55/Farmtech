@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Check your farm details', 'body' => '<p>Stud name, breeder number and address print on the breeder line — e.g. <code>0696358 DIE BULT MEATMASTER STOET, POSBUS 42, KENHARDT, 8900</code>.</p>', 'cta' => [route('rfid.settings.edit'), 'Farm settings']])
    @include('help._step', ['n' => 2, 'title' => 'Make the book', 'body' => '<p>More → <strong>Auction books</strong> → give it a name, pick the section (Ewes / Ooie, Rams / Ramme…) and the sale date.</p>', 'cta' => [route('rfid.catalogues.index'), 'Auction books']])
    @include('help._step', ['n' => 3, 'title' => 'Tick the animals', 'body' => '<p>Search or filter on the right, tick them, <strong>Add selected</strong>. Each animal\'s comment (e.g. "Moontlik dragtig van DVS 23 3369") comes along.</p>'])
    @include('help._step', ['n' => 4, 'title' => 'Number the lots in one click', 'body' => '<p>Choose the first number and how many per lot — 4 per lot gives <code>66A 66B 66C 66D 67A…</code>. Tweak any number by hand if you like.</p>'])
    @include('help._step', ['n' => 5, 'title' => 'Print', 'body' => '<p>Print opens the Logix-style layout: lot, ID, birth date and type, REG, SP/C/B status, EBVs with accuracies, lambing record, sire and dam with grandparents, comments, and a buyer-notes column. Print it or "Save as PDF".</p>'])
@endsection
