@props(['product'])

@php
    $audit = $product->complianceAudit;
    // Matched on the value's own IPxx pattern rather than a specific spec_key
    // wording, since the AI-generated key varies by category ("IP Rating",
    // "Enclosure Rating", "Ingress Protection") but a real IP rating value
    // always looks like IP54/IP65/IP67.
    $ipRating = null;
    foreach ($product->specs as $spec) {
        if (preg_match('/\bIP\s?(\d{2})\b/i', $spec->spec_value, $m)) {
            $ipRating = 'IP'.$m[1];
            break;
        }
    }

    // Every entry here is backed by a real field the pipeline actually
    // recorded for THIS product — never a fixed "always show 4 badges" set.
    // A product with fewer verified attributes simply shows fewer badges.
    $badges = collect([
        $audit?->icasa_status ? match ($audit->icasa_status) {
            'pre_approved' => ['label' => 'ICASA Type Approved', 'tone' => 'green'],
            'exempt' => ['label' => 'ICASA Exempt', 'tone' => 'slate'],
            'requires_permit' => ['label' => 'ICASA Permit Required', 'tone' => 'amber'],
            default => null,
        } : null,
        $audit?->plug_type_checked ? ['label' => 'Power Spec Verified', 'tone' => 'green'] : null,
        ($audit?->frequency_checked && str_contains($audit->frequency_checked, '134.2') && $product->category->value === 'rfid')
            ? ['label' => 'ISO 11784/11785 Compliant', 'tone' => 'green'] : null,
        $audit?->battery_transport_cert ? ['label' => 'Battery Transport Certified', 'tone' => 'green'] : null,
        $ipRating ? ['label' => $ipRating.' Ingress Protection', 'tone' => 'green', 'mono' => true] : null,
    ])->filter()->values();

    $toneClasses = [
        'green' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'amber' => 'bg-precision/10 border-precision/30 text-precision-dark',
        'slate' => 'bg-canvas border-border text-ink-secondary',
    ];
@endphp

@if ($badges->isNotEmpty())
    <div class="flex flex-wrap gap-2 mt-4">
        @foreach ($badges as $badge)
            <span class="inline-flex items-center gap-1.5 border rounded-full px-3 py-1.5 text-xs font-semibold {{ $toneClasses[$badge['tone']] }} {{ $badge['mono'] ?? false ? 'font-mono' : '' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                {{ $badge['label'] }}
            </span>
        @endforeach
    </div>
@endif
