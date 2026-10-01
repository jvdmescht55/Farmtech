<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    public const KINDS = [
        'device' => 'A new device',
        'software' => 'Something in the software',
        'custom_build' => 'Build something just for my farm',
    ];

    public const STATUSES = ['new' => 'New', 'planned' => 'Planned', 'building' => 'Building', 'done' => 'Done', 'declined' => 'Not now'];

    protected $fillable = ['user_id', 'kind', 'title', 'details', 'name', 'email', 'status', 'reply'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
