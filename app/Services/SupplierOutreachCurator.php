<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Picks the real, currently-live products that are the best fit for the
 * supplier WhatsApp outreach hub: high-margin, air-freight-friendly
 * ("value dense"), and solving one of the specific high-value problems this
 * outreach push is targeting — never a hand-picked or fabricated list.
 *
 * Some of the exact sub-types requested when this was scoped (colostrum
 * refractometers, hanging calf scales, electric-fence fault finders, GSM
 * fence voltage alarms, magnetic trailer cameras, cordless grease guns,
 * digital wire tension meters) simply aren't in the catalog yet — those
 * keywords are kept here so they start matching automatically the moment a
 * matching product is imported, but until then the closest real equivalents
 * fill each bucket instead of leaving it empty or inventing a listing.
 */
class SupplierOutreachCurator
{
    public const MIN_MARGIN_PCT = 35.0;

    /**
     * title keyword (checked lowercase, first match wins) => [bucket, target problem].
     * Ordered most-specific-first within each bucket so a specific phrase
     * ("grain moisture") is checked before a broader one could ever compete.
     */
    private const PROBLEM_MAP = [
        // Livestock & Veterinary
        'colostrum' => ['Livestock & Veterinary', 'On-farm colostrum quality testing for calf survival'],
        'refractometer' => ['Livestock & Veterinary', 'On-farm colostrum/brix quality testing'],
        'ultrasound' => ['Livestock & Veterinary', 'Reproductive/pregnancy scanning without a vet callout'],
        'hanging' => ['Livestock & Veterinary', 'Accurate live-weight tracking for feeding & sale decisions'],
        'calf scale' => ['Livestock & Veterinary', 'Accurate live-weight tracking for feeding & sale decisions'],
        'ear tag' => ['Livestock & Veterinary', 'Fast, accurate animal ID and traceability'],
        'tag reader' => ['Livestock & Veterinary', 'Fast, accurate animal ID and traceability'],
        'rfid' => ['Livestock & Veterinary', 'Fast, accurate animal ID and traceability'],
        'livestock scale' => ['Livestock & Veterinary', 'Accurate live-weight tracking for feeding & sale decisions'],
        'livestock' => ['Livestock & Veterinary', 'Accurate live-weight tracking for feeding & sale decisions'],

        // Water, Fuel & Borehole Telemetry
        'ultrasonic tank' => ['Water, Fuel & Borehole Telemetry', 'Remote tank level monitoring — no manual dipping'],
        'tank level' => ['Water, Fuel & Borehole Telemetry', 'Remote diesel/water tank monitoring — no manual dipping'],
        'borehole' => ['Water, Fuel & Borehole Telemetry', 'Remote borehole pump control without a site visit'],
        'pump controller' => ['Water, Fuel & Borehole Telemetry', 'Remote irrigation/borehole pump control'],
        'pump inverter' => ['Water, Fuel & Borehole Telemetry', 'Remote irrigation/borehole pump control'],
        'soil moisture' => ['Water, Fuel & Borehole Telemetry', 'Irrigation timing based on real soil data, not guesswork'],
        'solar pump' => ['Water, Fuel & Borehole Telemetry', 'Off-grid water pumping without diesel/mains power'],
        'diesel' => ['Water, Fuel & Borehole Telemetry', 'Fuel handling & dispensing accuracy'],

        // Security & Perimeter
        'fence fault' => ['Security & Perimeter', 'Locating electric fence faults without walking the line'],
        'trailer camera' => ['Security & Perimeter', 'Blind-spot & hitch visibility for towing safely'],
        'security camera' => ['Security & Perimeter', 'Remote visual perimeter monitoring, no mains power needed'],
        'perimeter' => ['Security & Perimeter', 'Perimeter intrusion detection'],
        'fence energizer' => ['Security & Perimeter', 'Perimeter fence monitoring & stock-theft deterrence'],
        'electric fence' => ['Security & Perimeter', 'Perimeter fence monitoring & stock-theft deterrence'],

        // Workshop & In-Field Tools
        'grease gun' => ['Workshop & In-Field Tools', 'Fast, mess-free bearing/fitting lubrication in the field'],
        'wire tension' => ['Workshop & In-Field Tools', 'Correct fence wire tensioning without guesswork'],
        'grain moisture' => ['Workshop & In-Field Tools', 'On-the-spot grain moisture testing at harvest'],
        'moisture analyzer' => ['Workshop & In-Field Tools', 'On-the-spot grain moisture testing at harvest'],
        'moisture meter' => ['Workshop & In-Field Tools', 'On-the-spot grain/soil moisture testing'],
    ];

    /** @return Collection<int, object{product: Product, bucket: string, target_problem: string}> */
    public function topProblemSolvers(int $limit = 15): Collection
    {
        $matches = Product::storefrontVisible()
            ->where('profit_margin_pct', '>=', self::MIN_MARGIN_PCT)
            ->where('est_weight_kg', '<', ValueDensityEvaluator::MAX_GROSS_WEIGHT_KG)
            ->get()
            ->map(function (Product $product) {
                $match = $this->matchProblem($product->title);

                return $match ? (object) [
                    'product' => $product,
                    'bucket' => $match[0],
                    'target_problem' => $match[1],
                ] : null;
            })
            ->filter()
            ->values();

        $sorted = $matches->all();
        usort($sorted, function ($a, $b) {
            return ((float) $b->product->profit_margin_pct <=> (float) $a->product->profit_margin_pct)
                ?: ((float) $a->product->est_weight_kg <=> (float) $b->product->est_weight_kg);
        });

        return collect($sorted)->take($limit)->values();
    }

    /** @return array{0: string, 1: string}|null */
    private function matchProblem(string $title): ?array
    {
        $lower = strtolower($title);

        foreach (self::PROBLEM_MAP as $keyword => $result) {
            if (str_contains($lower, $keyword)) {
                return $result;
            }
        }

        return null;
    }
}
