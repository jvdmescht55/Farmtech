<?php

namespace App\Models;

use App\Services\Herd\PedigreeTier;
use App\Services\Herd\TierResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Animal extends Model
{
    public const STATUSES = ['active' => 'Aktief', 'sold' => 'Verkoop', 'dead' => 'Dood', 'culled' => 'Uitskot'];

    protected $fillable = [
        'user_id', 'in_herd', 'species', 'eid', 'visual_id', 'name', 'sex', 'breed', 'birth_date', 'birth_type',
        'registered', 'is_commercial', 'tier', 'gen_score', 'sire_id', 'dam_id', 'status', 'status_date',
        'ebvs', 'dam_record', 'notes', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'in_herd' => 'boolean',
            'registered' => 'boolean',
            'is_commercial' => 'boolean',
            'birth_date' => 'date',
            'status_date' => 'date',
            'ebvs' => 'array',
            'dam_record' => 'array',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'dam_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class)->orderByDesc('scanned_at');
    }

    public function offspring(): Builder
    {
        return static::query()->where(fn ($q) => $q->where('sire_id', $this->id)->orWhere('dam_id', $this->id));
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnimalEvent::class)->orderByDesc('date');
    }

    /** Species biology from config('herd.species'). */
    public function bio(?string $key = null): mixed
    {
        $bio = config('herd.species.'.($this->species ?: 'sheep')) ?? config('herd.species.sheep');

        return $key ? ($bio[$key] ?? null) : $bio;
    }

    public function scopeHerd(Builder $q): Builder
    {
        return $q->where('in_herd', true);
    }

    public static function normalizeVisualId(?string $id): ?string
    {
        $id = trim(preg_replace('/\s+/', ' ', (string) $id));

        return $id === '' ? null : Str::upper($id);
    }

    public static function looksCommercial(string $visualId): bool
    {
        foreach (config('herd.commercial_prefixes') as $prefix) {
            if (Str::startsWith(Str::upper($visualId), Str::upper($prefix).' ') || Str::upper($visualId) === Str::upper($prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Find an animal by visual ID for this farm, or create a pedigree-only reference row. */
    public static function findOrReference(int $userId, ?string $visualId): ?self
    {
        $visualId = static::normalizeVisualId($visualId);
        if (! $visualId) {
            return null;
        }

        return static::firstOrCreate(
            ['user_id' => $userId, 'visual_id' => $visualId],
            [
                'in_herd' => false,
                'is_commercial' => static::looksCommercial($visualId),
                'registered' => ! static::looksCommercial($visualId),
            ],
        );
    }

    public function tierResult(): TierResult
    {
        return app(PedigreeTier::class)->resolve($this);
    }

    public function ebv(string $trait): ?array
    {
        $e = $this->ebvs[$trait] ?? null;

        return is_array($e) && ($e['v'] ?? '') !== '' && ($e['v'] ?? null) !== null ? $e : null;
    }

    public function latestWeight(): ?Scan
    {
        return $this->scans()->whereNotNull('weight_kg')->first();
    }

    public function ageLabel(): ?string
    {
        if (! $this->birth_date) {
            return null;
        }
        $months = (int) $this->birth_date->diffInMonths(now());

        return $months < 24 ? $months.' mo' : intdiv($months, 12).' yr '.($months % 12).' mo';
    }

    public function sexLabel(): string
    {
        return match ($this->sex) {
            'M' => $this->bio('male'),
            'F' => $this->bio('female'),
            default => '—',
        };
    }
}
