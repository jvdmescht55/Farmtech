<?php

namespace App\Console\Commands;

use App\Models\Animal;
use App\Models\ReaderSync;
use App\Models\Scan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fills a customer account with a believable Meatmaster stud — pedigreed
 * ewes and lambs, four sires with genuinely different growth, and a year of
 * weigh sessions — so the Herd Management app can be demonstrated.
 */
class SeedHerdDemo extends Command
{
    protected $signature = 'herd:demo {email : Customer account to fill} {--reset : Remove that account\'s existing herd data first}';

    protected $description = 'Seed a demo herd (animals, pedigree, weigh sessions) into a customer account';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user || ! $user->isCustomer()) {
            $this->error('Only customer accounts can receive demo data.');

            return self::FAILURE;
        }

        mt_srand(crc32($user->email));

        DB::transaction(function () use ($user) {
            if ($this->option('reset')) {
                Animal::where('user_id', $user->id)->delete();
                ReaderSync::where('user_id', $user->id)->delete();
                Scan::where('user_id', $user->id)->delete();
            }

            $user->update(['farm_name' => $user->farm_name ?: 'Die Bult Meatmaster Stoet', 'breeder_number' => $user->breeder_number ?: '0696358',
                'farm_address' => $user->farm_address ?: 'Posbus 42, Kenhardt, 8900', 'stud_prefix' => 'DVS', 'breed' => 'Meatmaster']);

            $ref = fn (string $id, array $extra = []) => Animal::updateOrCreate(['user_id' => $user->id, 'visual_id' => $id], $extra + ['in_herd' => false, 'registered' => ! str_starts_with($id, 'CC'), 'is_commercial' => str_starts_with($id, 'CC')]);

            // Four sires, each with its own growth potential (g/day premium).
            $sires = [];
            foreach ([['DVS 222435', 38], ['DVS 222234', 12], ['DVS 222342', -6], ['DVS 219870', 24]] as $i => [$id, $bonus]) {
                $s = $ref($id, ['sex' => 'M', 'birth_type' => '0'.(1 + $i % 2), 'sire_id' => $ref('DVS 2110'.(12 + $i))->id, 'dam_id' => $ref('DVS 1991'.(11 + $i))->id]);
                $sires[] = [$s, $bonus];
            }

            // Ewe flock: mostly stud, a few graded-up lines from commercial foundation stock.
            $ewes = [];
            for ($i = 0; $i < 42; $i++) {
                $commercialLine = $i % 9 === 0;
                $ewe = Animal::updateOrCreate(['user_id' => $user->id, 'visual_id' => sprintf('DVS 21 %04d', 3100 + $i * 7)], [
                    'in_herd' => true, 'sex' => 'F', 'breed' => 'Meatmaster', 'registered' => true,
                    'eid' => '98200010'.sprintf('%07d', 3100 + $i * 7),
                    'birth_date' => Carbon::create(2021, 3, 1)->addDays(mt_rand(0, 60)), 'birth_type' => mt_rand(0, 2) ? '01' : '02',
                    'sire_id' => $ref('DVS 18'.(8150 + $i % 6))->id,
                    'dam_id' => $commercialLine ? $ref('CC 2'.(10480 + $i))->id : $ref('DVS 19'.(9000 + $i))->id,
                    'gen_score' => mt_rand(84, 99),
                    'dam_record' => ['first' => number_format(mt_rand(140, 220) / 10, 1), 'sp' => (string) mt_rand(230, 280), 'tl' => (string) mt_rand(2, 7), 'lb' => (string) mt_rand(3, 10), 'lw' => (string) mt_rand(3, 9), 'mli' => (string) mt_rand(80, 105), 'epi' => (string) mt_rand(95, 120)],
                    'ebvs' => ['wean_dir' => ['v' => round(mt_rand(-80, 260) / 100, 2), 'acc' => mt_rand(55, 70)], 'wean_mat' => ['v' => round(mt_rand(-70, 60) / 100, 2), 'acc' => mt_rand(38, 48)], 'pw_dir' => ['v' => round(mt_rand(-70, 330) / 100, 2), 'acc' => mt_rand(38, 45)], 'nlw' => ['v' => round(mt_rand(0, 160) / 100, 2), 'acc' => mt_rand(30, 40)]],
                    'status' => 'active',
                ]);
                $ewes[] = $ewe;
            }

            // Lamb crop: born Sep–Oct 2025, weighed from birth through ~12 months.
            $sessionDates = collect([0, 58, 100, 150, 210, 270, 330])->map(fn ($d) => Carbon::create(2025, 10, 20)->addDays($d));
            $syncs = $sessionDates->mapWithKeys(fn ($d) => [$d->toDateString() => ReaderSync::create(['user_id' => $user->id, 'source' => 'csv', 'filename' => 'demo-session-'.$d->format('Ymd').'.csv', 'created_at' => $d->copy()->setTime(16, 0), 'updated_at' => $d->copy()->setTime(16, 0)])]);

            $n = 0;
            foreach ($ewes as $ei => $ewe) {
                $twins = mt_rand(0, 100) < 45;
                [$sire, $bonus] = $sires[$ei % 4];
                $born = Carbon::create(2025, 9, 1)->addDays(mt_rand(0, 45));
                foreach (range(1, $twins ? 2 : 1) as $k) {
                    $n++;
                    $sex = mt_rand(0, 1) ? 'M' : 'F';
                    $lamb = Animal::updateOrCreate(['user_id' => $user->id, 'visual_id' => sprintf('DVS 25 %04d', 5000 + $n)], [
                        'in_herd' => true, 'sex' => $sex, 'breed' => 'Meatmaster', 'registered' => true,
                        'eid' => '98200010'.sprintf('%07d', 5000 + $n),
                        'birth_date' => $born, 'birth_type' => $twins ? '02' : '01',
                        'sire_id' => $sire->id, 'dam_id' => $ewe->id, 'status' => mt_rand(0, 100) < 4 ? 'dead' : 'active',
                        'gen_score' => mt_rand(85, 99),
                        'ebvs' => ['wean_dir' => ['v' => round((mt_rand(-50, 250) + $bonus * 3) / 100, 2), 'acc' => mt_rand(40, 55)], 'pw_dir' => ['v' => round((mt_rand(-40, 300) + $bonus * 4) / 100, 2), 'acc' => mt_rand(30, 42)]],
                    ]);

                    $adg = 245 + $bonus + ($sex === 'M' ? 28 : 0) - ($twins ? 34 : 0) + mt_rand(-45, 45);
                    $kg = ($twins ? 3.9 : 4.8) + mt_rand(-5, 5) / 10;
                    $lastDate = $born;
                    $lastSeen = null;
                    foreach ($sessionDates as $si => $d) {
                        if ($d->lt($born) || mt_rand(0, 100) < 6) {
                            continue;
                        }
                        $days = $lastDate->diffInDays($d);
                        $rate = $adg * (1 - min(0.55, $born->diffInDays($d) / 700)); // growth slows with age
                        if ($si === 4 && mt_rand(0, 100) < 7) {
                            $rate = -mt_rand(20, 80); // a dry spell / illness
                        }
                        $kg = max(2.5, $kg + $rate * $days / 1000);
                        Scan::create(['user_id' => $user->id, 'reader_sync_id' => $syncs[$d->toDateString()]->id, 'animal_id' => $lamb->id,
                            'eid' => $lamb->eid, 'weight_kg' => round($kg, 1), 'weigh_type' => $si === 2 ? 'wean' : ($si >= 4 ? 'post_wean' : 'routine'),
                            'scanned_at' => $d->copy()->setTime(7, 0)->addMinutes(mt_rand(0, 300))]);
                        $lastDate = $d;
                        $lastSeen = $d;
                    }
                    $lamb->update(['last_seen_at' => $lastSeen]);
                }
            }

            foreach ($syncs as $sync) {
                $count = $sync->scans()->count();
                $sync->update(['scan_count' => $count, 'matched_count' => $count]);
            }
            foreach ($ewes as $ewe) {
                $ewe->update(['last_seen_at' => $sessionDates->last()]);
            }

            $this->recentActivity($user, $ewes, $sires);
        });

        $this->info('Alerts now: '.app(\App\Services\Herd\HerdAlerts::class)->forUser($user->id)->count());
        $this->info('Demo herd ready: '.Animal::where('user_id', $user->id)->where('in_herd', true)->count().' animals, '.Scan::where('user_id', $user->id)->whereNotNull('weight_kg')->count().' weighings.');

        return self::SUCCESS;
    }

    /** The last few weeks on the farm — enough going on for the alerts engine to have something to say. */
    private function recentActivity(User $user, array $ewes, array $sires): void
    {
        $today = now()->startOfDay();
        $sync = ReaderSync::create(['user_id' => $user->id, 'source' => 'api', 'filename' => null, 'created_at' => $today->copy()->subDays(3)->setTime(9, 0)]);
        $weighDay = $today->copy()->subDays(3)->setTime(8, 0);

        // Re-weigh this year's lambs; a handful lose condition.
        $lambs = Animal::where('user_id', $user->id)->where('in_herd', true)->where('status', 'active')->where('visual_id', 'like', 'DVS 25 %')->get();
        foreach ($lambs as $i => $lamb) {
            $last = Scan::where('animal_id', $lamb->id)->whereNotNull('weight_kg')->orderByDesc('scanned_at')->first();
            if (! $last) {
                continue;
            }
            $delta = match (true) {
                $i % 17 === 3 => -round($last->weight_kg * 0.07, 1),   // sharp drop
                $i % 13 === 5 => -mt_rand(5, 15) / 10,                   // mild loss
                $i % 11 === 7 => mt_rand(0, 3) / 10,                     // barely growing
                default => mt_rand(20, 45) / 10,
            };
            Scan::create(['user_id' => $user->id, 'reader_sync_id' => $sync->id, 'animal_id' => $lamb->id, 'eid' => $lamb->eid,
                'weight_kg' => round($last->weight_kg + $delta, 1), 'weigh_type' => 'routine', 'scanned_at' => $weighDay->copy()->addMinutes($i * 2)]);
            $lamb->update(['last_seen_at' => $weighDay]);
        }
        $sync->update(['scan_count' => $lambs->count(), 'matched_count' => $lambs->count()]);

        // Spring lambing has started.
        $births = [[0, '01', 5.2], [1, '02', 3.9], [1, '02', 3.6], [2, '03', 3.3], [2, '03', 2.7], [2, '03', 3.1], [3, '01', 2.8]];
        foreach ($births as $k => [$ei, $type, $kg]) {
            $dam = $ewes[$ei];
            $born = $today->copy()->subDays(4 + $ei * 3);
            $lamb = Animal::create(['user_id' => $user->id, 'in_herd' => true, 'species' => 'sheep', 'visual_id' => sprintf('DVS 26 %04d', 100 + $k),
                'eid' => '98200020'.sprintf('%07d', 100 + $k), 'sex' => $k % 2 ? 'F' : 'M', 'breed' => 'Meatmaster', 'registered' => true,
                'birth_date' => $born, 'birth_type' => $type, 'sire_id' => $sires[$ei % 4][0]->id, 'dam_id' => $dam->id, 'last_seen_at' => $born]);
            Scan::create(['user_id' => $user->id, 'animal_id' => $lamb->id, 'eid' => $lamb->eid, 'weight_kg' => $kg, 'weigh_type' => 'birth', 'scanned_at' => $born->copy()->setTime(10, 0)]);
        }
        \App\Models\AnimalEvent::create(['user_id' => $user->id, 'animal_id' => $ewes[2]->id, 'type' => 'birth', 'date' => $today->copy()->subDays(10), 'count' => 3, 'notes' => 'Drieling, een swak']);

        // Matings — some ewes due soon, one overdue.
        foreach ([[5, 140], [6, 145], [7, 146], [8, 170]] as [$ei, $daysAgo]) {
            \App\Models\AnimalEvent::create(['user_id' => $user->id, 'animal_id' => $ewes[$ei]->id, 'type' => 'mating', 'date' => $today->copy()->subDays($daysAgo), 'mate_id' => $sires[0][0]->id]);
        }
        \App\Models\AnimalEvent::create(['user_id' => $user->id, 'animal_id' => $ewes[6]->id, 'type' => 'pregnancy_scan', 'date' => $today->copy()->subDays(80), 'result' => 'twins']);
        \App\Models\AnimalEvent::create(['user_id' => $user->id, 'animal_id' => $ewes[9]->id, 'type' => 'pregnancy_scan', 'date' => $today->copy()->subDays(80), 'result' => 'triplets']);
        \App\Models\AnimalEvent::create(['user_id' => $user->id, 'animal_id' => $ewes[11]->id, 'type' => 'pregnancy_scan', 'date' => $today->copy()->subDays(80), 'result' => 'empty']);

        // A dosing with a withholding period, and a treatment.
        foreach ($lambs->take(3) as $lamb) {
            \App\Models\AnimalEvent::create(['user_id' => $user->id, 'animal_id' => $lamb->id, 'type' => 'dosing', 'date' => $today->copy()->subDays(5), 'product' => 'Closantel', 'dose' => '5 ml', 'withdrawal_until' => $today->copy()->addDays(23)]);
        }

        // One ewe not seen for months; one lamb from a half-sib mating.
        $ewes[20]->update(['last_seen_at' => $today->copy()->subDays(140)]);
        $ewes[30]->update(['sire_id' => $sires[1][0]->sire_id]);
        Animal::create(['user_id' => $user->id, 'in_herd' => true, 'species' => 'sheep', 'visual_id' => 'DVS 26 0200', 'eid' => '982000200000200', 'sex' => 'M', 'breed' => 'Meatmaster',
            'birth_date' => $today->copy()->subDays(20), 'birth_type' => '01', 'sire_id' => $sires[1][0]->id, 'dam_id' => $ewes[30]->id, 'last_seen_at' => $today->copy()->subDays(20)]);
    }
}
