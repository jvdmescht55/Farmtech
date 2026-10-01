@extends('help._guide')
@section('steps')
    @include('help._step', ['n' => 1, 'title' => 'Add the device', 'body' => '<p>Custom devices → <strong>Add a device</strong>. Name it and list what it measures (e.g. Tank level %, min 25). Copy the key it shows you.</p>', 'cta' => [route('custom.index'), 'Custom devices']])
    @include('help._step', ['n' => 2, 'title' => 'Send readings', 'body' => '<p><code>POST https://farmtech.site/api/v1/readings</code> with header <code>Authorization: Bearer &lt;key&gt;</code> and a body like <code>{"tank_level": 72}</code>. A plain GET with <code>?key=…&amp;tank_level=72</code> works too.</p>'])
    @include('help._step', ['n' => 3, 'title' => 'Watch the charts', 'body' => '<p>Each reading gets a chart and the latest value. Go outside your limits and it shows up in alerts.</p>'])
    @include('help._step', ['n' => 4, 'title' => 'Rather have us build it?', 'body' => '<p>Tell us what you need — we build custom devices for farms.</p>', 'cta' => [route('herd.suggest', ['kind' => 'custom_build']), 'Ask us']])
@endsection
