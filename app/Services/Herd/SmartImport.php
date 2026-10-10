<?php

namespace App\Services\Herd;

use App\Models\Animal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * "Drop in any spreadsheet": no templates. Every sheet is read, the header
 * row is found (even under a title or logo rows), each column is matched to
 * where it belongs by its name (English or Afrikaans) and by what's in it,
 * and every row is split into the herd book, weighings and records at once.
 * Columns we don't have a place for are kept in the animal's notes, so
 * nothing in the farmer's file is lost.
 */
class SmartImport
{
    /** Where a column can go: key => [label, group]. */
    public const FIELDS = [
        'visual_id' => ['Animal ID / ear tag', 'Animal'],
        'eid' => ['Electronic tag (15 digits)', 'Animal'],
        'name' => ['Name', 'Animal'],
        'sex' => ['Sex', 'Animal'],
        'species' => ['Species', 'Animal'],
        'breed' => ['Breed', 'Animal'],
        'birth_date' => ['Birth date', 'Animal'],
        'birth_type' => ['Single / twin', 'Animal'],
        'registered' => ['Registered', 'Animal'],
        'tier' => ['Tier / grade', 'Animal'],
        'gen' => ['Gen score', 'Animal'],
        'status' => ['Status (sold, dead…)', 'Animal'],
        'sire' => ['Sire (father)', 'Parents'],
        'dam' => ['Dam (mother)', 'Parents'],
        'sire_sire' => ['Sire\'s sire', 'Parents'],
        'sire_dam' => ['Sire\'s dam', 'Parents'],
        'dam_sire' => ['Dam\'s sire', 'Parents'],
        'dam_dam' => ['Dam\'s dam', 'Parents'],
        'weight' => ['Weight (kg)', 'Weighing'],
        'weight:birth' => ['Birth weight', 'Weighing'],
        'weight:wean' => ['Wean weight', 'Weighing'],
        'weight:post_wean' => ['Post-wean weight', 'Weighing'],
        'weight:mature' => ['Mature weight', 'Weighing'],
        'weigh_type' => ['Kind of weighing', 'Weighing'],
        'date' => ['Date (weighing or record)', 'Weighing'],
        'type' => ['Record type (dosing, mating…)', 'Records'],
        'product' => ['Product / medicine', 'Records'],
        'dose' => ['Dose', 'Records'],
        'withdrawal_days' => ['Withdrawal days', 'Records'],
        'withdrawal_until' => ['Withdrawal until', 'Records'],
        'mate' => ['Mated to', 'Records'],
        'count' => ['Number born', 'Records'],
        'result' => ['Result (scan…)', 'Records'],
        'notes' => ['Notes', 'Other'],
        'extra' => ['Keep in notes', 'Other'],
        'skip' => ['Leave out', 'Other'],
    ];

    private const HERD = ['name', 'sex', 'species', 'breed', 'birth_date', 'birth_type', 'registered', 'tier', 'gen', 'status',
        'sire', 'dam', 'sire_sire', 'sire_dam', 'dam_sire', 'dam_dam', 'sire_birth_type', 'dam_birth_type', 'notes', 'extra'];

    private ?int $userId = null;

    private const WEIGH_DAYS = ['birth' => 0, 'wean' => 100, 'post_wean' => 210, 'mature' => 365];

    // ------------------------------------------------------------------
    // Reading and understanding a file
    // ------------------------------------------------------------------

    /**
     * Look at every sheet and decide what each column is.
     *
     * @return array<int, array{name: string, header_row: ?int, headers: string[], map: string[], rows: int, sample: array, summary: array}>
     */
    public function analyse(string $path, string $filename): array
    {
        $out = [];
        foreach (CsvReader::sheets($path, pathinfo($filename, PATHINFO_FILENAME) ?: 'Sheet1') as $name => $grid) {
            [$headerRow, $headers, $body] = $this->split($grid);
            if (! $body) {
                continue;
            }
            $map = $this->guessMap($headers, $body);
            $sample = array_slice($body, 0, 6);
            // Show Excel day numbers as the dates they are.
            foreach ($map as $i => $f) {
                if (in_array($f, ['birth_date', 'date', 'withdrawal_until'], true)) {
                    foreach ($sample as &$r) {
                        if (is_numeric($r[$i] ?? null) && $r[$i] > 20000 && $r[$i] < 70000 && ($d = $this->date($r[$i]))) {
                            $r[$i] = Carbon::parse($d)->format('d/m/Y');
                        }
                    }
                    unset($r);
                }
            }
            $out[] = [
                'name' => (string) $name,
                'header_row' => $headerRow,
                'headers' => $headers,
                'map' => $map,
                'rows' => count($body),
                'sample' => $sample,
                'summary' => $this->summary($map, $body),
            ];
        }

        return $out;
    }

