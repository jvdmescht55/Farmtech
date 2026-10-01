<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'farm_name',
        'breeder_number',
        'stud_prefix',
        'farm_address',
        'breed',
        'species',
        'phone',
        'terms_accepted_at',
        'tours_seen',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'tours_seen' => 'array',
        ];
    }

    /** Full-access role — Settings, Users, Sourcing, Products, plus everything Staff can do. */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->is_active;
    }

    /** Anyone allowed into /admin at all — both roles, as long as the account is active. */
    public function canAccessAdminPanel(): bool
    {
        return $this->is_active && in_array($this->role, ['admin', 'staff'], true);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /** Whether the account holds an active licence for a software module (e.g. 'rfid'). Admins can open every module. */
    public function hasModule(string $module): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->isAdmin() || config("herd.modules.{$module}.open")) {
            return true;
        }

        return $this->licenses()->where('module', $module)->whereNull('revoked_at')->exists();
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function readers(): HasMany
    {
        return $this->hasMany(Reader::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    public function catalogues(): HasMany
    {
        return $this->hasMany(SaleCatalogue::class);
    }

    /** The line printed above a breeder's lots, e.g. "0696358  DIE BULT MEATMASTER STOET, POSBUS 42, KENHARDT, 8900". */
    public function breederLine(): string
    {
        return trim(($this->breeder_number ? $this->breeder_number.'  ' : '').mb_strtoupper(collect([$this->farm_name, $this->farm_address])->filter()->implode(', ')));
    }

    public function hasSeenTour(string $key): bool
    {
        return in_array($key, $this->tours_seen ?? [], true);
    }
}
