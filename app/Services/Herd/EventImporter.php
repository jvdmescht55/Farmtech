<?php

namespace App\Services\Herd;

use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Logbook import: one event per row, matched to animals by visual ID or EID. */
class EventImporter
{
    public const TYPE = ['type', 'event', 'gebeurtenis', 'tipe'];

    /** @return array{created:int, skipped:int, unknown:array} */
    public function import(User $user, array $rows): array
    {
        $created = $skipped = 0;
        $unknown = [];
        $types = collect(config('herd.event_types'));

        DB::transaction(function () use ($user, $rows, $types, &$created, &$skipped, &$unknown) {
            foreach ($rows as $row) {
                $animal = $this->animal($user->id, $row);
                $rawType = Str::lower((string) CsvReader::pick($row, self::TYPE));
                $type = $types->has($rawType) ? $rawType
                    : ($types->search(fn ($t) => $rawType !== '' && Str::lower($t['label']) === $rawType) ?: $this->synonym($rawType));
                $date = $this->date(CsvReader::pick($row, ['date', 'datum']));

                if (! $animal || ! $type || ! $date) {
                    $skipped++;
                    if (! $animal && count($unknown) < 20) {
                        $unknown[] = CsvReader::pick($row, array_merge(ScanImporter::VID, ScanImporter::EID)) ?? '(leeg)';
                    }

                    continue;
                }

                $wd = CsvReader::pick($row, ['withdrawal_until', 'onttrekking_tot']);
                $wdDays = CsvReader::pick($row, ['withdrawal_days', 'onttrekking_dae', 'withdrawal']);
                AnimalEvent::create([
                    'user_id' => $user->id, 'animal_id' => $animal->id, 'type' => $type, 'date' => $date,
                    'product' => CsvReader::pick($row, ['product', 'produk', 'medicine', 'medisyne']),
                    'dose' => CsvReader::pick($row, ['dose', 'dosis']),
                    'withdrawal_until' => $wd ? $this->date($wd) : (is_numeric($wdDays) ? Carbon::parse($date)->addDays((int) $wdDays)->toDateString() : null),
                    'mate_id' => ($m = CsvReader::pick($row, ['mate', 'ram', 'bull', 'bul', 'sire'])) ? Animal::findOrReference($user->id, $m)?->id : null,
                    'count' => is_numeric($c = CsvReader::pick($row, ['count', 'aantal', 'born'])) ? (int) $c : null,
                    'result' => CsvReader::pick($row, ['result', 'uitslag']),
                    'notes' => CsvReader::pick($row, ['notes', 'notas', 'comment', 'opmerking']),
                ]);
                $created++;
            }
        });

        return compact('created', 'skipped', 'unknown');
    }

    private function animal(int $userId, array $row): ?Animal
    {
        if ($vid = Animal::normalizeVisualId(CsvReader::pick($row, ScanImporter::VID))) {
            if ($a = Animal::where('user_id', $userId)->where('visual_id', $vid)->first()) {
                return $a;
            }
        }
        $eid = preg_replace('/\D/', '', (string) CsvReader::pick($row, ScanImporter::EID));

        return $eid ? Animal::where('user_id', $userId)->where('eid', $eid)->first() : null;
    }

    private function synonym(string $t): ?string
    {
        $t = Str::ascii($t);

        return match (true) {
            Str::contains($t, ['dose', 'dosing', 'drench', 'doseer']) => 'dosing',
            Str::contains($t, ['vacc', 'inent', 'geent', 'enting', 'entstof', 'jab']) => 'vaccination',
            Str::contains($t, ['treat', 'behandel', 'injection', 'inspuit']) => 'treatment',
            Str::contains($t, ['mat', 'paar', 'dek', 'join', 'tup']) => 'mating',
            Str::contains($t, ['scan', 'dragtig', 'preg']) => 'pregnancy_scan',
            Str::contains($t, ['birth', 'lamb', 'calv', 'geboor', 'lam']) => 'birth',
            Str::contains($t, ['wean', 'speen']) => 'weaning',
            Str::contains($t, ['sold', 'sale', 'verkoop']) => 'sale',
            Str::contains($t, ['death', 'dead', 'died', 'vrek', 'dood']) => 'death',
            Str::contains($t, ['cull', 'uitskot']) => 'cull',
            $t !== '' => 'observation',
            default => null,
        };
    }

    private function date(?string $v): ?string
    {
        if (! $v) {
            return null;
        }
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $f) {
            try {
                $d = Carbon::createFromFormat('!'.$f, $v);
                if ($d && $d->format($f) === $v) {
                    return $d->toDateString();
                }
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($v)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
