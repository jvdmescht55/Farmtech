{{-- One top-level tab. $on = active, $badge = optional count. --}}
<a href="{{ $href }}" class="relative shrink-0 py-3.5 text-[14px] flex items-center gap-1.5 transition-colors {{ $on ? 'text-sand' : 'text-sand/55 hover:text-sand' }}">
    {{ $label }}
    @if (! empty($badge))<span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-ochre text-char text-[11px] font-semibold grid place-items-center">{{ $badge }}</span>@endif
    @if ($on)<span class="absolute inset-x-0 -bottom-px h-[2px] bg-ochre rounded-full"></span>@endif
</a>
