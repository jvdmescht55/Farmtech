@include('errors.layout', ['code' => '403 · No access', 'title' => 'No access', 'photo' => 'windmill-red',
    'headline' => 'This gate is locked.',
    'body' => "Your account doesn't have access to this part. If you've bought the device, activate it with the card in the box.",
    'links' => [[url('/activate'), 'Activate a device', true], [url('/app'), 'Herd Manager', false]]])
