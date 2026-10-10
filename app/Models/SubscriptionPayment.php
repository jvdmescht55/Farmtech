<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = ['user_id', 'reference', 'plan', 'amount_cents', 'method', 'status', 'period_start', 'period_end', 'gateway_reference', 'paid_at'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'paid_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rand(): string
    {
        return 'R'.number_format($this->amount_cents / 100, $this->amount_cents % 100 ? 2 : 0, '.', ' ');
    }
}
