{{-- @include('help._step', ['n' => 1, 'title' => …, 'body' => html, 'cta' => [url, label]?]) --}}
<div class="panel p-6 sm:p-7 flex gap-5">
    <span class="w-10 h-10 shrink-0 rounded-full bg-char text-sand grid place-items-center font-headline text-xl">{{ $n }}</span>
    <div class="min-w-0 flex-1">
        <div class="font-headline text-2xl leading-tight">{{ $title }}</div>
        <div class="mt-2 text-stone leading-relaxed space-y-3 [&_strong]:text-char [&_code]:font-num [&_code]:text-char [&_code]:bg-sand-deep [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:rounded">{!! $body !!}</div>
        @isset($cta)<a href="{{ $cta[0] }}" class="btn-dark btn-sm mt-4">{{ $cta[1] }}</a>@endisset
    </div>
</div>
