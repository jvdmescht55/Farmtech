@include('errors.layout', ['code' => '429 · Slow down', 'title' => 'Too many tries', 'photo' => 'karoo-mist',
    'headline' => 'Easy, oom.',
    'body' => "That's a lot of tries in a short time. Wait a minute and give it another go.",
    'links' => [['javascript:history.back()', 'Go back', true]]])