    /** Pick the header row: the one in the first 15 whose cells we recognise best. */
    private function split(array $grid): array
    {
        $grid = array_values(array_filter($grid, fn ($r) => trim(implode('', $r)) !== ''));
        if (! $grid) {
            return [null, [], []];
        }

        // Reader dumps with no header at all: the first cell is a 15-digit tag.
        if (preg_match('/^\d{15}$/', preg_replace('/[\s.]/', '', $grid[0][0] ?? ''))) {
            $width = max(array_map('count', array_slice($grid, 0, 50)));

            return [null, array_map(fn ($i) => 'Column '.($i + 1), range(0, $width - 1)), $this->pad($grid, $width)];
        }

        $best = 0;
        $bestScore = -1;
        foreach (array_slice($grid, 0, 15, true) as $i => $row) {
            $filled = array_filter($row, fn ($c) => trim($c) !== '');
            $score = count(array_filter($filled, fn ($c) => ! in_array($this->guess($c), ['extra'], true)));
            // A header row is words, not numbers.
            $score -= count(array_filter($filled, fn ($c) => is_numeric(str_replace([',', ' '], ['.', ''], $c)))) * 0.5;
            if (count($filled) >= 2 && $score > $bestScore) {
                [$best, $bestScore] = [$i, $score];
            }
        }

        $headers = $grid[$best];
        $body = array_slice($grid, $best + 1);
        $width = max(count($headers), $body ? max(array_map('count', array_slice($body, 0, 200))) : 0);
        foreach (range(0, $width - 1) as $i) {
            $h = trim($headers[$i] ?? '');
            // Excel saves a date header ("01/03/2026") as a day number.
            if (is_numeric($h) && $h > 30000 && $h < 70000) {
                $h = date('d/m/Y', (int) round(((float) $h - 25569) * 86400));
            }
            $headers[$i] = $h !== '' ? $h : 'Column '.($i + 1);
        }

        // Drop repeated header rows and "Total / Average" lines at the bottom.
        $body = array_values(array_filter($body, function ($r) use ($headers) {
            $first = Str::lower(trim($r[0] ?? ''));

            return $r !== array_slice($headers, 0, count($r)) && ! Str::startsWith($first, ['total', 'totaal', 'average', 'gemiddeld', 'avg', 'sum']);
        }));

        return [$best, array_values($headers), $this->pad($body, $width)];
    }

    private function pad(array $rows, int $width): array
    {
        return array_map(fn ($r) => array_slice(array_pad(array_map(fn ($c) => trim((string) $c), $r), $width, ''), 0, $width), $rows);
    }

    /** Header name → field, then corrected by what's actually in each column. */
    public function guessMap(array $headers, array $body): array
    {
        $map = [];
        foreach ($headers as $i => $h) {
            $map[$i] = str_starts_with($h, 'Column ') ? 'extra' : $this->guess($h);
        }
        $column = fn (int $i) => array_values(array_filter(array_column($body, $i), fn ($v) => $v !== ''));
        $share = function (int $i, callable $test) use ($column) {
            $vals = array_slice($column($i), 0, 300);

            return $vals ? count(array_filter($vals, $test)) / count($vals) : 0;
        };
        $isEid = fn ($v) => (bool) preg_match('/^\d{15}$/', preg_replace('/[\s.]/', '', $v));

        // Tag columns: 15 digits is an electronic tag, anything else an ear tag.
        foreach ($map as $i => $f) {
            if (in_array($f, ['visual_id', 'eid', 'extra'], true)) {
                $s = $share($i, $isEid);
                if ($s >= 0.6 && ! in_array('eid', $map, true)) {
                    $map[$i] = 'eid';
                } elseif ($f === 'eid' && $s < 0.3) {
                    $map[$i] = in_array('visual_id', $map, true) ? 'extra' : 'visual_id';
                }
            }
        }
        // Headerless dumps: dates and weights by look.
        foreach ($map as $i => $f) {
            if ($f !== 'extra' || ! str_starts_with($headers[$i], 'Column ')) {
                continue;
            }
            if (! in_array('date', $map, true) && $share($i, fn ($v) => $this->date($v) !== null || (ctype_digit($v) && strlen($v) >= 9)) >= 0.8) {
                $map[$i] = 'date';
            } elseif (! in_array('weight', $map, true) && $share($i, fn ($v) => is_numeric(str_replace(',', '.', $v)) && (float) str_replace(',', '.', $v) > 0.3 && (float) str_replace(',', '.', $v) < 1500) >= 0.8) {
                $map[$i] = 'weight';
            } elseif (! in_array('visual_id', $map, true) && $share($i, fn ($v) => ! is_numeric($v)) >= 0.8) {
                $map[$i] = 'visual_id';
            }
        }
        // Values must fit, or the column is kept as a note instead of half-lost.
        $checks = [
            'sex' => fn ($v) => $this->sex($v) !== null,
            'status' => fn ($v) => $this->status($v) !== null,
            'tier' => fn ($v) => in_array(strtoupper($v), config('herd.tiers'), true),
            'birth_date' => fn ($v) => $this->date($v) !== null,
            'date' => fn ($v) => $this->date($v) !== null || (ctype_digit($v) && strlen($v) >= 9),
            'weight' => fn ($v) => $this->kg($v) !== null,
            'gen' => fn ($v) => is_numeric($v),
            'count' => fn ($v) => is_numeric($v),
            'withdrawal_days' => fn ($v) => is_numeric($v),
        ];
        foreach ($map as $i => $f) {
            $base = str_starts_with($f, 'weight') ? 'weight' : $f;
            if (isset($checks[$base]) && $column($i) && $share($i, $checks[$base]) < 0.6) {
                $map[$i] = 'extra';
            }
        }
        // One column per field (the first wins); weights on different dates may repeat.
        $seen = [];
        foreach ($map as $i => $f) {
            if (in_array($f, ['extra', 'skip', 'notes'], true) || str_starts_with($f, 'weight@')) {
                continue;
            }
            if (isset($seen[$f])) {
                $map[$i] = 'extra';
            }
            $seen[$f] = true;
        }
        // In a records sheet a "Ram" or "Bull" column is who she was mated to.
        if (in_array('type', $map, true)) {
            foreach ($map as $i => $f) {
                if ($f === 'sire' && preg_match('/^(ram|bull|bul)/', CsvReader::key($headers[$i])) && ! in_array('mate', $map, true)) {
                    $map[$i] = 'mate';
                }
            }
        }
        // "Cow ID" or "Ewe no" is the animal itself, not a mother; a bare "Ewe" only when there's no other ID.
        if (! in_array('visual_id', $map, true)) {
            foreach ($map as $i => $f) {
                $hk = CsvReader::key($headers[$i]);
                if ($f === 'dam' && preg_match('/^(ewe|ooi|cow|koei|doe)(_?(id|no|nr|tag|number|nommer))?$/', $hk, $m)
                    && (! empty($m[2]) || ! in_array('eid', $map, true))) {
                    $map[$i] = 'visual_id';
                    break;
                }
            }
        }
        // No ID column found: the first column whose values are (nearly) all different.
        if (! array_intersect($map, ['visual_id', 'eid'])) {
            foreach ($map as $i => $f) {
                $vals = $column($i);
                if ($f === 'extra' && count($vals) >= count($body) * 0.9 && count(array_unique($vals)) >= count($vals) * 0.95) {
                    $map[$i] = 'visual_id';
                    break;
                }
            }
        }

        return $map;
    }

