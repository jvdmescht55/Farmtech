<?php

namespace App\Models;

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
}
