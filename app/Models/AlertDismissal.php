<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertDismissal extends Model
{
    protected $fillable = ['user_id', 'alert_key', 'until'];

    protected function casts(): array
    {
        return ['until' => 'datetime'];
    }
}
