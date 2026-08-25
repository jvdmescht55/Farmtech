<?php

namespace App\Support;

/**
 * Presentation-only cleanup of raw Alibaba spec attribute LABELS (never the
 * values — those are the real technical facts and stay untouched) for
 * display in the admin/storefront spec tables. Deliberately not an AI call:
 * this only reformats label text (known relabels, stray parenthetical
 * units, casing), so it's safe to run on every render with no risk of
 * altering a technical fact, unlike touching the underlying `specifications`
 * data itself would be.
 */
final class SpecLabelHumanizer
{
    /** Known Chinglish/inconsistent labels -> clean English, keyed lowercase. */
    private const RELABEL = [
        'warranty(year)' => 'Warranty',
        'warranty (year)' => 'Warranty',
        'warranty(years)' => 'Warranty',
        'after sale service' => 'After-Sales Service',
        'after sales service' => 'After-Sales Service',
        'after-sale service' => 'After-Sales Service',
        'after service' => 'After-Sales Service',
        'place of origin' => 'Place of Origin',
        'model no' => 'Model Number',
        'model no.' => 'Model Number',
        'model name' => 'Model Number',
        'power source' => 'Power Source',
        'power supply' => 'Power Supply',
        'input voltage' => 'Input Voltage',
        'output voltage' => 'Output Voltage',
        'net weight' => 'Net Weight',
        'gross weight' => 'Gross Weight',
        'product name' => 'Product Name',
        'usage' => 'Application',
        'application' => 'Application',
        'certificate' => 'Certification',
        'certification' => 'Certification',
        'feature' => 'Key Features',
        'keywords' => 'Key Features',
        'oem odm' => 'Custom Branding',
        'oem/odm' => 'Custom Branding',
        'moq' => 'Minimum Order Quantity',
    ];

    public static function humanize(string $rawKey): string
    {
        $key = trim($rawKey);
        if ($key === '') {
            return $rawKey;
        }

        $lower = strtolower($key);
        if (isset(self::RELABEL[$lower])) {
            return self::RELABEL[$lower];
        }

        // Strip a trailing parenthetical unit/qualifier Alibaba sometimes
        // bakes into the label itself rather than the value
        // ("Warranty(year)", "Weight(kg)") — generic, not a per-unit list.
        $stripped = preg_replace('/\s*\([a-z%\/\.]+\)\s*$/i', '', $key);
        $stripped = trim((string) $stripped);
        $key = $stripped !== '' ? $stripped : $key;

        // Title-case each word, but leave existing all-caps acronyms
        // (IP65, LCD, GPS, RFID, NPK...) untouched rather than mangling
        // them into "Ip65"/"Lcd".
        $words = preg_split('/\s+/', $key) ?: [$key];
        $words = array_map(
            fn (string $word) => preg_match('/^[A-Z0-9]{2,}$/', $word) ? $word : ucfirst(strtolower($word)),
            $words
        );

        $clean = trim(implode(' ', $words));

        return $clean !== '' ? $clean : $rawKey;
    }
}
