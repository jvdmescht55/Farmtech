@props(['tier' => null])
<span {{ $attributes->merge(['class' => 'tier '.($tier ? 'tier-'.$tier : 'tier-unknown')]) }} title="{{ $tier ? (config('herd.tier_labels')[$tier] ?? $tier) : 'Pedigree incomplete' }}">{{ $tier ?? '?' }}</span>
