<?php

namespace App\Http\Controllers\Rfid\Concerns;

use Illuminate\Database\Eloquent\Model;

trait OwnsRecords
{
    /** Every herd record belongs to one farm account; anything else is a 404, not a 403, so IDs don't leak. */
    protected function own(Model $record): void
    {
        abort_unless((int) $record->user_id === (int) auth()->id(), 404);
    }
}
