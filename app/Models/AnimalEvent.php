<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalEvent extends Model
{
    protected $fillable = ['user_id', 'animal_id', 'type', 'date', 'product', 'dose', 'withdrawal_until', 'mate_id', 'count', 'result', 'notes'];

    protected function casts(): array
    {
        return ['date' => 'date', 'withdrawal_until' => 'date'];
    }

    protected static function booted(): void
    {
        // Sale / death / cull events keep the animal's status in step.
        static::created(function (AnimalEvent $e) {
            $status = config("herd.event_types.{$e->type}.status");
            if ($status && $e->animal) {
                $e->animal->update(['status' => $status, 'status_date' => $e->date]);
            }
        });

        // Undo: removing the entry that set the status puts the animal back.
        static::deleted(function (AnimalEvent $e) {
            $status = config("herd.event_types.{$e->type}.status");
            if ($status && $e->animal && $e->animal->status === $status) {
                $other = static::where('animal_id', $e->animal_id)->whereIn('type', collect(config('herd.event_types'))->filter(fn ($t) => isset($t['status']))->keys())->latest('date')->first();
                $e->animal->update($other
                    ? ['status' => config("herd.event_types.{$other->type}.status"), 'status_date' => $other->date]
                    : ['status' => 'active', 'status_date' => null]);
            }
        });
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function mate(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'mate_id');
    }

    public function label(): string
    {
        return config("herd.event_types.{$this->type}.label", $this->type);
    }
}
