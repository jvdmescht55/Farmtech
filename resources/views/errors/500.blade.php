@include('errors.layout', ['code' => '500 · Our mistake', 'title' => 'Something broke', 'photo' => 'windpomp-storm',
    'headline' => 'Eish. That one\'s on us.',
    'body' => "Something went wrong on our side and we've been told about it. Your data is safe. Try again in a minute.",
    'links' => [['javascript:location.reload()', 'Try again', true], [url('/'), 'Home', false], [url('/contact'), 'Tell us what happened', false]]])
