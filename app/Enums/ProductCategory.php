<?php

namespace App\Enums;

enum ProductCategory: string
{
    case Scales = 'scales';
    case Ultrasound = 'ultrasound';
    case Rfid = 'rfid';
    case SmartIrrigation = 'smart_irrigation';
    case PrecisionGuidance = 'precision_guidance';
    case Accessories = 'accessories';
    case LaserLevels = 'laser_levels';
    case MoistureMeters = 'moisture_meters';
    case RebarDetectors = 'rebar_detectors';
    case Theodolites = 'theodolites';
    case ThermalDiagnostics = 'thermal_diagnostics';
    case PlatformScales = 'platform_scales';
    case FleetTrackers = 'fleet_trackers';
    case VehicleAccessories = 'vehicle_accessories';
    case FuelMonitoring = 'fuel_monitoring';
    case IndustrialRfid = 'industrial_rfid';
    case SolarPumps = 'solar_pumps';
    case MpptControllers = 'mppt_controllers';
    case Fencing = 'fencing';
    case ThermalNightVisionOptics = 'thermal_night_vision_optics';
    case GameTrailCameras = 'game_trail_cameras';
    case GameFeeders = 'game_feeders';
    case RangefindersBallistic = 'rangefinders_ballistic';
    case WildlifeTracking = 'wildlife_tracking';

    public function industry(): Industry
    {
        return match ($this) {
            self::Scales, self::Ultrasound, self::Rfid, self::SmartIrrigation, self::PrecisionGuidance, self::Accessories => Industry::Agriculture,
            self::LaserLevels, self::MoistureMeters, self::RebarDetectors, self::Theodolites, self::ThermalDiagnostics => Industry::Construction,
            self::PlatformScales, self::FleetTrackers, self::VehicleAccessories, self::FuelMonitoring, self::IndustrialRfid => Industry::IndustrialLogistics,
            self::SolarPumps, self::MpptControllers, self::Fencing => Industry::SolarPower,
            self::ThermalNightVisionOptics, self::GameTrailCameras, self::GameFeeders,
            self::RangefindersBallistic, self::WildlifeTracking => Industry::Hunting,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Scales => 'Scale Indicators & Platform Kits',
            self::Ultrasound => 'Veterinary Pregnancy Ultrasound',
            self::Rfid => 'RFID Tags & Stick Readers',
            self::SmartIrrigation => 'Smart Irrigation Controllers',
            self::PrecisionGuidance => 'Tractor GPS & Autosteer Systems',
            self::Accessories => 'Probes & Accessories',
            self::LaserLevels => 'Rotary Laser Levels & Detectors',
            self::MoistureMeters => 'Concrete & Grain Moisture Meters',
            self::RebarDetectors => 'Rebar Detectors & Cover Meters',
            self::Theodolites => 'Digital Theodolites',
            self::ThermalDiagnostics => 'Thermal Cameras & Diagnostics',
            self::PlatformScales => 'Crane & Platform Scale Indicators',
            self::FleetTrackers => 'Real-Time GPS/OBD Trackers',
            self::VehicleAccessories => 'Bakkie & 4x4 Electrical Upgrades',
            self::FuelMonitoring => 'Ultrasonic Fuel & Tank Monitors',
            self::IndustrialRfid => 'Industrial RFID Fixed Gate Scanners',
            self::SolarPumps => 'Submersible Borehole Pumps',
            self::MpptControllers => 'MPPT Digital Inverter Regulators',
            self::Fencing => 'Solar Electric Fencing',
            self::ThermalNightVisionOptics => 'Thermal & Night Vision Optics',
            self::GameTrailCameras => 'Game & Trail Cameras',
            self::GameFeeders => 'Game Feeders & Timers',
            self::RangefindersBallistic => 'Rangefinders & Ballistic Tech',
            self::WildlifeTracking => 'Wildlife Tracking & Radio Collars',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Scales => 'Scales',
            self::Ultrasound => 'Ultrasound',
            self::Rfid => 'RFID & Tagging',
            self::SmartIrrigation => 'Smart Irrigation',
            self::PrecisionGuidance => 'Autosteer & GPS',
            self::Accessories => 'Accessories',
            self::LaserLevels => 'Laser Levels',
            self::MoistureMeters => 'Moisture Meters',
            self::RebarDetectors => 'Rebar Detectors',
            self::Theodolites => 'Theodolites',
            self::ThermalDiagnostics => 'Thermal Cameras',
            self::PlatformScales => 'Platform Scales',
            self::FleetTrackers => 'Fleet Trackers',
            self::VehicleAccessories => 'Vehicle Tech',
            self::FuelMonitoring => 'Fuel Monitors',
            self::IndustrialRfid => 'Industrial RFID',
            self::SolarPumps => 'Solar Pumps',
            self::MpptControllers => 'MPPT Controllers',
            self::Fencing => 'Fencing',
            self::ThermalNightVisionOptics => 'Thermal & Night Vision',
            self::GameTrailCameras => 'Trail Cameras',
            self::GameFeeders => 'Game Feeders',
            self::RangefindersBallistic => 'Rangefinders',
            self::WildlifeTracking => 'Wildlife Tracking',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Scales, self::PlatformScales => 'scale',
            self::Ultrasound => 'ultrasound',
            self::Rfid, self::IndustrialRfid => 'rfid',
            self::SmartIrrigation, self::FuelMonitoring => 'irrigation',
            self::Accessories, self::VehicleAccessories => 'accessories',
            self::LaserLevels, self::ThermalDiagnostics => 'laser',
            self::MoistureMeters => 'moisture',
            self::RebarDetectors => 'rebar',
            self::Theodolites, self::PrecisionGuidance => 'theodolite',
            self::FleetTrackers => 'gps',
            self::SolarPumps => 'solar',
            self::MpptControllers => 'mppt',
            self::Fencing => 'fencing',
            self::ThermalNightVisionOptics => 'thermal-optic',
            self::GameTrailCameras => 'trail-camera',
            self::GameFeeders => 'feeder',
            self::RangefindersBallistic => 'rangefinder',
            self::WildlifeTracking => 'wildlife-tracking',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Scales => 'Digital weighing indicators and platform scales built for the crush, race, or loading ramp.',
            self::Ultrasound => 'Handheld pregnancy-diagnosis scanners with probe type confirmed for cattle, sheep, or swine.',
            self::Rfid => 'Handheld and stick readers checked against the 134.2 kHz ISO 11784/11785 livestock standard.',
            self::SmartIrrigation => 'Sensor-driven irrigation controllers and valves checked for power source and IP rating.',
            self::PrecisionGuidance => 'High-precision RTK tractor auto-steering guidance kits and agricultural drone accessories.',
            self::Accessories => 'Replacement parts and accessories for the scanners and readers above.',
            self::LaserLevels => 'Self-levelling rotary and line lasers checked for laser class and stated working range.',
            self::MoistureMeters => 'Pin and pinless grain and concrete moisture meters checked for calibration.',
            self::RebarDetectors => 'Cover meters and rebar locators checked for detection depth and accuracy tolerance.',
            self::ThermalDiagnostics => 'Handheld thermal cameras and infrared diagnostics for farm machinery and electrical panels.',
            self::Theodolites => 'Digital theodolites and total stations checked for angular accuracy.',
            self::PlatformScales => 'Crane scales and industrial platform indicators checked for load capacity.',
            self::FleetTrackers => 'GPS/OBD vehicle trackers checked for ICASA cellular module approval.',
            self::VehicleAccessories => 'Heavy-duty 12V accessories, dual-battery split relays, head units, and electrical kits for Hilux and farm bakkies.',
            self::FuelMonitoring => 'Non-invasive ultrasonic diesel tank level sensors and high-accuracy digital flow meters.',
            self::IndustrialRfid => 'Fixed and handheld UHF RFID gate/access scanners.',
            self::SolarPumps => 'Solar-driven borehole and irrigation pumps checked for voltage, flow rate, and head.',
            self::MpptControllers => 'MPPT solar charge and hybrid inverter controllers.',
            self::Fencing => 'Energizers and fencing kits checked for power source, joule output, and NRCS compliance.',
            self::ThermalNightVisionOptics => 'Thermal and night-vision monoculars, scopes, and clip-ons checked for IP65+ weatherproofing and battery runtime before listing.',
            self::GameTrailCameras => 'Game and trail scouting cameras checked for SA-compatible 4G/LTE cellular bands (B1/B3/B8/B20/B40) or true solar/battery standalone operation, and IP65+ ingress protection.',
            self::GameFeeders => 'Programmable game and livestock feeders with timer units checked for weatherproof housing and 12V/battery power compatibility.',
            self::RangefindersBallistic => 'Laser rangefinders and ballistic calculators checked for stated range accuracy and IP65+ weatherproofing.',
            self::WildlifeTracking => 'GPS/radio wildlife tracking collars checked for battery life, SA-legal transmission frequency, and collar durability rating.',
        };
    }

