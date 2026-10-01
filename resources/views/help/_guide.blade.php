{{-- Shared guide frame. Child defines @section('steps'). --}}
@extends('layouts.herd', ['module' => null])
@section('title', $meta[0])
@section('eyebrow')<a href="{{ route('help.index') }}" class="hover:text-char">← All guides</a> · {{ $meta[1] }} @endsection
@section('content')
<div class="grid lg:grid-cols-12 gap-8">
    <div class="lg:col-span-8 space-y-4">@yield('steps')</div>
    <aside class="lg:col-span-4 space-y-4 lg:sticky lg:top-40 self-start">
        @yield('aside')
        <div class="panel p-6">
            <div class="kpi-label mb-3">Other guides</div>
            <ul class="space-y-2 text-sm">
                @foreach (\App\Http\Controllers\HelpController::GUIDES as $k => [$t])
                    @if ($k !== $guide)<li><a href="{{ route('help.show', $k) }}" class="link-u">{{ $t }}</a></li>@endif
                @endforeach
            </ul>
        </div>
    </aside>
</div>
@endsection
