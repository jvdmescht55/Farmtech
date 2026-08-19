<?php

namespace App\Enums;

/**
 * Single source of truth for product categories — label, storefront copy,
 * representative photo (see resources/data/category-images.php for
 * attribution), and the HS-code heading the AI vetting pipeline should use.
 * Was a rigid DB enum; adding "fencing" and "solar_pumps" here (plus the
 * matching migration to widen the column, and vetting rules in
 * worker/src/prompts/vettingPrompt.js) is now a one-file change instead of
 * a hunt through six controllers and views.
 */
enum ProductCategory: string
{
    case Scales = 'scales';
    case Ultrasound = 'ultrasound';
    case Rfid = 'rfid';
    case Accessories = 'accessories';
    case Fencing = 'fencing';
    case SolarPumps = 'solar_pumps';

    public function label(): string
    {
        return match ($this) {
            self::Scales => 'Livestock Scales & Load Cells',
            self::Ultrasound => 'Veterinary Ultrasound Scanners',
            self::Rfid => 'RFID Readers & Ear Tagging',
            self::Accessories => 'Probes & Accessories',
            self::Fencing => 'Electric Fencing & Energizers',
            self::SolarPumps => 'Solar Water Pumps & Irrigation',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Scales => 'Scales',
            self::Ultrasound => 'Ultrasound',
            self::Rfid => 'RFID & Tagging',
            self::Accessories => 'Accessories',
            self::Fencing => 'Fencing',
            self::SolarPumps => 'Solar & Water',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Scales => 'Digital weighing indicators and platform scales built for the crush, race, or loading ramp — 220V/50Hz or battery powered, with load-cell sensitivity confirmed before listing.',
            self::Ultrasound => 'Handheld pregnancy-diagnosis scanners with probe type confirmed for cattle, sheep, or swine — rectal linear, convex, or mechanical sector.',
            self::Rfid => 'Handheld and stick readers checked against the 134.2 kHz ISO 11784/11785 livestock standard — the only frequency that reads standard SA ear tags.',
            self::Accessories => 'Replacement parts and accessories for the scanners and readers above.',
            self::Fencing => 'Energizers and fencing kits checked for power source, joule output, and NRCS electrical-safety compliance — the standard SA requires before an energizer can legally be sold or imported.',
            self::SolarPumps => 'Solar-driven borehole and irrigation pumps checked for voltage, flow rate, and head — built for load-shedding-proof water supply on livestock and crop farms.',
        };
    }

    /** 6-digit international HS heading the AI vetting prompt should classify under (see README for SARS sourcing). */
    public function hsCodeHint(): string
    {
        return match ($this) {
            self::Scales => '8423.82',
            self::Ultrasound => '9018.12',
            self::Rfid, self::Accessories => '8471.90',
            self::Fencing => '8543.70',
            self::SolarPumps => '8413.70',
        };
    }

    /**
     * Real (non-AI-generated) representative photo + required CC attribution
     * — see resources/data/category-images.php.
     *
     * @return array{url: string, credit: string, license: string, license_url: string, source_url: string}
     */
    public function image(): array
    {
        static $images = null;
        $images ??= require resource_path('data/category-images.php');

        return $images[$this->value];
    }

    /** @return array{url: string, credit: string, license: string, license_url: string, source_url: string} */
    public static function heroFallbackImage(): array
    {
        static $images = null;
        $images ??= require resource_path('data/category-images.php');

        return $images['hero_fallback'];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
