@extends('layouts.admin')
@section('heading', 'Suggestions')
@section('content')
<div class="flex flex-wrap gap-1 mb-6">
    <a href="{{ route('admin.suggestions.index') }}" class="rounded-full px-4 h-9 inline-flex items-center text-sm border {{ ! request('status') ? 'bg-char text-sand border-char' : 'bg-white border-hairline' }}">All</a>
    @foreach (\App\Models\Suggestion::STATUSES as $k => $l)
        <a href="{{ route('admin.suggestions.index', ['status' => $k]) }}" class="rounded-full px-4 h-9 inline-flex items-center text-sm border {{ request('status') === $k ? 'bg-char text-sand border-char' : 'bg-white border-hairline' }}">{{ $l }} <span class="ml-1 opacity-60">{{ $counts[$k] ?? 0 }}</span></a>
    @endforeach
</div>
<div class="space-y-4">
    @forelse ($suggestions as $s)
        <form method="POST" action="{{ route('admin.suggestions.update', $s) }}" class="panel p-6 grid lg:grid-cols-[1fr_20rem] gap-6">
            @csrf @method('PATCH')
            <div>
                <div class="flex flex-wrap items-center gap-3"><span class="font-medium text-lg">{{ $s->title }}</span><span class="chip bg-sand-deep text-stone">{{ \App\Models\Suggestion::KINDS[$s->kind] }}</span></div>
                <div class="text-sm text-stone mt-1">{{ $s->name ?? $s->user?->name }} · {{ $s->email ?? $s->user?->email }}{{ $s->user?->farm_name ? ' · '.$s->user->farm_name : '' }} · {{ $s->created_at->format('j M Y') }}{{ $s->user ? ' · customer' : ' · from the website' }}</div>
                @if ($s->details)<p class="mt-3 whitespace-pre-line">{{ $s->details }}</p>@endif
            </div>
            <div class="space-y-2">
                <select name="status" class="field">@foreach (\App\Models\Suggestion::STATUSES as $k => $l)<option value="{{ $k }}" @selected($s->status === $k)>{{ $l }}</option>@endforeach</select>
                <textarea name="reply" rows="3" placeholder="Reply — the customer sees this" class="field text-sm">{{ $s->reply }}</textarea>
                <button class="btn-dark btn-sm w-full">Save</button>
            </div>
        </form>
    @empty
        <div class="panel p-12 text-center text-stone">No suggestions yet. They come from the website and from customers inside Kuddebestuur.</div>
    @endforelse
</div>
<div class="mt-6">{{ $suggestions->links() }}</div>
@endsection
