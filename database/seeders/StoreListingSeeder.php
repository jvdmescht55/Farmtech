<?php

namespace Database\Seeders;

use App\Models\StoreListing;
use Illuminate\Database\Seeder;

/** Draft listings for Farmtech's own devices. Safe to re-run: never overwrites a listing that already exists. */
class StoreListingSeeder extends Seeder
{
    public function run(): void
    {
        StoreListing::firstOrCreate(['slug' => 'kraaltrac-pro'], [
            'name' => 'KraalTrac Pro',
            'tagline' => 'EID handheld RFID reader & weight-logging terminal',
            'category' => 'Livestock management',
            'price_cents' => 799900,
            'availability' => 'Built to order · fully assembled & tested',
            'module' => 'rfid',
            'sort' => 1,
            'overview' => "Say goodbye to overpriced imported readers that lock you into pricey cloud subscriptions. The KraalTrac Pro is a rugged handheld EID reader and weigh-logging terminal built for South African kraal conditions.\n\nRecording birth weights, tracking weaning, or keeping stud records for your Meatmasters and cattle? Scan the tag, punch in the weight, and it lands in Herd Manager on farmtech.site — your herd book, growth figures and alerts update by themselves. Offline-first, so you never lose a record out in the far camps.",
            'features' => [
                ['title' => 'Instant EID scanning', 'body' => 'Built-in 134.2 kHz FDX-B antenna wand reads standard animal ear tags on contact. No more typing numbers with cold fingers.'],
                ['title' => 'Offline-first — no signal, no stress', 'body' => 'Onboard flash memory keeps hundreds of records when the Wi-Fi doesn\'t reach the kraal, and syncs the moment you\'re back in range.'],
                ['title' => 'Herd Manager: 3 months free', 'body' => 'Pairs with your Herd Manager account using a 6-digit code. Weigh sessions, daily gain, comparisons, sorting, alerts and sale catalogues — no monthly subscription.'],
                ['title' => 'Your data stays yours', 'body' => 'Export everything to CSV or a full backup any time. We never sell or share your farm data.'],
                ['title' => 'Built farm-tough', 'body' => 'High-strength PETG with 3.5 mm walls, internal reinforcing spines and gussets — made to survive drops, dust, manure and the crush.'],
                ['title' => 'Full pedigree & weight suite', 'body' => 'Log birth, wean, post-wean and mature weights with sex, sire and dam — straight from the keypad.'],
            ],
            'specs' => [
                ['label' => 'Primary function', 'value' => 'Handheld 134.2 kHz RFID tag scanning & weight entry'],
                ['label' => 'RFID frequency', 'value' => '134.2 kHz (ISO 11784/11785 FDX-B ear tags)'],
                ['label' => 'Controller', 'value' => 'ESP32 with Wi-Fi'],
                ['label' => 'Display', 'value' => '20×4 I²C character LCD — readable outdoors'],
                ['label' => 'Keypad', 'value' => 'Sealed 3×3 matrix keypad with bezel lock'],
                ['label' => 'Antenna wand', 'value' => '40 cm reach, internal wire management & reinforcing spine'],
                ['label' => 'Enclosure', 'value' => '2-piece PETG clamshell, M3/M4 machine hardware'],
                ['label' => 'Power', 'value' => 'External power bank on a rear strap bracket'],
                ['label' => 'Software', 'value' => 'Herd Manager on farmtech.site: 3 months free, then R249 a month per farm'],
            ],
            'in_box' => [
                '1× KraalTrac Pro handheld terminal & wand, assembled and tested',
                '1× Power bank mounting strap kit',
                '1× Herd Manager activation card (pair in 6 digits)',
                '1× Quick-start guide',
            ],
            'why' => "Imported commercial livestock systems can retail for R25 000 to R50 000 and more. The KraalTrac Pro does the everyday kraal jobs — automatic EID reading, solid offline storage and proper herd records — at a fraction of the price. Built by farmers, for farmers.",
            'is_published' => false,
        ]);

        StoreListing::firstOrCreate(['slug' => 'kraaltrac-watch'], [
            'name' => 'KraalTrac Watch',
            'tagline' => 'Gate & water-point counter with missed-drink alerts',
            'category' => 'Livestock management',
            'price_cents' => null,
            'availability' => 'Coming soon — register interest',
            'module' => 'watch',
            'sort' => 2,
            'overview' => "Mount it at the trough or a gate. Every tagged animal that walks past gets counted, and if one hasn't come to drink in a day, you'll know before it becomes a problem.",
            'features' => [
                ['title' => 'Daily head count', 'body' => 'See how many came through today against how many should have.'],
                ['title' => 'Missed-drink alerts', 'body' => 'An animal that skips the water for 24 hours (or your own limit) gets flagged straight away.'],
                ['title' => 'Every water point', 'body' => 'Run one per trough or gate — each has its own count and limits.'],
                ['title' => 'Same herd book', 'body' => 'Visits show up on each animal\'s page next to its weights and records.'],
            ],
            'specs' => [
                ['label' => 'Reads', 'value' => '134.2 kHz FDX-B ear tags'],
                ['label' => 'Controller', 'value' => 'ESP32 with Wi-Fi'],
                ['label' => 'Software', 'value' => 'Herd Manager, Watch section (same subscription as your scanner)'],
            ],
            'in_box' => [],
            'why' => null,
            'is_published' => false,
        ]);
    }
}
