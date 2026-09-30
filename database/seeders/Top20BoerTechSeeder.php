<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Setting;
use App\Services\LandedCostCalculator;
use Illuminate\Database\Seeder;

/**
 * Seeds the "Top 20 High-Utility Boer-Tech" research shortlist into the
 * staging queue (status = pending_review, is_active = false) so an admin can
 * run a real sourcing pass on each one.
 *
 * These are researched category picks, NOT scraped listings, so every field
 * that must be real is left honestly empty: supplier_name / supplier_url /
 * source_url are null, there are no images and no reviews, and each row is
 * tagged verification_tier = 'research_candidate' with a "Listing Status"
 * note. The USD factory cost, weight and target margin are design estimates
 * (the same inputs the admin margin slider takes); landed cost and retail
 * are then computed by the shared LandedCostCalculator against the live
 * settings + USD/ZAR rate, so the numbers match what the admin product page
 * will recompute. Customs duty is a conservative estimate to confirm with
 * the clearing agent at sourcing.
 *
 * Idempotent: keyed on SKU via updateOrCreate, safe to re-run.
 */
class Top20BoerTechSeeder extends Seeder
{
    private const BATCH_TAG = 'Top 20 Boer-Tech · Aug 2026';

    public function run(): void
    {
        $calc = new LandedCostCalculator(
            freightUsdPerKg: (float) Setting::get('air_freight_usd_per_kg', 16),
            domesticDeliveryZar: (float) Setting::get('clearing_agent_fee_zar', 250),
            vatRate: (float) Setting::get('vat_rate', 0.15),
        );

        $usdZar = (float) (ExchangeRate::latestRate('USDZAR') ?? 18.50);
        $leadTime = config('farmtech.lead_time_default', '7-12 business days');

        $summary = [];

        foreach ($this->products() as $p) {
            $breakdown = $calc->calculate(
                supplierUsd: $p['usd'],
                weightKg: $p['weight'],
                usdZarRate: $usdZar,
                dutyRate: $p['duty'],
                targetMarginPct: $p['margin'],
            );

            $marginPct = $calc->marginPctFromRetail($breakdown['landed_cost_zar'], $breakdown['retail_price_zar']);

            $specifications = array_merge($p['specs'], [
                'Target Pain Point' => $p['pain'],
                'OEM / Demo-Video Search' => $p['yt'],
                'Sourcing Batch' => self::BATCH_TAG,
                'Listing Status' => 'Research candidate — not yet sourced. Supplier, factory price, certifications and specs to be confirmed on a real sourcing pass before publishing.',
            ]);

            $product = Product::updateOrCreate(
                ['sku' => $p['sku']],
                [
                    'title' => $p['title'],
                    'category' => $p['category'],
                    'short_description' => $p['short'],
                    'key_features' => $p['features'],
                    'included_items' => $p['included'],
                    'specifications' => $specifications,
                    'requirements_notes' => $p['requirements'] ?? null,
                    'warranty_period' => '12 months (supplier-backed — confirm at sourcing)',

                    // Costing inputs (design estimates) + computed outputs.
                    'original_price_usd' => $p['usd'],
                    'supplier_cost_usd' => $p['usd'],
                    'exchange_rate' => $usdZar,
                    'est_weight_kg' => $p['weight'],
                    'gross_weight_kg' => $p['weight'],
                    'hs_code' => $p['hs'],
                    'customs_duty_rate' => $p['duty'],
                    'vat_rate' => (float) Setting::get('vat_rate', 0.15),
                    'intl_freight_zar' => $breakdown['intl_freight_zar'],
                    'customs_vat_zar' => $breakdown['customs_vat_zar'],
                    'domestic_delivery_zar' => $breakdown['domestic_delivery_zar'],
                    'landed_cost_zar' => $breakdown['landed_cost_zar'],
                    'retail_price_zar' => $breakdown['retail_price_zar'],
                    'profit_margin_pct' => $marginPct,

                    // Staging state — nothing real is claimed.
                    'status' => 'pending_review',
                    'is_active' => false,
                    'verification_tier' => 'research_candidate',
                    'icasa_status' => $p['radio'] ? 'verification_required' : 'not_applicable',
                    'radio_frequency_confirmed' => false,
                    'datasheet_uploaded' => false,
                    'stock_status' => 'pre_order',
                    'stock_availability_type' => 'available_to_order',
                    'lead_time_days' => $leadTime,
                    'supplier_name' => null,
                    'supplier_url' => null,
                    'source_url' => null,
                ],
            );

            $summary[] = [
                'sku' => $product->sku,
                'title' => $product->title,
                'category' => $product->category->label(),
                'pain' => $p['pain'],
                'usd' => $p['usd'],
                'retail_zar' => (float) $product->retail_price_zar,
                'margin' => $marginPct,
                'yt' => $p['yt'],
            ];
        }

        $this->printMarkdown($summary, $usdZar);
    }

