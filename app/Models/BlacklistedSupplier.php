<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlacklistedSupplier extends Model
{
    protected $fillable = ['supplier_name', 'reason', 'blacklisted_by'];

    public static function isBlacklisted(string $supplierName): bool
    {
        return static::where('supplier_name', $supplierName)->exists();
    }
}
