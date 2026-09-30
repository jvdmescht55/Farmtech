{{-- One box in the pedigree tree. $a may be null (unrecorded). --}}
<div class="rounded-lg border {{ $a ? 'border-border bg-white' : 'border-dashed border-ink-muted/40 bg-canvas' }} px-3 py-2 {{ $class ?? '' }}">
    <div class="text-[10px] uppercase tracking-wide text-ink-muted">{{ $role }}</div>
    @if ($a)
        <div class="flex items-center gap-2 mt-0.5">
            @if ($a->in_herd)
                <a href="{{ route('rfid.animals.show', $a) }}" class="font-mono text-sm font-semibold text-brand-900 hover:underline truncate">{{ $a->visual_id }}</a>
            @else
                <span class="font-mono text-sm font-semibold truncate">{{ $a->visual_id }}</span>
            @endif
            @if ($a->birth_type)<span class="text-[10px] text-ink-muted">({{ $a->birth_type }})</span>@endif
            <x-tier :tier="$a->tierResult()->tier" class="ml-auto" />
        </div>
    @else
        <div class="text-sm text-ink-muted mt-0.5">Not recorded</div>
    @endif
</div>
