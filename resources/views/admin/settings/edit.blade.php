@extends('layouts.admin')
@section('heading', 'Settings')
@section('content')
<div class="grid xl:grid-cols-5 gap-6 items-start">
    <div class="xl:col-span-3 panel overflow-hidden">
        <div class="panel-head"><div class="panel-title">Shop &amp; email setup</div></div>
        <ul class="divide-y divide-hairline">
            @foreach ($checks as [$label, $ok, $keys])
                <li class="px-5 py-4 flex gap-4">
                    <span class="mt-0.5 w-6 h-6 shrink-0 rounded-full grid place-items-center text-xs {{ $ok ? 'bg-[#3F7A3A] text-white' : 'bg-ochre/20 text-ochre-dark' }}">{{ $ok ? '✓' : '!' }}</span>
                    <div class="min-w-0"><div class="font-medium">{{ $label }} <span class="text-sm font-normal {{ $ok ? 'text-[#3F7A3A]' : 'text-ochre-dark' }}">{{ $ok ? 'set up' : 'not set up yet' }}</span></div>
                        <div class="text-xs text-stone mt-1 break-words">In <code>.env</code>: {{ $keys }}</div></div>
                </li>
            @endforeach
        </ul>
        <p class="px-5 py-4 text-sm text-stone bg-sand-light">These are kept in the server's <code>.env</code> file because they're secret. After changing it, run <code>php artisan config:cache</code>.</p>
    </div>
    <form action="{{ route('admin.settings.update') }}" method="POST" class="xl:col-span-2 panel p-6 space-y-4">
        @csrf @method('PUT')
        <div class="panel-title">Support WhatsApp</div>
        <div><label class="field-label">WhatsApp number</label><input name="support_whatsapp" value="{{ $whatsapp }}" placeholder="+27821234567" class="field"></div>
        <p class="text-xs text-stone">Digits only, optional leading +. Leave blank to hide it.</p>
        <button class="btn-dark">Save</button>
    </form>
</div>
@endsection
