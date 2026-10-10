<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = ['user_id', 'plan', 'trial_ends_at', 'paid_until', 'reminded_for', 'notes'];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime', 'paid_until' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The last day of full access, trial or paid, whichever is later. */
    public function accessUntil(): ?\Carbon\Carbon
    {
        $dates = array_filter([$this->trial_ends_at, $this->paid_until]);

        return $dates ? max($dates) : null;
    }
}