    /** What a header name means. Order matters: specific names before general ones. */
    public function guess(string $header): string
    {
        $k = CsvReader::key($header);
        if ($k === '') {
            return 'extra';
        }
        $w = '(weight|wt|gewig|mass|massa|kg|lw|mas)';

        // A date in the header of a weight column (or a header that IS a date) = a weighing on that day.
        if ($d = $this->dateIn($header)) {
            if (preg_match("/$w/", $k) || preg_match('/^[\d_]+$/', $k) || ! preg_match('/[a-z]{4,}/', preg_replace('/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*/', '', $k))) {
                return 'weight@'.$d;
            }
        }

        $ebvs = array_keys(config('herd.ebvs'));
        $bare = preg_replace('/^(ebv|bv|teelwaarde)_|_(ebv|bv)$/', '', $k);
        if (in_array($bare, $ebvs, true)) {
            return 'ebv:'.$bare;
        }
        if (str_ends_with($bare, '_acc') && in_array(substr($bare, 0, -4), $ebvs, true)) {
            return 'acc:'.substr($bare, 0, -4);
        }
        foreach (array_keys(config('herd.dam_record')) as $r) {
            if ($k === 'dam_'.$r) {
                return 'dam_record:'.$r;
            }
        }

        $rules = [
            'sire_sire' => '/^(sire|vaar|father)_?s?_(sire|vaar|father)$|^ss$|paternal_grand_?(sire|father)|vaar_se_vaar|grand_?sire_?paternal/',
            'sire_dam' => '/^(sire|vaar|father)_?s?_(dam|moer|mother)$|^sd$|paternal_grand_?(dam|mother)|vaar_se_moer/',
            'dam_sire' => '/^(dam|moer|mother)_?s?_(sire|vaar|father)$|^ds$|maternal_grand_?(sire|father)|moer_se_vaar/',
            'dam_dam' => '/^(dam|moer|mother)_?s?_(dam|moer|mother)$|^dd$|maternal_grand_?(dam|mother)|moer_se_moer/',
            'sire_birth_type' => '/^(sire|vaar)_birth_?(type|status)$/',
            'dam_birth_type' => '/^(dam|moer)_birth_?(type|status)$/',
            'withdrawal_until' => '/(withdrawal|onttrekking|withhold\w*)_?(until|tot|date|datum|end)|safe_?(from|date|to_?sell)|slaughter_?(from|date)/',
            'withdrawal_days' => '/^(withdrawal|onttrekking|wdl|whp|withhold\w*)(_?(days|dae|period|periode|tyd))?$/',
            'weigh_type' => '/weigh_?type|weight_?type|^wtype$|weeg_?tipe|soort_?weging/',
            // Gains, averages and targets are not weighings: keep them as notes.
            'extra' => '/gain|change|adg|toename|verskil|loss|diff|average|gemiddeld|target|teiken|price|prys|value|waarde|cost|koste|_r$|^r_|rand/',
            'weight:birth' => "/^(birth|geboorte|geb)_?$w|^$w?_?(at_)?birth$|^(bw|bwt|gebgew)$/",
            'weight:post_wean' => "/^(post_?wean\\w*|naspeen|yearling|jaar\\w*|12_?m\\w*|18_?m\\w*|6_?m\\w*|ses_?maande)_?$w|^$w?_?(post_?wean\\w*|naspeen)$|^(pww|pwwt|yw)$/",
            'weight:wean' => "/^(wean\\w*|speen|100_?d\\w*|d100|w100)_?$w|^$w?_?(at_)?(wean\\w*|speen)$|^(ww|wwt)$/",
            'weight:mature' => "/^(mature|adult|volwasse)_?$w/",
            'birth_date' => '/birth_?date|date_?of_?birth|^dob$|^born$|gebore|geboorte_?datum|geb_?dat|birthday|lambing_?date|lam_?datum|calving_?date|kalf_?datum/',
            'date' => '/(^|_)(date|datum)($|_)/',
            'mate' => '/^(mate|mated_?to|ram_?used|bull_?used|dek_?ram|dekram|served_?by|joined_?(with|to)|mating_?(ram|bull|sire)|paar_?met|gepaar_?met)$/',
            'sire' => '/^(sire|vaar|father|vader|ram|bull|bul)(_?(id|no|nr|tag|number|nommer|eid))?$/',
            'dam' => '/^(dam|moer|mother|moeder|ewe|ooi|cow|koei|doe)(_?(id|no|nr|tag|number|nommer|eid))?$/',
            'eid' => '/^(eid|e_?id|rfid|electronic|elektronies\w*|e_?tag|chip|transponder|iso|uid|tag_?uid)(_?(id|no|nr|number|nommer|tag|code))?$|^electronic_?(id|tag|number)$|^eid_?\w*$/',
            'visual_id' => '/^(visual(_?(id|tag|no|nr))?|vid|animal(_?(id|no|nr|number|tag))?|dier(_?(id|no|nr|nommer))?|id|id_?(no|nr|number|nommer)|ear_?tag\w*|oor_?\w*|merk|tattoo|tatoe\w*|number|no|nr|nommer|tag|tag_?(no|nr|number|nommer)|identification|identity|lamb|lam|calf|kalf|kid|lamb_?(id|no|nr)|lam_?(no|nr)|calf_?(id|no|nr)|management_?(tag|id|no)|(sheep|cow|ewe|goat|skaap|bok)_?(id|no|nr|tag)|stud_?(no|nr|number)|reg(istration)?_?(no|nr|number))$/',
            'name' => '/^(name|naam|animal_?name)$/',
            'sex' => '/^(sex|gender|geslag|m_?f|ram_?ooi)$/',
            'species' => '/^(species|spesie|soort|animal_?type|kind_?of_?animal)$/',
            'breed' => '/^(breed|ras)$/',
            'birth_type' => '/^(birth_?(type|status|rank)|geboorte_?(tipe|status)|single_?twin|twin|tweeling|born_?as|litter_?size|type_?of_?birth|bt|birth_?rank)$/',
            'registered' => '/^(registered|reg|geregistreer|registration_?status|is_?registered)$/',
            'tier' => '/^(tier|grade|graad|klas|class|status_?tier|category|kategorie|stud_?class)$/',
            'gen' => '/^(gen|gen_?score|genetic_?score)$/',
            'status' => '/^(status|state|toestand|alive|in_?herd|lewend)$/',
            'type' => '/^(type|event|event_?type|gebeurtenis|tipe|activity|action|aksie|what|wat|treatment_?type|record_?type|job|taak)$/',
            'product' => '/(product|produk|medicine|medisyne|remed\w*|drug|vaccine|entstof|middel|treatment|behandeling|dip|dose_?name)/',
            'dose' => '/^(dose|dosis|dosage|amount|hoeveelheid|ml|dose_?ml|dosis_?ml|volume)$/',
            'count' => '/^(count|aantal|number_?born|born_?alive|no_?born|lambs_?born|calves_?born|lammers|kalwers|litter)$/',
            'result' => '/^(result|uitslag|outcome|preg\w*_?result|scan_?result|pregnan\w*|dragtig\w*|scanned_?result)$/',
            'notes' => '/^(notes?|notas?|comments?|kommentaar|opmerk\w*|remarks?|memo|description|beskrywing|info)$/',
            'weight' => "/^(weight|wt|w|kg|mass|massa|gewig|lw|live_?weight|lewende_?(massa|gewig)|body_?weight|weight_?kg|kg_?weight|mass_?kg|gewig_?kg|massa_?kg|weight_?\\w*)$|gewig|massa/",
            'date' => '/^(date|datum|day|dag|scanned_?at|date_?time|time|ts|timestamp|when|wanneer)$|weigh\w*_?date|weeg_?datum|event_?date|treatment_?date|dosing_?date|scan_?date|date_?(weighed|done|of_\w+)|date|datum/',
        ];
        foreach ($rules as $field => $re) {
            if (preg_match($re, $k)) {
                return $field;
            }
        }

        return 'extra';
    }

