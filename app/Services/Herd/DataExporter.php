<?php

namespace App\Services\Herd;

use App\Models\AnimalEvent;
use App\Models\User;

/** CSV exports. The herd register export uses the import template's columns, so it round-trips. */
class DataExporter
{
    public const TYPES = [
        'herd' => ['Herd book', 'Every animal with pedigree, EBVs and lambing record. Edit it in Excel and bring it back in.'],
        'weighings' => ['All weighings', 'Every weight with the previous one, the change and daily gain.'],
        'sessions' => ['Weigh-session summary', 'One row per weigh day: count, average, median, spread, gain.'],
        'events' => ['Records', 'Treatments, matings, births, sales and deaths.'],
        'alerts' => ['Alerts', 'Everything flagged right now, with what to do.'],
    ];

    public function __construct(private readonly WeighStats $stats, private readonly HerdAlerts $alerts) {}

    /** @return array{0: array, 1: iterable} headers + rows */
    public function build(User $user, string $type): array
    {
        $graph = PedigreeTier::graph($user->id);

        return match ($type) {
            'herd' => $this->herd($graph),
            'weighings' => [
                ['visual_id', 'eid', 'date', 'weight_kg', 'weigh_type', 'previous_kg', 'previous_date', 'days', 'change_kg', 'adg_g_day'],
                $this->stats->rows($user->id)->sortBy(['date', 'animal_id'])->map(fn ($r) => [
                    $graph[$r->animal_id]->visual_id, $graph[$r->animal_id]->eid, $r->date, $r->kg, $r->type, $r->prev_kg, $r->prev_date, $r->days, $r->change, $r->adg,
                ]),
            ],
            'sessions' => [
                ['date', 'weighed', 'mean_kg', 'median_kg', 'min_kg', 'max_kg', 'sd_kg', 'cv_pct', 'mean_adg_g_day', 'lost_weight', 'mean_change_vs_previous_kg'],
                $this->stats->sessions($user->id)->map(fn ($s) => [
                    $s->date, $s->kg['n'], $s->kg['mean'], $s->kg['median'], $s->kg['min'], $s->kg['max'], $s->kg['sd'], $s->kg['cv'], $s->adg['mean'], $s->lost, $s->mean_change,
                ]),
            ],
            'events' => [
                ['visual_id', 'eid', 'type', 'date', 'product', 'dose', 'withdrawal_until', 'mate', 'count', 'result', 'notes'],
                AnimalEvent::with('animal', 'mate')->where('user_id', $user->id)->orderBy('date')->get()->map(fn ($e) => [
                    $e->animal->visual_id, $e->animal->eid, $e->type, $e->date->toDateString(), $e->product, $e->dose, $e->withdrawal_until?->toDateString(), $e->mate?->visual_id, $e->count, $e->result, $e->notes,
                ]),
            ],
            'alerts' => [
                ['severity', 'category', 'visual_id', 'eid', 'alert', 'detail', 'action'],
                $this->alerts->forUser($user->id)->map(fn ($a) => [$a['severity'], $a['category'], $a['animal']?->visual_id, $a['animal']?->eid, $a['title'], $a['detail'], $a['action']]),
            ],
        };
    }

    private function herd($graph): array
    {
        $headers = array_merge(['species'], HerdImporter::templateHeaders());
        $rows = $graph->where('in_herd', true)->sortBy('visual_id', SORT_NATURAL)->map(function ($a) use ($headers) {
            $v = [
                'species' => $a->species, 'visual_id' => $a->visual_id, 'eid' => $a->eid, 'name' => $a->name, 'sex' => $a->sex,
                'birth_date' => $a->birth_date?->format('d/m/Y'), 'birth_type' => $a->birth_type, 'registered' => $a->registered ? 'yes' : 'no',
                'tier' => $a->tier, 'gen' => $a->gen_score, 'status' => $a->status,
                'sire' => $a->sire?->visual_id, 'dam' => $a->dam?->visual_id,
                'sire_sire' => $a->sire?->sire?->visual_id, 'sire_dam' => $a->sire?->dam?->visual_id,
                'dam_sire' => $a->dam?->sire?->visual_id, 'dam_dam' => $a->dam?->dam?->visual_id,
                'sire_birth_type' => $a->sire?->birth_type, 'dam_birth_type' => $a->dam?->birth_type, 'notes' => $a->notes,
            ];
            foreach (array_keys(config('herd.ebvs')) as $k) {
                $v[$k] = $a->ebvs[$k]['v'] ?? null;
                $v[$k.'_acc'] = $a->ebvs[$k]['acc'] ?? null;
            }
            foreach (array_keys(config('herd.dam_record')) as $k) {
                $v['dam_'.$k] = $a->dam_record[$k] ?? null;
            }

            return array_map(fn ($h) => $v[$h] ?? null, $headers);
        });

        return [$headers, $rows];
    }

    public function stream(User $user, string $type)
    {
        [$headers, $rows] = $this->build($user, $type);

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads Afrikaans characters correctly
            fputcsv($out, $headers);
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, "farmtech-{$type}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Everything in one ZIP. */
    public function backup(User $user): string
    {
        $path = storage_path('app/private/backup-'.$user->id.'-'.now()->format('YmdHis').'.zip');
        @mkdir(dirname($path), 0775, true);
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach (array_keys(self::TYPES) as $type) {
            [$headers, $rows] = $this->build($user, $type);
            $fh = fopen('php://temp', 'r+');
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, $headers);
            foreach ($rows as $r) {
                fputcsv($fh, $r);
            }
            rewind($fh);
            $zip->addFromString("{$type}.csv", stream_get_contents($fh));
            fclose($fh);
        }
        $zip->addFromString('README.txt', "Farmtech Herd Manager backup — ".now()->toDateTimeString()."\n".($user->farm_name ?: $user->name)."\n\nherd.csv can be re-imported under Data. events.csv and weighings.csv too.\n");
        $zip->close();

        return $path;
    }
}
