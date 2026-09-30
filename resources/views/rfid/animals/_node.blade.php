{{-- One box in the pedigree tree. $a may be null (unrecorded). --}}
<div class="rounded-lg border {{ $a ? 'border-hairline bg-white' : 'border-dashed border-ink-muted/40 bg-sand-light' }} px-3 py-2 {{ $class ?? '' }}">
    <div class="text-[10px] uppercase tracking-wide text-stone-light">{{ $role }}</div>
    @if ($a)
        <div class="flex items-center gap-2 mt-0.5">
            @if ($a->in_herd)
                <a href="{{ route('rfid.animals.show', $a) }}" class="font-mono text-sm font-medium text-char hover:underline truncate">{{ $a->visual_id }}</a>
            @else
                <span class="font-mono text-sm font-medium truncate">{{ $a->visual_id }}</span>
            @endif
            @if ($a->birth_type)<span class="text-[10px] text-stone-light">({{ $a->birth_type }})</span>@endif
            <x-tier :tier="$a->tierResult()->tier" class="ml-auto" />
        </div>
    @else
        <div class="text-sm text-stone-light mt-0.5">Not recorded</div>
    @endif
</div>
