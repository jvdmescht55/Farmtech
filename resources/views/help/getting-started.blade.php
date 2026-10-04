@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Fill in your farm', 'body' => '<p>Farm name, breeder number and main breed. It goes on your auction books and sets things up the way you farm.</p>', 'cta' => [route('rfid.settings.edit'), 'Farm settings']])
    @include('help._step', ['n' => 2, 'title' => 'Get your animals in', 'body' => '<p>Pick whatever is easiest — you can mix them:</p><ul class="list-disc pl-5 space-y-1"><li><strong>Excel or Logix export</strong> — upload it under Import &amp; export. <a class="link-u text-char" href="'.route('help.show', 'excel').'">How</a></li><li><strong>Just start scanning</strong> — any tag the scale doesn\'t know becomes a new animal.</li><li><strong>By hand</strong> — Herd → Add animal.</li></ul>', 'cta' => [route('rfid.data'), 'Import & export']])
    @include('help._step', ['n' => 3, 'title' => 'Pair your KraalTrac', 'body' => '<p>Switch the scale on near Wi-Fi. It shows a 6-digit code — type it under More → Devices. Sommer easy. <a class="link-u text-char" href="'.route('help.show', 'esp32').'">Full guide</a></p>', 'cta' => [route('rfid.readers.index'), 'Pair it']])
    @include('help._step', ['n' => 4, 'title' => 'Do a weigh day', 'body' => '<p>Open <strong>Start weighing</strong> on your phone, scan, punch in the weight on the scale. Each animal pops up with its gain since last time.</p>', 'cta' => [route('rfid.live'), 'Start weighing']])
    @include('help._step', ['n' => 5, 'title' => 'Check what needs you', 'body' => '<p>The Overview and Alerts tab tell you about weight loss, lambs due, low birth weights and more — with what to do about each.</p>', 'cta' => [route('rfid.alerts'), 'See alerts']])
@endsection
@section('aside')
    <div class="rounded-[24px] bg-char text-sand p-6"><div class="font-headline text-3xl">Made for your farm</div><p class="text-sand/70 mt-2 text-sm">Something doesn't fit the way you work? Tell us. We shape Herd Manager around the farmers using it.</p><a href="{{ route('herd.suggest') }}" class="btn-light btn-sm mt-4">Suggest something</a></div>
@endsection