    public function hsCodeHint(): string
    {
        return match ($this) {
            self::Scales, self::PlatformScales => '8423.82',
            self::Ultrasound => '9018.12',
            self::Rfid, self::Accessories, self::IndustrialRfid => '8471.90',
            self::SmartIrrigation => '8424.82',
            self::PrecisionGuidance => '9015.80',
            self::LaserLevels => '9015.30',
            self::MoistureMeters, self::ThermalDiagnostics => '9027.80',
            self::RebarDetectors => '9031.80',
            self::Theodolites => '9015.20',
            self::FleetTrackers => '8526.91',
            self::VehicleAccessories => '8708.29',
            self::FuelMonitoring => '9026.10',
            self::Fencing => '8543.70',
            self::SolarPumps => '8413.70',
            self::MpptControllers => '8504.40',
            self::ThermalNightVisionOptics => '9013.80',
            self::GameTrailCameras => '8525.89',
            self::GameFeeders => '8543.70',
            self::RangefindersBallistic => '9015.80',
            self::WildlifeTracking => '8526.91',
        };
    }

    public function image(): array
    {
        static $images = null;
        $images ??= require resource_path('data/category-images.php');
        return $images[$this->value] ?? $images['hero_fallback'];
    }

    public static function heroFallbackImage(): array
    {
        static $images = null;
        $images ??= require resource_path('data/category-images.php');
        return $images['hero_fallback'];
    }

    public static function options(): array
    {
        return array_map(fn (self $case) => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
