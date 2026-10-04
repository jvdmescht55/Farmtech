@include('errors.layout', ['code' => '404 · Not found', 'title' => 'Not found', 'photo' => 'dirt-road',
    'headline' => 'This road goes nowhere, boet.',
    'body' => "The page you're looking for isn't here. Maybe it moved, or the link has a typo.",
    'links' => [[url('/'), 'Take me home', true], [url('/app'), 'Open Herd Manager', false], [url('/contact'), 'Talk to a person', false]]])
