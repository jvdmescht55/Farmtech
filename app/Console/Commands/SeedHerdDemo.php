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
        });

        $this->info('Demo herd ready: '.Animal::where('user_id', $user->id)->where('in_herd', true)->count().' animals, '.Scan::where('user_id', $user->id)->whereNotNull('weight_kg')->count().' weighings.');

        return self::SUCCESS;
    }
}
