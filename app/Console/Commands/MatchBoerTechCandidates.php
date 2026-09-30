<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * READ-ONLY report. For each FT-BT research candidate (see Top20BoerTechSeeder)
 * it scores the FT-ALI listings already in the catalog by keyword overlap and
 * shows the closest few, with the real supplier, source_url and image count —
 * so a human can eyeball which candidate genuinely corresponds to which
 * scraped listing.
 *
 * It deliberately writes nothing. A keyword hit ("moisture meter",
 * "ultrasound") is a lead, not proof of product identity, so copying a real
 * supplier's name / listing URL / photos onto a candidate is a per-pair human
 * decision, not something to bulk-apply from a fuzzy match. Nothing here
 * activates or publishes anything either.
 */
class MatchBoerTechCandidates extends Command
{
    protected $signature = 'catalog:match-boer-tech {--min-score=3 : Minimum score for a listing to be reported}';

    protected $description = 'Read-only: match the 20 FT-BT research candidates against existing FT-ALI listings by keyword and print the closest ones with their real supplier + source URL.';

    /**
     * Curated match phrases per candidate SKU (lowercase, substring match).
     * Kept explicit rather than derived from the title so the matcher stays
     * precise — e.g. "stick reader" not just "reader".
     */
    private const KEYWORDS = [
        'FT-BT-TANK-4G' => ['tank level', 'water level sensor', 'ultrasonic level', 'liquid level sensor', 'level transmitter', 'level meter'],
        'FT-BT-BORE-CTRL' => ['pump controller', 'pump control', 'water pump controller', 'motor protector', 'pump starter', 'pump protection'],
        'FT-BT-BORE-LVL' => ['submersible level', 'level transmitter', 'hydrostatic level', 'water level sensor', '4-20ma level', 'liquid level transmitter'],
        'FT-BT-SOLAR-FILL' => ['solar pump controller', 'pump controller', 'float switch', 'water level control', 'liquid level controller'],
        'FT-BT-DIESEL-GUARD' => ['fuel level sensor', 'diesel level', 'fuel level', 'tank level', 'fuel monitoring', 'ultrasonic fuel'],
        'FT-BT-FUEL-FLOW' => ['flow meter', 'oval gear', 'diesel flow', 'fuel flow', 'turbine flow', 'flowmeter'],
        'FT-BT-CRUSH-SCALE' => ['load cell', 'weighing indicator', 'livestock scale', 'load bar', 'cattle scale', 'shear beam', 'animal scale', 'weight indicator'],
        'FT-BT-HANG-300' => ['hanging scale', 'crane scale', 'hook scale', 'digital hanging'],
        'FT-BT-PREG-US' => ['veterinary ultrasound', 'ultrasound scanner', 'pregnancy ultrasound', 'rectal probe', 'bovine ultrasound', 'animal ultrasound', 'veterinary scanner'],
        'FT-BT-STICK-134K' => ['stick reader', 'rfid reader', 'ear tag reader', '134.2', 'fdx-b', 'animal id reader', 'livestock rfid', 'tag reader'],
        'FT-BT-FENCE-FF' => ['fence fault', 'fault finder', 'fence tester', 'fence voltage tester', 'wire break', 'fence volt meter'],
        'FT-BT-FENCE-4G' => ['fence alarm', 'fence energizer', 'fence monitor', 'gsm fence', 'security fence alarm', 'fence voltage alarm'],
        'FT-BT-FENCE-15J' => ['fence energizer', 'fence charger', 'energiser', 'electric fence', 'fence controller', 'fence energiser'],
        'FT-BT-SOIL-7IN1' => ['soil sensor', 'npk sensor', 'soil moisture', '7 in 1 soil', 'soil ph', 'soil nutrient', 'soil integrated sensor', 'soil detector'],
        'FT-BT-GRAIN-MC' => ['grain moisture', 'moisture meter', 'moisture tester', 'grain tester', 'moisture analyzer'],
        'FT-BT-BRIX-COL' => ['refractometer', 'brix meter', 'brix refractometer', 'colostrum'],
        'FT-BT-GREASE-20V' => ['grease gun', 'cordless grease', 'electric grease gun', 'battery grease gun'],
        'FT-BT-WIRE-TENS' => ['tension meter', 'wire tension', 'cable tension', 'tension gauge', 'tensiometer'],
        'FT-BT-TRAILER-CAM' => ['reversing camera', 'backup camera', 'trailer camera', 'wireless camera', 'rear view camera', 'hitch camera', 'reverse camera'],
        'FT-BT-THERMAL-CAM' => ['thermal camera', 'thermal imaging', 'infrared camera', 'thermal imager', 'thermal imaging camera'],
    ];

