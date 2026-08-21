@component('mail::message')
# New support request — {{ $topic }}

**From:** {{ $senderName }} ({{ $senderEmail }})

{{ $messageBody }}

@component('mail::button', ['url' => 'mailto:'.$senderEmail])
Reply to {{ $senderName }}
@endcomponent

{{ config('app.name') }}
@endcomponent
