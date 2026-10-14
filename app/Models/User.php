<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserTier;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property string|null $bio
 * @property string|null $phone
 * @property Role $role
 * @property UserTier $tier
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'bio', 'phone', 'password', 'role', 'tier'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'tier' => UserTier::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isUser(): bool
    {
        return $this->role === Role::User;
    }

    public function isKreator(): bool
    {
        return $this->role === Role::Kreator;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function canAccessTier(UserTier|string $tier): bool
    {
        return ($this->tier ?? UserTier::Free)->canAccess($tier);
    }
}
