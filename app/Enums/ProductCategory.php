<?php

namespace App\Enums;

/**
 * Single source of truth for product categories — label, storefront copy,
 * representative photo (see resources/data/category-images.php for
 * attribution), the HS-code heading the AI vetting pipeline should use, and
 * which Industry (see Industry.php) it belongs to. Was livestock-only; now
 * spans agriculture, construction, industrial/logistics, and solar power —
 * adding a category here (plus a matching entry in category-images.php and
 * a category-specific ruleset in worker/src/prompts/vettingPrompt.js) is a
 * few-file change rather than a schema migration, since `category` is a
 * plain string column, not a DB enum.
 */
enum ProductCategory: string
{
    // Agriculture
    case Scales = 'scales';
    case Ultrasound = 'ultrasound';
    case Rfid = 'rfid';
    case SmartIrrigation = 'smart_irrigation';
    case Accessories = 'accessories';

    // Construction
    case LaserLevels = 'laser_levels';
    case MoistureMeters = 'moisture_meters';
    case RebarDetectors = 'rebar_detectors';
    case Theodolites = 'theodolites';

    // Industrial & Logistics
    case PlatformScales = 'platform_scales';
    case FleetTrackers = 'fleet_trackers';
    case IndustrialRfid = 'industrial_rfid';

    // Solar Power
    case SolarPumps = 'solar_pumps';
    case MpptControllers = 'mppt_controllers';
    case Fencing = 'fencing';

    public function industry(): Industry
    {
        return match ($this) {
            self::Scales, self::Ultrasound, self::Rfid, self::SmartIrrigation, self::Accessories => Industry::Agriculture,
            self::LaserLevels, self::MoistureMeters, self::RebarDetectors, self::Theodolites => Industry::Construction,
            self::PlatformScales, self::FleetTrackers, self::IndustrialRfid => Industry::IndustrialLogistics,
            self::SolarPumps, self::MpptControllers, self::Fencing => Industry::SolarPower,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Scales => 'Scale Indicators & Platform Kits',
            self::Ultrasound => 'Veterinary Pregnancy Ultrasound',
            self::Rfid => 'RFID Tags & Stick Readers',
            self::SmartIrrigation => 'Smart Irrigation Controllers',
            self::Accessories => 'Probes & Accessories',
            self::LaserLevels => 'Rotary Laser Levels & Detectors',
            self::MoistureMeters => 'Concrete Moisture Meters',
            self::RebarDetectors => 'Rebar Detectors & Cover Meters',
            self::Theodolites => 'Digital Theodolites',
            self::PlatformScales => 'Crane & Platform Scale Indicators',
            self::FleetTrackers => 'Real-Time GPS/OBD Trackers',
            self::IndustrialRfid => 'Industrial RFID Fixed Gate Scanners',
            self::SolarPumps => 'Submersible Borehole Pumps',
            self::MpptControllers => 'MPPT Digital Inverter Regulators',
            self::Fencing => 'Solar Electric Fencing',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Scales => 'Scales',
            self::Ultrasound => 'Ultrasound',
            self::Rfid => 'RFID & Tagging',
            self::SmartIrrigation => 'Smart Irrigation',
            self::Accessories => 'Accessories',
            self::LaserLevels => 'Laser Levels',
            self::MoistureMeters => 'Moisture Meters',
            self::RebarDetectors => 'Rebar Detectors',
            self::Theodolites => 'Theodolites',
            self::PlatformScales => 'Platform Scales',
            self::FleetTrackers => 'Fleet Trackers',
            self::IndustrialRfid => 'Industrial RFID',
            self::SolarPumps => 'Solar Pumps',
            self::MpptControllers => 'MPPT Controllers',
            self::Fencing => 'Fencing',
        };
    }

    /** Icon key for resources/views/components/category-icon.blade.php — a real inline SVG per category, not a generic placeholder. */
    public function icon(): string
    {
        return match ($this) {
            self::Scales, self::PlatformScales => 'scale',
            self::Ultrasound => 'ultrasound',
            self::Rfid, self::IndustrialRfid => 'rfid',
            self::SmartIrrigation => 'irrigation',
            self::Accessories => 'accessories',
            self::LaserLevels => 'laser',
            self::MoistureMeters => 'moisture',
            self::RebarDetectors => 'rebar',
            self::Theodolites => 'theodolite',
            self::FleetTrackers => 'gps',
            self::SolarPumps => 'solar',
            self::MpptControllers => 'mppt',
            self::Fencing => 'fencing',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Scales => 'Digital weighing indicators and platform scales built for the crush, race, or loading ramp — 220V/50Hz or battery powered, with load-cell sensitivity confirmed before listing.',
            self::Ultrasound => 'Handheld pregnancy-diagnosis scanners with probe type confirmed for cattle, sheep, or swine — rectal linear, convex, or mechanical sector.',
            self::Rfid => 'Handheld and stick readers checked against the 134.2 kHz ISO 11784/11785 livestock standard — the only frequency that reads standard SA ear tags.',
            self::SmartIrrigation => 'Sensor-driven irrigation controllers and valves checked for power source and IP rating before listing — built for load-shedding-proof, water-wise crop irrigation.',
            self::Accessories => 'Replacement parts and accessories for the scanners and readers above.',
            self::LaserLevels => 'Self-levelling rotary and line lasers checked for laser class (2 or 3R) and stated working range before listing — the safety spec SA site teams need to know before use.',
            self::MoistureMeters => 'Pin and pinless concrete/timber moisture meters checked for measurement range and calibration reference before listing.',
            self::RebarDetectors => 'Cover meters and rebar locators checked for detection depth and accuracy tolerance before listing — for slab scanning before coring or drilling.',
            self::Theodolites => 'Digital theodolites and total stations checked for angular accuracy and IP rating before listing — site-survey grade equipment.',
            self::PlatformScales => 'Crane scales and industrial platform indicators checked for load capacity and calibration certificate before listing — for weighbridges, cranes, and warehouse floor scales.',
            self::FleetTrackers => 'GPS/OBD vehicle trackers checked for ICASA cellular module approval and power source before listing — for fleet and asset tracking.',
            self::IndustrialRfid => 'Fixed and handheld UHF RFID gate/access scanners checked for ICASA approval and read range before listing — for warehouse, yard, and access-control use, distinct from the 134.2 kHz livestock standard above.',
            self::SolarPumps => 'Solar-driven borehole and irrigation pumps checked for voltage, flow rate, and head — built for load-shedding-proof water supply on livestock and crop farms.',
            self::MpptControllers => 'MPPT solar charge/inverter controllers checked for input voltage range and rated current before listing — for off-grid and load-shedding backup solar setups.',
            self::Fencing => 'Energizers and fencing kits checked for power source, joule output, and NRCS electrical-safety compliance — the standard SA requires before an energizer can legally be sold or imported.',
        };
    }

    /** 6-digit international HS heading the AI vetting prompt should classify under (see README for SARS sourcing). */
    public function hsCodeHint(): string
    {
        return match ($this) {
            self::Scales, self::PlatformScales => '8423.82',
            self::Ultrasound => '9018.12',
            self::Rfid, self::Accessories, self::IndustrialRfid => '8471.90',
            self::SmartIrrigation => '8424.82',
            self::LaserLevels => '9015.30',
            self::MoistureMeters => '9027.80',
            self::RebarDetectors => '9031.80',
            self::Theodolites => '9015.20',
            self::FleetTrackers => '8526.91',
            self::Fencing => '8543.70',
            self::SolarPumps => '8413.70',
            self::MpptControllers => '8504.40',
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