    /** What will happen, in farmer words, for the preview. */
    private function summary(array $map, array $body): array
    {
        $plan = $this->plan($map);
        $ids = $weights = $records = $noId = 0;
        $animals = [];
        foreach ($body as $r) {
            $row = $this->row($map, $r);
            if (! $row['visual_id'] && ! $row['eid']) {
                $noId++;

                continue;
            }
            $animals[$row['visual_id'] ?: $row['eid']] = true;
            $weights += count($row['weights']);
            $records += (int) ($plan['events'] && $row['type'] !== null);
        }

        return [
            'animals' => count($animals),
            'herd' => $plan['herd'],
            'weights' => $weights,
            'presence' => $plan['presence'] ? count($body) - $noId : 0,
            'records' => $records,
            'no_id' => $noId,
            'kept' => array_values(array_keys(array_filter($map, fn ($f) => $f === 'extra'))),
        ];
    }

    /** Which importers a sheet feeds. */
    private function plan(array $map): array
    {
        $fields = array_values($map);
        $weights = (bool) array_filter($fields, fn ($f) => str_starts_with($f, 'weight') && $f !== 'weigh_type');
        $events = in_array('type', $fields, true);
        $herdish = (bool) array_intersect($fields, array_diff(self::HERD, ['extra', 'notes'])) || (bool) array_filter($fields, fn ($f) => str_contains($f, ':') && ! str_starts_with($f, 'weight'));

        return [
            'herd' => $herdish || (! $weights && ! $events && ! in_array('date', $fields, true)),
            'weights' => $weights,
            'events' => $events,
            // A bare list of tags with times (a reader dump): each line is a sighting.
            'presence' => ! $weights && ! $events && ! $herdish && in_array('date', $fields, true),
        ];
    }

