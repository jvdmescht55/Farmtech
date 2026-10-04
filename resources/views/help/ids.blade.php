@extends('help._guide')
@section('steps')
    <div class="panel p-8">
        <div class="kpi-label">Birthday numbers</div>
        <div class="mt-4 flex flex-wrap items-end gap-2 font-num">
            @foreach ([['25', 'year', '2025'], ['09', 'month', 'September'], ['12', 'number', '12th that month']] as [$d, $l, $m])
                <div class="text-center"><div class="rounded-2xl bg-char text-sand px-5 py-4 text-5xl">{{ $d }}</div><div class="mt-2 text-xs uppercase tracking-wider text-stone">{{ $l }}</div><div class="text-sm">{{ $m }}</div></div>
            @endforeach
        </div>
        <p class="mt-6 text-stone">So <strong class="text-char">250912</strong> is the 12th lamb born in September 2025. The number <em>is</em> the birthday. Anyone in the kraal can read an animal's age straight off its tag.</p>
    </div>
    @include('help._step', ['n' => 1, 'title' => 'The scale suggests the next one', 'body' => '<p>When you register a new lamb, the scale fills in the next free number for this month. Press <code>#</code> to accept or <code>C</code> to change it.</p>'])
    @include('help._step', ['n' => 2, 'title' => 'The website reads the birth month', 'body' => '<p>New animals with a birthday number get their birth month filled in automatically, so ages, 100-day weights and weaning reminders work straight away.</p>'])
    @include('help._step', ['n' => 3, 'title' => 'Older numbers still work', 'body' => '<p>Animals you already have (2415, 21270, 197013…) keep their numbers. Only new animals get birthday numbers.</p>'])
@endsection
