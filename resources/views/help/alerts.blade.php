@extends('help._guide')
@section('steps')
    @foreach ([
        ['Today (red)', 'Act today: sharp weight loss, low birth weight, duplicate tags, missed drinks well past the limit.'],
        ['This week (ochre)', 'Losing weight, not thriving, triplets, lambs due in 3 days, overdue, inbreeding, a water point gone quiet, a custom sensor outside your limit.'],
        ['Good to know (grey)', 'Twins, weaning overdue, older ewes to think about culling, withdrawal dates, animals not scanned lately.'],
        ['Done ✓ or Snooze', 'Done hides an alert for good; Snooze hides it for 7 days. Alerts also disappear by themselves once the data says the problem\'s gone.'],
        ['Every farm is different', 'If a limit doesn\'t suit your farm, tell us — we tune it for you.'],
    ] as $i => [$t, $b])
        @include('help._step', ['n' => $i + 1, 'title' => $t, 'body' => '<p>'.e($b).'</p>'])
    @endforeach
    <div><a href="{{ route('rfid.alerts') }}" class="btn-dark">Open alerts</a></div>
@endsection