    // ------------------------------------------------------------------
    // Bringing it in
    // ------------------------------------------------------------------

    /**
     * @param  array<int, array<int, string>>  $maps  sheet index => column index => field (from the preview; empty = our guess)
     * @param  array<int, bool>  $include  sheet index => import it?
     * @return array{animals: int, created: int, updated: int, weights: int, records: int, kept: int, skipped: int, unknown: array}
     */
    public function run(User $user, string $path, string $filename, array $maps = [], array $include = []): array
    {
        $this->userId = $user->id;
        $total = ['animals' => 0, 'created' => 0, 'updated' => 0, 'weights' => 0, 'records' => 0, 'kept' => 0, 'skipped' => 0, 'unknown' => []];
        $sheets = CsvReader::sheets($path, pathinfo($filename, PATHINFO_FILENAME) ?: 'Sheet1');
        $index = -1;

        foreach ($sheets as $name => $grid) {
            [, $headers, $body] = $this->split($grid);
            if (! $body) {
                continue;
            }
            $index++;
            if (array_key_exists($index, $include) && ! $include[$index]) {
                continue;
            }
            $map = $this->guessMap($headers, $body);
            foreach ($maps[$index] ?? [] as $i => $f) {
                if (isset($map[$i]) && $this->validField((string) $f)) {
                    $map[$i] = (string) $f;
                }
            }
            $r = $this->importSheet($user, $headers, $map, $body, count($sheets) > 1 ? "$filename · $name" : $filename);
            foreach (['animals', 'created', 'updated', 'weights', 'records', 'kept', 'skipped'] as $k) {
                $total[$k] += $r[$k];
            }
            $total['unknown'] = array_slice(array_unique(array_merge($total['unknown'], $r['unknown'])), 0, 10);
        }

        return $total;
    }

    /** Farmer words for any field, including the per-file ones (weights on a date, EBVs). */
    public static function label(string $f): string
    {
        if (isset(self::FIELDS[$f])) {
            return self::FIELDS[$f][0];
        }
        if (str_starts_with($f, 'weight@')) {
            return 'Weight on '.Carbon::parse(substr($f, 7))->format('d/m/Y');
        }
        [$kind, $key] = array_pad(explode(':', $f, 2), 2, '');

        return match ($kind) {
            'ebv' => 'EBV: '.(config("herd.ebvs.$key.label") ?? $key),
            'acc' => 'EBV accuracy: '.(config("herd.ebvs.$key.label") ?? $key),
            'dam_record' => 'Dam record: '.(config("herd.dam_record.$key.label") ?? config("herd.dam_record.$key") ?? $key),
            'sire_birth_type' => 'Sire\'s birth type',
            'dam_birth_type' => 'Dam\'s birth type',
            default => $f,
        };
    }

    public function validField(string $f): bool
    {
        return array_key_exists($f, self::FIELDS)
            || (bool) preg_match('/^weight@\d{4}-\d{2}-\d{2}$/', $f)
            || (bool) preg_match('/^(ebv|acc):('.implode('|', array_keys(config('herd.ebvs'))).')$/', $f)
            || (bool) preg_match('/^dam_record:('.implode('|', array_keys(config('herd.dam_record'))).')$/', $f)
            || in_array($f, ['sire_birth_type', 'dam_birth_type'], true);
    }