    private function printMarkdown(array $rows, float $usdZar): void
    {
        $out = $this->command?->getOutput();

        if (! $out) {
            return;
        }

        $out->writeln('');
        $out->writeln('<info>Seeded '.count($rows).' research candidates into the staging queue (status=pending_review, verification_tier=research_candidate).</info>');
        $out->writeln('USD/ZAR '.number_format($usdZar, 2).' · freight $'.Setting::get('air_freight_usd_per_kg', 16).'/kg · VAT '.(100 * (float) Setting::get('vat_rate', 0.15)).'% · duty = conservative estimate, confirm with clearing agent.');
        $out->writeln('');
        $out->writeln('| # | Product | Category | Pain point solved | Factory (USD) | Retail (ZAR) | Gross margin | OEM / YouTube search |');
        $out->writeln('|---|---------|----------|-------------------|--------------:|-------------:|-------------:|----------------------|');

        foreach ($rows as $i => $r) {
            $out->writeln(sprintf(
                '| %d | %s | %s | %s | $%s | R%s | %s%% | `%s` |',
                $i + 1,
                str_replace('|', '/', $r['title']),
                $r['category'],
                str_replace('|', '/', $r['pain']),
                number_format($r['usd'], 2),
                number_format($r['retail_zar'], 0),
                number_format($r['margin'], 1),
                $r['yt'],
            ));
        }

        $out->writeln('');
    }

