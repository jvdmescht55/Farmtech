@include('errors.layout', ['code' => '419 · Page expired', 'title' => 'Page expired', 'photo' => 'karoo-mist',
    'headline' => 'That page sat a bit long.',
    'body' => "For your safety, forms expire after a while. Go back, refresh the page and try again. Nothing was saved twice.",
    'links' => [['javascript:history.back()', 'Go back', true], [url('/app'), 'Herd Manager', false]]])