    public function handle(): int
    {
        $minScore = max(1, (int) $this->option('min-score'));

        $candidates = Product::where('sku', 'like', 'FT-BT-%')->orderBy('sku')->get();
        $listings = Product::where('sku', 'like', 'FT-ALI-%')->with('images')->get();

        if ($candidates->isEmpty()) {
            $this->warn('No FT-BT candidates found — run Top20BoerTechSeeder first.');

            return self::SUCCESS;
        }

        $this->info("Matching {$candidates->count()} FT-BT candidates against {$listings->count()} FT-ALI listings (read-only)...");

        $rows = [];
        $detail = [];
        $strong = 0;
        $weak = 0;
        $none = 0;

        foreach ($candidates as $candidate) {
            $keywords = self::KEYWORDS[$candidate->sku] ?? [];
            $scored = [];

            foreach ($listings as $listing) {
                $title = Str::lower($listing->title);
                $blob = Str::lower(json_encode($listing->specifications).' '.$listing->short_description);
                $score = 0;
                $titleHit = false;

                foreach ($keywords as $kw) {
                    if (str_contains($title, $kw)) {
                        $score += 3;
                        $titleHit = true;
                    } elseif (str_contains($blob, $kw)) {
                        $score += 1;
                    }
                }

                if ($titleHit && $score >= $minScore) {
                    $scored[] = ['listing' => $listing, 'score' => $score];
                }
            }

            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']
                ?: $b['listing']->images->count() <=> $a['listing']->images->count());

            $top = array_slice($scored, 0, 3);
            $best = $top[0] ?? null;

            $strength = match (true) {
                $best === null => 'none',
                $best['score'] >= 6 => 'strong',
                default => 'weak',
            };
            $strength === 'strong' ? $strong++ : ($strength === 'weak' ? $weak++ : $none++);

            $rows[] = [
                'Candidate' => $candidate->sku,
                'Title' => Str::limit($candidate->title, 30),
                'Match' => strtoupper($strength),
                'Best FT-ALI' => $best ? $best['listing']->sku : '—',
                'Supplier' => $best ? Str::limit($best['listing']->supplier_name ?: '—', 26) : '—',
                'Imgs' => $best ? $best['listing']->images->count() : 0,
                'Score' => $best ? $best['score'] : 0,
            ];

            $detail[$candidate->sku] = [
                'candidate' => $candidate,
                'matches' => $top,
            ];
        }

        $this->newLine();
        $this->table(['Candidate', 'Title', 'Match', 'Best FT-ALI', 'Supplier', 'Imgs', 'Score'], $rows);
        $this->newLine();
        $this->line("Strong matches: {$strong}   ·   Weak (needs review): {$weak}   ·   No match: {$none}");

        $this->newLine();
        $this->line('<options=bold>Per-candidate detail — verify before linking anything</>');
        foreach ($detail as $sku => $d) {
            $this->newLine();
            $this->line("<fg=yellow>{$sku}</>  {$d['candidate']->title}");

            if ($d['matches'] === []) {
                $this->line('    no FT-ALI listing scored above the threshold — this one needs a fresh sourcing pass');

                continue;
            }

            foreach ($d['matches'] as $i => $m) {
                $l = $m['listing'];
                $this->line(sprintf(
                    '    %d. [score %d] %s  (%d imgs)  %s',
                    $i + 1,
                    $m['score'],
                    $l->sku,
                    $l->images->count(),
                    $l->supplier_name ?: 'supplier not recorded',
                ));
                $this->line('       '.$l->title);
                $this->line('       source: '.($l->source_url ?: '— none on file —'));
            }
        }

        $this->newLine();
        $this->comment('No records were changed. To adopt a verified match, confirm the pair by hand on the '
            .'candidate\'s admin page (or ask for a `catalog:link-boer-tech <FT-BT-sku> <FT-ALI-sku>` command that '
            .'copies source_url / supplier / images / specs for one reviewed pair). Candidates stay in pending_review '
            .'until a real supplier, price and specs are confirmed.');

        return self::SUCCESS;
    }
}
