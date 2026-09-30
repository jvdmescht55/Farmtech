<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Someone who registered interest in a device or sent a message from the public site. */
class Lead extends Model
{
    protected $fillable = ['type', 'name', 'email', 'phone', 'farm_name', 'herd_size', 'province', 'interest', 'message', 'handled_at'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }
}
