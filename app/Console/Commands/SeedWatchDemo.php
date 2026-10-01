<?php

namespace App\Console\Commands;

use App\Models\Animal;
use App\Models\DeviceReading;
use App\Models\License;
use App\Models\Reader;
use App\Models\ReaderSync;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Demo data for KraalTrac Watch and a custom device, on a customer account that already has a demo herd. */
class SeedWatchDemo extends Command
{
    protected $signature = 'herd:demo-watch {email}';

    protected $description = 'Seed two water points with two weeks of visits, plus a custom tank sensor';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user || ! $user->isCustomer()) {
            $this->error('Only customer accounts.');

            return self::FAILURE;
        }
        mt_srand(crc32($user->email.'watch'));

        DB::transaction(function () use ($user) {
            License::firstOrCreate(['user_id' => $user->id, 'module' => 'watch'], ['code' => License::generateCode(), 'activated_at' => now(), 'device_model' => 'KraalTrac Watch', 'notes' => 'Demo']);

            Reader::where('user_id', $user->id)->whereIn('kind', ['watch', 'custom'])->delete();
            $points = [
                Reader::create(['user_id' => $user->id, 'kind' => 'watch', 'name' => 'Watch 1', 'location' => 'Trough — Bergkamp', 'model' => 'KraalTrac Watch', 'alert_hours' => 24, 'last_synced_at' => now()->subMinutes(3)]),
                Reader::create(['user_id' => $user->id, 'kind' => 'watch', 'name' => 'Watch 2', 'location' => 'Gate — Rivierkamp', 'model' => 'KraalTrac Watch', 'alert_hours' => 36, 'last_synced_at' => now()->subMinutes(9)]),
            ];

            $ewes = Animal::where('user_id', $user->id)->where('in_herd', true)->where('status', 'active')->where('sex', 'F')->whereNotNull('eid')->limit(48)->get();
            $missing = $ewes->take(3)->pluck('id');

            foreach (range(13, 0) as $daysAgo) {
                $day = now()->subDays($daysAgo)->startOfDay();
                foreach ($points as $pi => $p) {
                    $sync = ReaderSync::create(['user_id' => $user->id, 'reader_id' => $p->id, 'source' => 'api', 'created_at' => $day->copy()->addHours(6), 'updated_at' => $day->copy()->addHours(20)]);
                    $n = 0;
                    foreach ($ewes as $i => $e) {
                        if ($i % 2 !== $pi) {
                            continue; // half the flock in each camp
                        }
                        if ($missing->contains($e->id) && $daysAgo <= 1 + $i) {
                            continue; // the three that stop coming
                        }
                        foreach ([mt_rand(7, 10), mt_rand(13, 16), mt_rand(17, 19)] as $k => $hour) {
                            if ($k === 2 && mt_rand(0, 2)) {
                                continue;
                            }
                            $at = $day->copy()->addHours($hour)->addMinutes(mt_rand(0, 59));
                            if ($at->isFuture()) {
                                continue;
                            }
                            foreach (range(1, mt_rand(1, 4)) as $r) { // repeated reads while drinking
                                Scan::create(['user_id' => $user->id, 'reader_sync_id' => $sync->id, 'animal_id' => $e->id, 'eid' => $e->eid, 'scanned_at' => $at->copy()->addSeconds($r * mt_rand(20, 90))]);
                                $n++;
                            }
                        }
                    }
                    $sync->update(['scan_count' => $n, 'matched_count' => $n]);
                }
            }

            $tank = Reader::create(['user_id' => $user->id, 'kind' => 'custom', 'name' => 'Tank — Bergkamp', 'location' => 'Next to the windpomp', 'model' => 'Custom',
                'metrics' => [['key' => 'level', 'label' => 'Tank level', 'unit' => '%', 'min' => 25, 'max' => null], ['key' => 'temp', 'label' => 'Water temp', 'unit' => '°C', 'min' => null, 'max' => 30]],
                'last_synced_at' => now()->subMinutes(12)]);
            $level = 90.0;
            for ($h = 7 * 24; $h >= 0; $h -= 2) {
                $t = now()->subHours($h);
                $hour = (int) $t->format('G');
                $level += ($hour >= 9 && $hour <= 17 ? -2.6 : 1.4) + mt_rand(-5, 5) / 10;
                $level = max(8, min(98, $level - ($h < 30 ? 1.2 : 0)));
                DeviceReading::create(['reader_id' => $tank->id, 'metric' => 'level', 'value' => round($level, 1), 'recorded_at' => $t]);
                DeviceReading::create(['reader_id' => $tank->id, 'metric' => 'temp', 'value' => round(17 + 7 * sin(($hour - 9) / 24 * 2 * M_PI) + mt_rand(-10, 10) / 10, 1), 'recorded_at' => $t]);
            }
        });

        $this->info('Watch + custom device demo ready.');

        return self::SUCCESS;
    }
}