    private function importSheet(User $user, array $headers, array $map, array $body, string $label): array
    {
        $plan = $this->plan($map);
        $herdRows = $scanRows = $eventRows = $notes = [];
        $skipped = 0;
        $animals = [];

        foreach ($body as $cells) {
            $row = $this->row($map, $cells, $headers);
            if (! $row['visual_id'] && ! $row['eid']) {
                $skipped++;

                continue;
            }
            // Only an electronic tag: use the ID this farm already has for it, or the tag itself.
            if (! $row['visual_id']) {
                $row['visual_id'] = Animal::where('user_id', $user->id)->where('eid', $row['eid'])->value('visual_id') ?? $row['eid'];
            }
            $animals[$row['visual_id']] = true;

            if ($plan['herd'] || ($plan['events'] && ! Animal::where('user_id', $user->id)->where('visual_id', $row['visual_id'])->exists())) {
                $herdRows[] = $row['herd'] + ['visual_id' => $row['visual_id'], 'eid' => $row['eid']];
            }
            foreach ($row['weights'] as [$kg, $type, $date]) {
                $date ??= $row['date'] ?? $this->weighDate($type, $row['herd']['birth_date'] ?? null);
                $scanRows[] = ['visual_id' => $row['visual_id'], 'eid' => $row['eid'], 'weight' => $kg, 'weigh_type' => $type, 'date' => $date];
            }
            if ($plan['presence']) {
                $scanRows[] = ['visual_id' => $row['visual_id'], 'eid' => $row['eid'], 'date' => $row['date']];
            }
            if ($plan['events'] && $row['type'] !== null) {
                $eventRows[] = $row['event'] + ['visual_id' => $row['visual_id'], 'eid' => $row['eid'], 'type' => $row['type'], 'date' => $row['date'],
                    'notes' => implode('; ', array_filter([$row['event']['notes'] ?? null, ...$row['extras']]))];
            } elseif ($row['extras'] || $row['note']) {
                $when = ! $plan['herd'] && $row['date'] ? ' ('.Carbon::parse($row['date'])->format('d/m/Y').')' : '';
                $notes[$row['visual_id']] = array_merge($notes[$row['visual_id']] ?? [],
                    array_filter([$row['note']]), array_map(fn ($e) => $e.$when, $row['extras']));
            }
        }

        $created = $updated = $weights = $records = $kept = 0;
        $unknown = [];
        if ($herdRows) {
            $h = app(HerdImporter::class)->import($user, $herdRows);
            [$created, $updated] = [$h['created'], $h['updated']];
        }
        if ($scanRows) {
            $sync = app(ScanImporter::class)->import($user, null, 'csv', $scanRows, Str::limit($label, 180, ''));
            $weights = (int) $sync->scan_count;
            $created += (int) $sync->new_count;
        }
        if ($eventRows) {
            $e = app(EventImporter::class)->import($user, $eventRows);
            [$records, $unknown] = [$e['created'], $e['unknown']];
            $skipped += $e['skipped'];
        }
        foreach ($notes as $vid => $lines) {
            $animal = Animal::where('user_id', $user->id)->where('visual_id', Animal::normalizeVisualId($vid))->first();
            if (! $animal) {
                continue;
            }
            $existing = (string) $animal->notes;
            $new = array_values(array_filter(array_unique($lines), fn ($l) => ! str_contains($existing, $l)));
            if ($new) {
                $animal->update(['notes' => trim($existing."\n".implode("\n", $new))]);
                $kept += count($new);
            }
        }

        return ['animals' => count($animals), 'created' => $created, 'updated' => $updated, 'weights' => $weights, 'records' => $records,
            'kept' => $kept, 'skipped' => $skipped, 'unknown' => $unknown];
    }

