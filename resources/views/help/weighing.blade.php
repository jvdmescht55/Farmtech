@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Open Start weighing on your phone', 'body' => '<p>It shows each read as it arrives — the animal, its weight, the change since last time and any warnings.</p>', 'cta' => [route('rfid.live'), 'Start weighing']])
    @include('help._step', ['n' => 2, 'title' => 'Scan, weigh, save on the scale', 'body' => '<p>Pick the weight type (birth, wean, post-wean, mature) so growth figures make sense later.</p>'])
    @include('help._step', ['n' => 3, 'title' => 'Look at the weigh day', 'body' => '<p>Weighing → <strong>Sessions</strong>: average, range, daily gain, and who lost weight — sort by "Losers first" to catch problems quickly.</p>', 'cta' => [route('rfid.weighings.index'), 'Weigh days']])
    @include('help._step', ['n' => 4, 'title' => 'Compare and sort', 'body' => '<p><strong>Compare</strong> shows which ram\'s lambs grow best, twins vs singles, and this weigh day vs the last. <strong>Sort by weight</strong> splits the mob into groups and tells you who\'s ready for the market.</p>'])
@endsection
