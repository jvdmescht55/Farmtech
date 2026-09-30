@props(['keys' => [], 'dark' => false])
@php($items = \App\Support\SiteImages::only($keys))
@if ($items)
    <p {{ $attributes->merge(['class' => 'text-[11px] leading-relaxed '.($dark ? 'text-white/40' : 'text-ink-muted')]) }}>
        Photos:
        @foreach ($items as $key => $img)
            <a href="{{ $img['source'] }}" target="_blank" rel="noopener" class="hover:underline">{{ $img['credit'] }}</a>
            (<a href="{{ $img['license_url'] }}" target="_blank" rel="noopener" class="hover:underline">{{ $img['license'] }}</a>){{ $loop->last ? '' : ' · ' }}
        @endforeach
    </p>
@endif