    /** One spreadsheet line → cleaned values, split by where they go. */
    private function row(array $map, array $cells, array $headers = []): array
    {
        $out = ['visual_id' => null, 'eid' => null, 'date' => null, 'type' => null, 'herd' => [], 'event' => [], 'weights' => [], 'extras' => [], 'note' => null];
        $weighType = null;
        foreach ($map as $i => $f) {
            $v = trim((string) ($cells[$i] ?? ''));
            if ($v === '' || $f === 'skip' || in_array(Str::lower($v), ['-', '—', 'n/a', 'na', 'null', '#n/a'], true)) {
                continue;
            }
            switch (true) {
                case $f === 'visual_id':
                    $out['visual_id'] = Animal::normalizeVisualId($v);
                    break;
                case $f === 'eid':
                    $digits = preg_replace('/\D/', '', $v);
                    $out['eid'] = strlen($digits) >= 8 ? $digits : null;
                    if (! $out['eid']) {
                        $out['visual_id'] ??= Animal::normalizeVisualId($v);
                    }
                    break;
                case $f === 'date':
                    $out['date'] = ctype_digit($v) && strlen($v) >= 9 ? $v : $this->date($v, true);
                    break;
                case $f === 'weigh_type':
                    $weighType = $v;
                    break;
                case $f === 'weight':
                    if (($kg = $this->kg($v)) !== null) {
                        $out['weights'][] = [$kg, '__row', null];
                    }
                    break;
                case str_starts_with($f, 'weight:'):
                    if (($kg = $this->kg($v)) !== null) {
                        $out['weights'][] = [$kg, substr($f, 7), null];
                    }
                    break;
                case str_starts_with($f, 'weight@'):
                    if (($kg = $this->kg($v)) !== null) {
                        $out['weights'][] = [$kg, 'routine', substr($f, 7)];
                    }
                    break;
                case $f === 'type':
                    $out['type'] = $v;
                    break;
                case in_array($f, ['product', 'dose', 'withdrawal_days', 'mate', 'count', 'result'], true):
                    $out['event'][$f] = $f === 'mate' ? $this->parentId($v) : $v;
                    break;
                case $f === 'withdrawal_until':
                    $out['event'][$f] = $this->date($v);
                    break;
                case $f === 'notes':
                    $out['note'] = $out['note'] ? $out['note'].'; '.$v : $v;
                    $out['event']['notes'] = $out['note'];
                    break;
                case $f === 'extra':
                    $out['extras'][] = ($headers[$i] ?? 'Column '.($i + 1)).': '.$v;
                    break;
                default:
                    $clean = $this->clean($f, $v);
                    if ($clean !== null) {
                        $out['herd'][str_contains($f, ':') ? $this->herdKey($f) : $f] = $clean;
                    } else {
                        // Doesn't fit the field: keep what the farmer wrote.
                        $out['extras'][] = ($headers[$i] ?? self::FIELDS[$f][0] ?? $f).': '.$v;
                    }
            }
        }
        // "Bonsmara" in the breed column says cattle even without a species column.
        if (! isset($out['herd']['species']) && isset($out['herd']['breed']) && ($sp = $this->species($out['herd']['breed']))) {
            $out['herd']['species'] = $sp;
        }
        foreach ($out['weights'] as &$w) {
            if ($w[1] === '__row') {
                $w[1] = $weighType ?? 'routine';
            }
        }

        return $out;
    }

    /** "ebv:wean_dir" → the column name HerdImporter reads. */
    private function herdKey(string $f): string
    {
        [$kind, $key] = explode(':', $f, 2);

        return match ($kind) {
            'acc' => $key.'_acc',
            'dam_record' => 'dam_'.$key,
            default => $key,
        };
    }

    private function clean(string $f, string $v): ?string
    {
        return match ($f) {
            'sex' => $this->sex($v),
            'status' => $this->status($v),
            'birth_date' => $this->date($v),
            'birth_type', 'sire_birth_type', 'dam_birth_type' => $this->birthType($v),
            'registered' => in_array(Str::lower($v), ['1', 'y', 'yes', 'ja', 'j', 'reg', 'registered', 'geregistreer', 'true', 'x', '✓'], true) ? 'yes' : 'no',
            'tier' => in_array(strtoupper($v), config('herd.tiers'), true) ? strtoupper($v) : null,
            'gen' => is_numeric($v) ? (string) (int) $v : null,
            'species' => $this->species($v),
            'sire', 'dam', 'sire_sire', 'sire_dam', 'dam_sire', 'dam_dam' => $this->parentId($v),
            default => str_contains($f, ':') && ! str_starts_with($f, 'dam_record:') ? (is_numeric(str_replace(',', '.', $v)) ? str_replace(',', '.', $v) : null) : $v,
        };
    }

    /** Parents given by electronic tag are linked to that animal's ID. */
    private function parentId(string $v): string
    {
        $digits = preg_replace('/[\s.]/', '', $v);
        if (preg_match('/^\d{15}$/', $digits) && ($vid = Animal::where('user_id', $this->userId ?? auth()->id())->where('eid', $digits)->value('visual_id'))) {
            return $vid;
        }

        return $v;
    }

    public function sex(string $v): ?string
    {
        $v = Str::lower(trim($v));

        return match (true) {
            in_array($v, ['m', 'male', 'ram', 'bull', 'bul', 'buck', 'bok', 'r', 'manlik', 'ramlam', 'ram lamb', 'bull calf', 'bulkalf', 'steer', 'os', 'wether', 'hamel', 'hamellam', '1'], true) => 'M',
            in_array($v, ['f', 'v', 'female', 'ewe', 'ooi', 'cow', 'koei', 'doe', 'heifer', 'vers', 'vroulik', 'ooilam', 'ewe lamb', 'heifer calf', 'verskalf', 'ooi lam', '2'], true) => 'F',
            default => null,
        };
    }

    public function status(string $v): ?string
    {
        $v = Str::lower(trim($v));

        return match (true) {
            in_array($v, ['active', 'aktief', 'alive', 'lewend', 'in herd', 'yes', 'ja', 'y', 'current', 'present'], true) => 'active',
            Str::contains($v, ['sold', 'verkoop', 'sale']) => 'sold',
            Str::contains($v, ['dead', 'died', 'dood', 'vrek', 'death']) => 'dead',
            Str::contains($v, ['cull', 'uitskot', 'slaughter', 'geslag']) => 'culled',
            default => null,
        };
    }

