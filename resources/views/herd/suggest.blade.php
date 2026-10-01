@extends('layouts.herd', ['module' => null])
@section('title', 'Suggestions')
@section('eyebrow')Herd Manager is built around the farmers using it @endsection

@section('content')
<div class="grid xl:grid-cols-5 gap-6">
    <form method="POST" action="{{ route('herd.suggest.store') }}" class="xl:col-span-2 panel p-6 sm:p-8 space-y-5 self-start" x-data="{ kind: '{{ old('kind', request('kind', 'software')) }}' }">
        @csrf
        <div class="font-headline text-4xl">What should we build for you?</div>
        <p class="text-stone">No idea is too small, oom. A missing column, a sensor you wish existed, or a whole device for the way <em>your</em> farm works.</p>
        <div class="grid gap-2">
            @foreach (\App\Models\Suggestion::KINDS as $k => $l)
                <label class="flex items-center gap-3 rounded-xl border px-4 py-3 cursor-pointer transition" :class="kind === '{{ $k }}' ? 'border-char bg-sand-light' : 'border-hairline'">
                    <input type="radio" name="kind" value="{{ $k }}" x-model="kind" class="text-char"> <span>{{ $l }}</span>
                </label>
            @endforeach
        </div>
        <div><label class="field-label">In one line</label><input name="title" value="{{ old('title') }}" required maxlength="160" placeholder="e.g. Count how many lambs each ewe weans" class="field"></div>
        <div><label class="field-label">Tell us more <span class="font-normal text-stone-light">(how you farm, what you do now, what would help)</span></label><textarea name="details" rows="5" class="field">{{ old('details') }}</textarea></div>
        <button class="btn-dark w-full">Send it</button>
    </form>

    <div class="xl:col-span-3">
        <div class="panel overflow-hidden">
            <div class="panel-head"><div class="panel-title">Your suggestions</div></div>
            <ul class="divide-y divide-hairline">
                @forelse ($mine as $s)
                    <li class="px-6 py-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-medium">{{ $s->title }}</span>
                            <span class="chip {{ in_array($s->status, ['building', 'done']) ? 'bg-ochre/15 text-ochre-dark' : 'bg-sand-deep text-stone' }}">{{ \App\Models\Suggestion::STATUSES[$s->status] }}</span>
                            <span class="ml-auto text-xs text-stone">{{ $s->created_at->format('j M Y') }}</span>
                        </div>
                        <div class="text-xs text-stone mt-1">{{ \App\Models\Suggestion::KINDS[$s->kind] }}</div>
                        @if ($s->reply)<div class="mt-3 rounded-xl bg-sand-light px-4 py-3 text-sm"><span class="text-stone">Farmtech:</span> {{ $s->reply }}</div>@endif
                    </li>
                @empty
                    <li class="px-6 py-16 text-center text-stone">Nothing yet — your ideas will show here with what we're doing about them.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