    /**
     * The 20 picks. Selection criteria per item: solves a costly, concrete
     * boer pain point; poorly stocked / heavily marked up by local co-ops;
     * air-freight friendly (< 10 kg, non-hazmat, high value density); lands
     * in the R2,500–R18,000 spend band. Titles deliberately carry the
     * keywords App\Services\SupplierOutreachCurator matches on, so each flows
     * into the outreach hub automatically once approved.
     *
     * @return array<int, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            [
                'sku' => 'FT-BT-TANK-4G',
                'title' => '4G Ultrasonic Water Tank Level Monitor with SMS & App Alerts',
                'category' => 'fuel_monitoring',
                'pain' => 'A reservoir or JoJo tank running dry unnoticed — burnt-out pump, stock without water, a wasted trip to check a float',
                'short' => 'Non-contact ultrasonic level sensor for water reservoirs and storage tanks. Reads remaining depth every few minutes and sends a low-level SMS and push alert before the tank empties — no float switch to seize, no climbing the stand.',
                'features' => [
                    'Low-level and empty alerts by SMS and app — know before stock runs out of water',
                    'Non-contact ultrasonic head: nothing submerged to foul or corrode',
                    'Solar panel + internal battery — no mains at the tank stand',
                    'Logs level history so you can see real daily draw-down and leaks',
                ],
                'specs' => [
                    'Measurement' => 'Ultrasonic, non-contact — 0.25 m to 15 m range, ±0.3% accuracy',
                    'Cellular' => '4G LTE Cat-1 — confirm bands B1 / B3 / B8 / B20 / B28 for South Africa',
                    'Power' => 'Integrated 5 W solar panel + 6,000 mAh Li-ion, > 30 days dark-run',
                    'Ingress Protection' => 'IP67 sensor head, IP65 controller enclosure',
                    'Alerts' => 'SMS + app push on user-set low / high / rate-of-change thresholds',
                    'Logging' => 'On-board level history, cloud dashboard, CSV export',
                ],
                'included' => ['Ultrasonic sensor head', 'Controller with solar panel', 'Mounting bracket + gasket', 'SIM tray tool', 'Quick-start guide'],
                'usd' => 55.0, 'weight' => 0.8, 'duty' => 0.00, 'margin' => 52.0, 'hs' => '9026.10', 'radio' => true,
                'yt' => 'ultrasonic water tank level sensor 4G GSM SMS alarm OEM',
            ],
            [
                'sku' => 'FT-BT-BORE-CTRL',
                'title' => '4G Remote Borehole Pump Controller with Dry-Run & Over-Current Protection',
                'category' => 'smart_irrigation',
                'pain' => 'Driving out to a borehole just to switch the pump on or off — and burning a pump when it runs dry between visits',
                'short' => 'Contactor-driven pump controller you start and stop from your phone. Current sensing cuts the pump on dry-run or overload and alerts you, so a borehole that pumps air never costs a motor.',
                'features' => [
                    'Start / stop the pump from anywhere over 4G — app or SMS command',
                    'Dry-run cutout on under-current, plus over-current and stall protection',
                    'Scheduling and run-time limits so a borehole rests between draws',
                    'Fault, mains-fail and restart alerts pushed to your phone',
                ],
                'specs' => [
                    'Pump Support' => 'Single-phase 220 V and 3-phase 380 V via contactor (contactor sized at sourcing)',
                    'Protection' => 'Dry-run (under-current), over-current, stall, phase-loss, under/over-voltage',
                    'Cellular' => '4G LTE — confirm bands B1 / B3 / B8 / B20 / B28 for South Africa',
                    'Sensing' => 'CT current sensing, configurable trip thresholds and delays',
                    'Mounting' => 'DIN-rail module in an IP54 field enclosure',
                    'Control' => 'App, SMS, web dashboard; local manual override switch',
                ],
                'included' => ['Controller module', 'Current transformers', 'IP54 enclosure', 'DIN rail + glands', 'Wiring diagram'],
                'requirements' => 'Wired in by a qualified electrician at the pump DB. Contactor and CT rating to be matched to the specific pump at sourcing.',
                'usd' => 82.0, 'weight' => 1.3, 'duty' => 0.00, 'margin' => 44.0, 'hs' => '8537.10', 'radio' => true,
                'yt' => 'remote borehole pump controller 4G dry run protection GSM',
            ],
            [
                'sku' => 'FT-BT-BORE-LVL',
                'title' => 'Submersible Borehole Water-Level Pressure Transmitter (4–20 mA, 0–100 m)',
                'category' => 'fuel_monitoring',
                'pain' => 'No idea what the static or pumping water level in a borehole actually is — so pumps are set by guesswork and boreholes get over-drawn',
                'short' => 'A stainless submersible transmitter that hangs down the borehole and outputs a 4–20 mA signal proportional to water column above it. Feeds any PLC, datalogger or the pump controller so draw-down is measured, not estimated.',
                'features' => [
                    'Direct read of static and dynamic (pumping) water level',
                    'Standard 4–20 mA loop output — works with any logger, PLC or telemetry unit',
                    '316 stainless body and vented cable for long submerged life',
                    'Lightning / surge protection on the loop',
                ],
                'specs' => [
                    'Range' => '0–100 m water column (other ranges available), ±0.25% FS',
                    'Output' => '4–20 mA, 2-wire loop-powered, 9–36 V DC',
                    'Wetted Materials' => '316L stainless steel, Viton seals, vented PU cable',
                    'Cable' => 'Integral vented cable, length to borehole depth',
                    'Protection' => 'IP68 (permanent submersion), integrated surge suppression',
                    'Temperature' => '−10 to +70 °C compensated',
                ],
                'included' => ['Submersible transmitter', 'Vented cable (length specified at order)', 'Cable hanging clamp', 'Desiccant vent filter', 'Calibration sheet'],
                'usd' => 62.0, 'weight' => 1.6, 'duty' => 0.00, 'margin' => 45.0, 'hs' => '9026.20', 'radio' => false,
                'yt' => 'submersible level transmitter 4-20mA borehole water OEM',
            ],
            [
                'sku' => 'FT-BT-SOLAR-FILL',
                'title' => 'Solar Borehole Pump Auto-Fill Controller with Tank Float Sensor',
                'category' => 'solar_pumps',
                'pain' => 'A solar borehole pump that keeps running against a full tank, or short-cycles all day, because nothing tells it when the storage tank is full',
                'short' => 'Links a solar (or mains) borehole pump to a float / level sensor in the storage tank: the pump runs only when the tank needs water and stops when it is full, with dry-well protection on the borehole side.',
                'features' => [
                    'Automatic fill: pump follows tank level, not daylight',
                    'Dry-well protection on the borehole probe',
                    'Works with DC solar pumps and AC pumps via contactor',
                    'Adjustable start / stop levels and minimum rest time',
                ],
                'specs' => [
                    'Inputs' => 'Tank float switch or 2-probe level sensor; borehole dry-well probe',
                    'Pump Output' => 'DC solar pump direct, or AC pump via contactor (sized at sourcing)',
                    'Adjustments' => 'Start level, stop level, minimum off-time, max run-time',
                    'Enclosure' => 'IP65, wall-mount',
                    'Indication' => 'Run / fault / tank-full LEDs, volt-free fault relay',
                    'Power' => '12–24 V DC control supply',
                ],
                'included' => ['Controller', 'Tank level float + cable', 'Borehole dry-well probe', 'IP65 enclosure', 'Wiring guide'],
                'usd' => 95.0, 'weight' => 2.6, 'duty' => 0.00, 'margin' => 44.0, 'hs' => '8537.10', 'radio' => false,
                'yt' => 'solar pump controller tank float auto fill dry run OEM',
            ],
            [
                'sku' => 'FT-BT-DIESEL-GUARD',
                'title' => 'Non-Invasive Ultrasonic Diesel Tank Level Sensor with 4G Theft Alarm',
                'category' => 'fuel_monitoring',
                'pain' => 'Diesel disappearing from the farm bulk tank overnight and no way to prove it or catch it early',
                'short' => 'Clamp-on ultrasonic sensor that reads diesel level through the tank wall — no cutting, no sender in the fuel. A sudden drop outside working hours fires an immediate theft alert with the litres lost and the time.',
                'features' => [
                    'Sudden-drop theft alert by SMS and app, with litres and timestamp',
                    'Bonds to the outside of the tank — nothing inside the fuel, no hot work',
                    'Daily usage and refill log to reconcile deliveries against a supplier invoice',
                    'Solar powered, sits on a bulk tank with no services nearby',
                ],
                'specs' => [
                    'Measurement' => 'External ultrasonic through-wall, 0–2.0 m depth, ±1% typical after calibration',
                    'Tank Types' => 'Steel and most plastic bulk tanks, flat or dished base',
                    'Cellular' => '4G LTE Cat-1 — confirm bands B1 / B3 / B8 / B20 / B28 for South Africa',
                    'Power' => 'Solar panel + rechargeable pack, multi-week dark-run',
                    'Alerts' => 'Rapid-loss (theft), low level, tamper / sensor-lift',
                    'Ingress Protection' => 'IP66',
                ],
                'included' => ['Ultrasonic sensor puck', 'Bonding compound', 'Solar-powered 4G controller', 'Mounting strap', 'Calibration guide'],
                'usd' => 68.0, 'weight' => 0.9, 'duty' => 0.00, 'margin' => 47.0, 'hs' => '9026.10', 'radio' => true,
                'yt' => 'non-invasive ultrasonic diesel tank level sensor 4G theft alarm',
            ],
            [
                'sku' => 'FT-BT-FUEL-FLOW',
                'title' => 'Digital Inline Diesel Flow Meter (Oval-Gear, ±0.5%, 10–120 L/min)',
                'category' => 'fuel_monitoring',
                'pain' => 'No accurate record of how much diesel went into which tractor, bakkie or implement — so fuel budgeting and theft detection are impossible',
                'short' => 'Oval-gear positive-displacement meter for the diesel bowser or bulk-tank outlet. Batch and cumulative totals to 0.1 L, resettable trip counter, and a pulse output if you later want it logged automatically.',
                'features' => [
                    'Positive-displacement oval-gear element — ±0.5% on diesel, holds accuracy at low flow',
                    'Batch total and non-resettable lifetime total',
                    'Large digits, field-readable; pulse output for future telemetry',
                    'Aluminium body, inline 1" BSP — drops into a standard bowser hose run',
                ],
                'specs' => [
                    'Flow Range' => '10–120 L/min, ±0.5% accuracy, ±0.2% repeatability',
                    'Connection' => '1" BSP (F), inline horizontal or vertical',
                    'Display' => 'Batch + cumulative, litres / gallons, resettable trip',
                    'Output' => 'Scaled pulse (open-collector) for dataloggers',
                    'Body' => 'Aluminium, Viton seals, diesel / biodiesel B20 compatible',
                    'Power' => '2× AA (display), no external power needed',
                ],
                'included' => ['Flow meter', 'BSP fittings', 'Spare seal kit', '2× AA batteries', 'Calibration certificate'],
                'usd' => 44.0, 'weight' => 1.2, 'duty' => 0.00, 'margin' => 53.0, 'hs' => '9028.20', 'radio' => false,
                'yt' => 'oval gear diesel flow meter digital inline OEM',
            ],
            [
                'sku' => 'FT-BT-CRUSH-SCALE',
                'title' => 'Livestock Crush Weigh-Scale Indicator + 2,000 kg Load-Bar Set (Bluetooth, EID-ready)',
                'category' => 'scales',
                'pain' => 'Dosing, drafting and selling cattle on guessed weights — under-dosing parasites and leaving money on the table at the sale',
                'short' => 'A sealed weigh indicator with a pair of heavy load bars that sit under any crush or race floor. Live weight in seconds, weight-gain history per animal when paired with an EID stick reader.',
                'features' => [
                    'Accurate individual live weights for correct dosing and sale decisions',
                    'Load bars bolt under an existing crush or weigh platform',
                    'Bluetooth to a free app; optional EID reader links weight to animal ID',
                    'Sealed IP67 indicator, rechargeable — built for the crush, not the office',
                ],
                'specs' => [
                    'Capacity' => '2,000 kg with 2× 1,000 kg alloy-steel load bars, 0.5 kg increments',
                    'Load Bars' => '1,000 mm, IP68 sealed cells, over-load stops',
                    'Indicator' => 'IP67, sunlight-readable, lock-on weighing, > 40 h battery',
                    'Connectivity' => 'Bluetooth (app), USB; RS232 for EID reader input',
                    'Data' => 'On-board sessions, per-animal history via app, CSV export',
                    'Power' => 'Internal rechargeable + 12 V vehicle lead',
                ],
                'included' => ['Weigh indicator', '2× 1,000 kg load bars', 'Load-bar cabling', 'Bracket set', 'Charger + 12 V lead'],
                'usd' => 225.0, 'weight' => 9.0, 'duty' => 0.00, 'margin' => 42.0, 'hs' => '8423.82', 'radio' => true,
                'yt' => 'cattle crush weighing indicator load bars bluetooth EID',
            ],
            [
                'sku' => 'FT-BT-HANG-300',
                'title' => 'Digital Hanging Scale for Calves & Lambs (300 kg, Weigh-Sling Included)',
                'category' => 'scales',
                'pain' => 'No practical way to record birth and weaning weights on small stock — so growth problems are spotted months too late',
                'short' => 'A rugged 300 kg hanging scale with a padded weigh-sling for calves, lambs and kids. Hang it off the loader bucket or a gantry, zero the sling, read the weight, log it.',
                'features' => [
                    'Birth, weaning and sale weights on calves, lambs and kids in seconds',
                    'Padded sling included — safe, quick, one-person weighing',
                    'Tare / hold / peak functions; auto-off to save the battery',
                    'Rechargeable, backlit display for early-morning work',
                ],
                'specs' => [
                    'Capacity' => '300 kg × 100 g, OIML-style internal calibration',
                    'Functions' => 'Tare, hold, peak-hold, kg / lb, low-battery warning',
                    'Hook' => 'Swivel safety hook top and bottom, 3× safe working load',
                    'Display' => '25 mm backlit LCD',
                    'Power' => 'Rechargeable Li-ion, ~60 h per charge',
                    'Operating Temp' => '−10 to +45 °C',
                ],
                'included' => ['Hanging scale', 'Padded weigh-sling', 'Swivel hooks', 'USB charger', 'Calibration sheet'],
                'usd' => 46.0, 'weight' => 1.4, 'duty' => 0.00, 'margin' => 45.0, 'hs' => '8423.81', 'radio' => false,
                'yt' => 'digital hanging scale calf lamb weigh sling 300kg',
            ],
            [
                'sku' => 'FT-BT-PREG-US',
                'title' => 'Veterinary Pregnancy Ultrasound Scanner (Rectal Linear Probe, Cattle & Sheep)',
                'category' => 'ultrasound',
                'pain' => 'Paying for a vet call-out every time cows or ewes need pregnancy testing, and waiting weeks for a slot',
                'short' => 'A handheld rectal-probe ultrasound for on-farm pregnancy diagnosis in cattle and small stock. Wrist monitor or video goggles, all-day battery, sealed probe — scan your own herd on your own schedule.',
                'features' => [
                    'On-farm preg-testing for cattle and sheep without a vet call-out',
                    'Rectal linear probe with the working frequency for repro scanning',
                    'Wrist-mounted screen or head-up goggles for hands-free work in the race',
                    'IP67 probe, quick-charge battery, rugged transport case',
                ],
                'specs' => [
                    'Probe' => 'Rectal linear array — confirm 5.0 / 7.5 MHz and scan depth at sourcing',
                    'Display' => 'Wrist LCD (sunlight-readable) + optional video goggles',
                    'Battery' => '≥ 5 h continuous scanning, hot-swap pack',
                    'Modes' => 'B-mode, B/B, zoom, on-screen calipers, cine loop',
                    'Ingress Protection' => 'IP67 probe, splash-resistant main unit',
                    'Storage' => 'Internal image/clip storage, USB export',
                ],
                'included' => ['Main unit', 'Rectal linear probe', 'Wrist display + strap', 'Video goggles', 'Battery ×2 + charger', 'Hard case'],
                'usd' => 385.0, 'weight' => 2.2, 'duty' => 0.00, 'margin' => 45.0, 'hs' => '9018.12', 'radio' => false,
                'yt' => 'veterinary pregnancy ultrasound rectal linear probe cattle goggles',
            ],
            [
                'sku' => 'FT-BT-STICK-134K',
                'title' => '134.2 kHz Bluetooth Stick Reader (ISO 11784/11785 HDX/FDX-B, Data Logger)',
                'category' => 'rfid',
                'pain' => 'Writing down tag numbers by hand in the race — transposed digits, lost sheets, hours of recapture',
                'short' => 'A one-hand stick reader for ISO livestock ear tags. Reads HDX and FDX-B to ~30 cm, buffers thousands of IDs, and streams each read over Bluetooth to a weigh scale or a phone app.',
                'features' => [
                    'Fast, accurate animal ID straight into the app or weigh head — no paper',
                    'Reads both HDX and FDX-B ISO tags; strong read range in a metal race',
                    '100,000-tag internal memory for offline sessions',
                    'IP67, gloved-hand button, all-day battery',
                ],
                'specs' => [
                    'Standard' => 'ISO 11784 / 11785, 134.2 kHz, HDX + FDX-B',
                    'Read Range' => 'Up to ~30 cm on a standard ISO ear tag',
                    'Memory' => '100,000 tag IDs with timestamp',
                    'Connectivity' => 'Bluetooth LE (licence-exempt) + USB; pairs with common weigh indicators',
                    'Ingress Protection' => 'IP67, drop-rated housing',
                    'Battery' => 'Rechargeable Li-ion, ~3,000 reads / full shift',
                ],
                'included' => ['Stick reader', 'USB-C charger', 'Wrist lanyard', 'Belt holster', 'Quick-start guide'],
                'usd' => 145.0, 'weight' => 1.0, 'duty' => 0.00, 'margin' => 45.0, 'hs' => '8471.90', 'radio' => false,
                'yt' => '134.2khz stick reader ISO 11784 11785 bluetooth HDX FDX-B',
            ],
            [
                'sku' => 'FT-BT-FENCE-FF',
                'title' => 'Electric Fence Directional Fault Finder / Wire-Break Locator',
                'category' => 'fencing',
                'pain' => 'Walking kilometres of electric fence line trying to find the short that has flattened the whole fence',
                'short' => 'Clip it on the fence and it shows voltage, leakage current and a direction arrow toward the fault. Follow the arrows to the break instead of pacing the whole boundary.',
                'features' => [
                    'Direction-to-fault arrow — walk straight to the short, not the whole line',
                    'Simultaneous kV and fault-current read',
                    'Contactless mode: hold near the wire, no need to clamp on every span',
                    'Backlit display for dawn / dusk fault-finding',
                ],
                'specs' => [
                    'Voltage Range' => '0.2–9.9 kV, ±(2% + 0.1 kV)',
                    'Current Range' => '0–150 A fault current, direction indication',
                    'Modes' => 'Contact clamp and contactless proximity',
                    'Display' => 'Backlit LCD, kV + A + direction arrow',
                    'Power' => '2× AA, auto-off',
                    'Ingress Protection' => 'IP54, rubber-armoured',
                ],
                'included' => ['Fault finder', 'Earth lead + probe', 'Clamp lead', '2× AA batteries', 'Carry pouch'],
                'usd' => 58.0, 'weight' => 0.6, 'duty' => 0.00, 'margin' => 48.0, 'hs' => '9030.39', 'radio' => false,
                'yt' => 'electric fence fault finder direction wire break locator',
            ],
            [
                'sku' => 'FT-BT-FENCE-4G',
                'title' => '4G GSM Electric Fence Voltage Alarm & Energizer Monitor (SMS + App)',
                'category' => 'fencing',
                'pain' => 'Finding out the perimeter fence has been down for hours — and stock is on the road — only when a neighbour phones',
                'short' => 'Wires into the fence line and the energizer. If the voltage drops below your threshold, the mains fails, or the energizer stops, it sends an immediate SMS and app alert with the line voltage.',
                'features' => [
                    'Instant SMS + app alert on low fence voltage, mains failure or energizer stop',
                    'Live line-voltage read any time from the app',
                    'Two zones / two inputs to split a long fence',
                    'Battery-backed so a mains cut is itself an alert, not a blind spot',
                ],
                'specs' => [
                    'Inputs' => '2× fence-voltage inputs (0–15 kV), 1× mains-present, 1× tamper',
                    'Cellular' => '4G LTE — confirm bands B1 / B3 / B8 / B20 / B28 for South Africa',
                    'Alerts' => 'SMS to multiple numbers + app push; daily OK heartbeat',
                    'Backup' => 'Internal Li-ion, > 24 h on mains failure',
                    'Relays' => '2× volt-free outputs (siren / light)',
                    'Enclosure' => 'IP65, wall-mount, external antenna',
                ],
                'included' => ['Monitor unit', 'Fence sense leads', 'External 4G antenna', 'PSU', 'Mounting kit'],
                'usd' => 74.0, 'weight' => 1.0, 'duty' => 0.00, 'margin' => 46.0, 'hs' => '8531.10', 'radio' => true,
                'yt' => 'GSM electric fence alarm voltage monitor SMS 4G',
            ],
            [
                'sku' => 'FT-BT-FENCE-15J',
                'title' => 'Solar Electric Fence Energizer (15 J, 80 km) with 40 W MPPT Panel Kit',
                'category' => 'fencing',
                'pain' => 'Remote boundary and camp fences with no mains — and small solar energizers that will not hold a line through wet grass and bush encroachment',
                'short' => 'A 15 J stored-energy energizer with its own 40 W panel, MPPT regulator and AGM battery. Enough punch for long multi-strand game and stock fence far from any power point.',
                'features' => [
                    'Drives long, dirty lines — game fence, bush encroachment, wet grass',
                    'Complete off-grid kit: energizer, MPPT regulator, panel, battery box',
                    'Battery and fence-fault indication; low-battery load-shed to protect the AGM',
                    'IP65 energizer for pole or wall mounting in the veld',
                ],
                'specs' => [
                    'Output' => '15 J stored / ~12 J output, ~10 kV open circuit, ~80 km / 40 ha multi-wire',
                    'Solar' => '40 W panel + MPPT charge regulator',
                    'Battery' => '12 V 40–100 Ah AGM (sized in kit at sourcing)',
                    'Compliance' => 'To be confirmed against NRCS / SANS 60335-2-76 before listing',
                    'Indication' => 'Fence volts, battery state, fault LED',
                    'Ingress Protection' => 'IP65 energizer, IP65 regulator',
                ],
                'included' => ['15 J energizer', 'MPPT solar regulator', '40 W solar panel', 'Battery box + leads', 'Earth stakes', 'Mounting hardware'],
                'usd' => 168.0, 'weight' => 8.5, 'duty' => 0.00, 'margin' => 42.0, 'hs' => '8543.70', 'radio' => false,
                'yt' => 'solar electric fence energizer 15 joule MPPT kit 80km',
            ],
            [
                'sku' => 'FT-BT-SOIL-7IN1',
                'title' => '7-in-1 Bluetooth Soil Meter — NPK, pH, EC, Moisture, Temp, TDS',
                'category' => 'moisture_meters',
                'pain' => 'Buying blanket fertiliser and lime for a whole land with no idea what any block actually needs',
                'short' => 'A push-in probe that reads nitrogen, phosphorus, potassium, pH, EC, moisture and temperature and logs each reading against a GPS point in the app — spot-check problem patches before you spend on inputs.',
                'features' => [
                    'Input decisions from real numbers per block, not a whole-farm average',
                    'Seven parameters from one probe; readings logged with GPS in the app',
                    'Field-portable — check a suspect patch in minutes',
                    'Export CSV / PDF for an agronomist',
                ],
                'specs' => [
                    'Parameters' => 'N, P, K (mg/kg), pH 3–9 (±0.3), EC, moisture %, soil temp, TDS',
                    'Interface' => 'Bluetooth LE to iOS / Android app',
                    'Probe' => 'Stainless electrodes, ~200 mm insertion',
                    'Logging' => 'Time + GPS stamped, CSV / PDF export',
                    'Power' => 'Rechargeable Li-ion, USB-C',
                    'Note' => 'Consumer-grade NPK — screening tool, not a lab substitute (state clearly on listing)',
                ],
                'included' => ['Soil meter probe', 'USB-C cable', 'Calibration solution (pH)', 'Carry case', 'Guide'],
                'usd' => 52.0, 'weight' => 0.7, 'duty' => 0.00, 'margin' => 55.0, 'hs' => '9027.80', 'radio' => false,
                'yt' => '7 in 1 soil NPK pH EC moisture meter bluetooth',
            ],
            [
                'sku' => 'FT-BT-GRAIN-MC',
                'title' => 'Whole-Grain Moisture Meter (Maize / Wheat / Soya, 20+ Calibrations, ±0.5%)',
                'category' => 'moisture_meters',
                'pain' => 'Load rejections and silo spoilage from delivering grain at the wrong moisture — and no way to check it in the field at harvest',
                'short' => 'Pour a cup of whole grain in, pick the crop, read moisture to 0.1%. Automatic temperature compensation and a test-weight function so what you measure at the header matches what the silo scores.',
                'features' => [
                    'On-the-spot moisture at the header, the heap or the truck',
                    '20+ crop calibrations incl. maize, wheat, sunflower, soya, sorghum',
                    'Automatic grain-temperature compensation',
                    'Whole-grain cup — no grinding, repeatable',
                ],
                'specs' => [
                    'Range' => '2–40% MC depending on crop, ±0.5% typical, 0.1% resolution',
                    'Calibrations' => '20+ stored crop curves, user-adjustable offset',
                    'Compensation' => 'Automatic temperature compensation, test-weight (hL) readout',
                    'Sample' => 'Whole-grain measuring cup, ~250 ml',
                    'Display' => 'Backlit LCD, hold + average of multiple samples',
                    'Power' => '9 V battery or USB',
                ],
                'included' => ['Moisture meter', 'Measuring cup', 'Scoop', '9 V battery', 'Calibration certificate', 'Case'],
                'usd' => 60.0, 'weight' => 0.9, 'duty' => 0.00, 'margin' => 47.0, 'hs' => '9027.80', 'radio' => false,
                'yt' => 'grain moisture meter maize wheat whole grain cup tester',
            ],
            [
                'sku' => 'FT-BT-BRIX-COL',
                'title' => 'Digital Brix & Colostrum Refractometer (0–35% / 0–95%, ATC)',
                'category' => 'accessories',
                'pain' => 'Feeding calves colostrum of unknown quality — and guessing fruit and grape ripeness by taste',
                'short' => 'A pocket digital refractometer with automatic temperature compensation. One drop reads Brix for colostrum IgG screening, or fruit / grape / cane sugar for harvest timing.',
                'features' => [
                    'Colostrum quality check in seconds — protect calf survival rates',
                    'Doubles as a harvest Brix meter for fruit, grapes and cane',
                    'Automatic temperature compensation — no correction tables',
                    'One drop, 3-second read, wipe clean; IP65 splash resistance',
                ],
                'specs' => [
                    'Scale' => 'Brix 0–95% (0.1% res); colostrum reference scale marked',
                    'Accuracy' => '±0.2% Brix',
                    'Compensation' => 'Automatic, 10–40 °C',
                    'Sample' => '0.3 ml, stainless prism well',
                    'Ingress Protection' => 'IP65',
                    'Power' => '2× AAA, auto-off',
                ],
                'included' => ['Digital refractometer', 'Pipettes ×3', 'Calibration (distilled water) guide', '2× AAA', 'Pouch'],
                'usd' => 44.0, 'weight' => 0.4, 'duty' => 0.00, 'margin' => 60.0, 'hs' => '9027.50', 'radio' => false,
                'yt' => 'digital brix refractometer colostrum ATC handheld',
            ],
            [
                'sku' => 'FT-BT-GREASE-20V',
                'title' => 'Cordless 20V Grease Gun (10,000 psi, Flex + Rigid Hose, Digital Cycle Counter)',
                'category' => 'accessories',
                'pain' => 'Hand-pumping a grease gun through every pin and bearing on a planter or baler — slow, uneven, and skipped points fail early',
                'short' => 'A battery grease gun that delivers a consistent shot at up to 10,000 psi. Flex and rigid hoses, a work light, and a counter that shows grams dispensed so every point gets the same charge.',
                'features' => [
                    'Fast, even lubrication of every pin and bearing on big implements',
                    '10,000 psi to shift a blocked zerk without a manual gun',
                    'Runs off the same 20 V battery platform as other cordless farm tools (confirm platform at sourcing)',
                    'Digital dispense counter + LED work light',
                ],
                'specs' => [
                    'Pressure' => 'Up to 10,000 psi (690 bar)',
                    'Flow' => '~140–280 g/min, variable-speed trigger',
                    'Cartridge' => 'Standard 400 g cartridge or bulk fill',
                    'Battery' => '20 V Li-ion 2.0 Ah (platform confirmed at sourcing), ~10 cartridges/charge',
                    'Hoses' => '760 mm flexible + rigid extension, spring guards',
                    'Extras' => 'Digital cycle / gram counter, LED light, shoulder strap',
                ],
                'included' => ['Grease gun', '20 V battery ×1', 'Charger', 'Flex hose + rigid pipe', 'Coupler + filler nipple', 'Shoulder strap'],
                'usd' => 80.0, 'weight' => 3.2, 'duty' => 0.00, 'margin' => 44.0, 'hs' => '8467.89', 'radio' => false,
                'yt' => 'cordless grease gun 20v 10000 psi digital counter',
            ],
            [
                'sku' => 'FT-BT-WIRE-TENS',
                'title' => 'Digital Fence Wire Tension Meter (0–500 kg, ±2%, HT 2.0–3.15 mm)',
                'category' => 'fencing',
                'pain' => 'New fences going slack in a season, or snapping droppers, because strain-post and wire tension is set by feel',
                'short' => 'Clamps over a strained wire and reads actual tension in kilograms-force. Set every wire on a fence to the same, correct strain so it stays tight and the posts last.',
                'features' => [
                    'Real tension figure per wire — no more setting fences by feel',
                    'Fits high-tensile 2.0–3.15 mm wire',
                    'Peak-hold to catch the reading while straining',
                    'Compact, lives in the fencing bag',
                ],
                'specs' => [
                    'Range' => '0–500 kgf (0–5 kN), ±2% FS',
                    'Wire' => 'High-tensile 2.0–3.15 mm; netting top-wire',
                    'Functions' => 'Live read, peak-hold, kgf / N / lbf',
                    'Display' => 'Backlit LCD',
                    'Power' => 'CR2032 / AAA, auto-off',
                    'Body' => 'Glass-filled nylon, hardened rollers',
                ],
                'included' => ['Tension meter', 'Battery', 'Lanyard', 'Instructions'],
                'usd' => 48.0, 'weight' => 0.5, 'duty' => 0.00, 'margin' => 56.0, 'hs' => '9031.80', 'radio' => false,
                'yt' => 'fence wire tension meter digital high tensile',
            ],
            [
                'sku' => 'FT-BT-TRAILER-CAM',
                'title' => 'Wireless Magnetic Trailer & Hitch Reversing Camera (Rechargeable, IP69K)',
                'category' => 'vehicle_accessories',
                'pain' => 'Hitching an implement or trailer alone, blind, with someone usually needed to stand and wave',
                'short' => 'A rechargeable magnetic camera that slaps onto the tow-bar or tailgate and sends a digital picture to a suction-mount monitor in the cab. Line up the ball and pin first time, on your own.',
                'features' => [
                    'Hitch up solo — clear view of ball and drawbar from the cab',
                    'Fully wireless: magnet mount, internal battery, no wiring to the trailer',
                    'Digital 2.4 GHz link (licence-exempt ISM), stable to ~10 m with metal in between',
                    'IP69K camera — survives pressure-washing and dust',
                ],
                'specs' => [
                    'Camera' => '1080p, 150° lens, IR night LEDs, IP69K, neodymium magnet base',
                    'Link' => '2.4 GHz digital, licence-exempt ISM band, ~10 m working range',
                    'Monitor' => '4.3–5" suction-mount, rechargeable, 12 V lead included',
                    'Battery' => 'Camera ~4–6 h per charge; magnetic charge contacts',
                    'Modes' => 'Reversing view + park guidelines, mirror flip',
                    'Ingress Protection' => 'IP69K camera, IP54 monitor',
                ],
                'included' => ['Magnetic camera', 'Suction monitor', 'USB chargers ×2', '12 V monitor lead', 'Spare magnet plate'],
                'usd' => 56.0, 'weight' => 0.8, 'duty' => 0.15, 'margin' => 46.0, 'hs' => '8525.89', 'radio' => true,
                'yt' => 'wireless magnetic trailer hitch reversing camera rechargeable',
            ],
            [
                'sku' => 'FT-BT-THERMAL-CAM',
                'title' => 'Handheld Thermal Imaging Camera for Bearing, Pump & Panel Diagnostics (256×192, −20–550 °C)',
                'category' => 'thermal_diagnostics',
                'pain' => 'Overheating bearings, slipping belts, failing irrigation-pump motors and loose DB connections that only get found when they burn out',
                'short' => 'A pocket thermal camera that shows heat as a picture. Walk the pump house, the pivot gearboxes, the workshop DB and the tractor turbo and see the hot spot before it fails.',
                'features' => [
                    'Spot a failing bearing, motor or electrical joint days before breakdown',
                    'Live thermal image with hot / cold spot tracking and a centre spot temp',
                    'On-farm uses: pump motors, gearboxes, belts, DB boards, cold-room seals, livestock inflammation checks',
                    'Rugged, one-hand, USB-C image offload',
                ],
                'specs' => [
                    'Detector' => '256×192 IR (confirm NETD ≤ 50 mK at sourcing), 25 Hz',
                    'Temp Range' => '−20 to +550 °C, ±2 °C or ±2%',
                    'Display' => '3.5" LCD, multi-palette, visible-light blend',
                    'Storage' => 'Radiometric JPEG to internal / USB-C',
                    'Battery' => 'Rechargeable, ~6 h continuous',
                    'Ingress Protection' => 'IP54, 2 m drop',
                ],
                'included' => ['Thermal camera', 'USB-C cable', 'Wrist strap', 'Hard case', 'Quick guide'],
                'usd' => 245.0, 'weight' => 0.6, 'duty' => 0.00, 'margin' => 45.0, 'hs' => '9013.80', 'radio' => false,
                'yt' => 'handheld thermal imaging camera 256x192 industrial diagnostics',
            ],
        ];
    }
}