    private function birthType(string $v): ?string
    {
        $v = Str::lower(trim($v));

        return match (true) {
            is_numeric($v) && (int) $v >= 1 && (int) $v <= 9 => str_pad((string) (int) $v, 2, '0', STR_PAD_LEFT),
            Str::contains($v, ['single', 'enkel', 'een']) || $v === 's' => '01',
            Str::contains($v, ['twin', 'tweel', 'twee']) || $v === 't' => '02',
            Str::contains($v, ['trip', 'driel', 'drie']) => '03',
            Str::contains($v, ['quad', 'vierl']) => '04',
            default => null,
        };
    }

    private function species(string $v): ?string
    {
        $v = Str::lower($v);

        return match (true) {
            Str::contains($v, ['cattle', 'bees', 'cow', 'bovine', 'bull', 'koei', 'beef', 'nguni', 'bonsmara', 'angus', 'brahman', 'simmental', 'hereford', 'beefmaster', 'drakensberger', 'afrikaner', 'sussex', 'charolais', 'limousin', 'wagyu', 'holstein', 'jersey', 'friesian', 'tuli', 'braford', 'brangus', 'santa gertrudis', 'boran', 'pinzgauer', 'simbra']) => 'cattle',
            Str::contains($v, ['goat', 'bok', 'caprine', 'boer goat', 'kalahari', 'savanna', 'angora', 'saanen', 'kiko']) => 'goat',
            Str::contains($v, ['sheep', 'skaap', 'skape', 'ovine', 'ewe', 'ram', 'dorper', 'merino', 'meatmaster', 'dohne', 'damara', 'van rooy', 'ile de france', 'suffolk', 'dormer', 'south african mutton', 'samm', 'persian', 'karakul', 'afrino']) => 'sheep',
            default => null,
        };
    }

    public function kg(string $v): ?float
    {
        $n = str_replace([' ', ','], ['', '.'], Str::lower(preg_replace('/\s*(kg|kgs|kilo\w*)$/i', '', trim($v))));
        if (! is_numeric($n) || (float) $n <= 0 || (float) $n >= 2000) {
            return null;
        }

        return round((float) $n, 1);
    }

    /**
     * Farm dates in whatever shape Excel or a person wrote them. Day first
     * (South African), unless that can't be right. Returns Y-m-d (with the
     * time when $withTime and one was given), or null.
     */
    public function date(string $v, bool $withTime = false): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        if (is_numeric($v) && $v > 20000 && $v < 70000) {
            $d = Carbon::createFromTimestampUTC((int) round(((float) $v - 25569) * 86400));

            return $d->toDateString();
        }
        $time = '';
        if (preg_match('/[ T](\d{1,2}):(\d{2})(?::\d{2})?\s*(am|pm)?/i', $v, $t)) {
            $h = (int) $t[1] % 12 + (isset($t[3]) && strtolower($t[3]) === 'pm' ? 12 : 0) + (! isset($t[3]) && (int) $t[1] === 12 ? 12 : 0);
            $time = sprintf(' %02d:%02d', $h % 24, (int) $t[2]);
        }
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $v, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2,4})/', $v, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if ($mo > 12 && $d <= 12) {
                [$d, $mo] = [$mo, $d];
            }
            $y += $y < 100 ? ($y > (int) now()->format('y') + 1 ? 1900 : 2000) : 0;
        } elseif (preg_match('/[a-z]{3}/i', $v)) {
            $af = ['januarie' => 'january', 'februarie' => 'february', 'maart' => 'march', 'mei' => 'may', 'junie' => 'june', 'julie' => 'july',
                'augustus' => 'august', 'okt' => 'oct', 'oktober' => 'october', 'desember' => 'december', 'des' => 'dec'];
            try {
                $c = Carbon::parse(str_ireplace(array_keys($af), array_values($af), $v));

                return $c->year >= 1950 && $c->year <= (int) now()->year + 1 ? $c->toDateString() : null;
            } catch (\Throwable) {
                return null;
            }
        } else {
            return null;
        }
        if (! checkdate($mo, $d, $y) || $y < 1950 || $y > (int) now()->year + 1) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $y, $mo, $d).($withTime ? $time : '');
    }

    /** A date written inside a column header, e.g. "Weight 01/03/2026" or "Mar 2026". */
    private function dateIn(string $header): ?string
    {
        if (preg_match('/(\d{1,2}[-\/.]\d{1,2}[-\/.]\d{2,4}|\d{4}[-\/.]\d{1,2}[-\/.]\d{1,2})/', $header, $m)) {
            return $this->date($m[1]);
        }
        if (preg_match('/\b(\d{1,2}\s+)?(jan|feb|mar|apr|may|mei|jun|jul|aug|sep|oct|okt|nov|dec|des)[a-z]*\.?\s+(\d{4})\b/i', $header, $m)) {
            return $this->date(trim(($m[1] ?: '1 ').$m[2].' '.$m[3]));
        }

        return null;
    }

    /** No date for a typed weight: count from the birth date, else today. */
    private function weighDate(string $type, ?string $birth): string
    {
        if ($birth && isset(self::WEIGH_DAYS[$type])) {
            return Carbon::parse($birth)->addDays(self::WEIGH_DAYS[$type])->min(now())->toDateString();
        }

        return now()->toDateString();
    }
}
