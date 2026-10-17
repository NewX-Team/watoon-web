<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserTier;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property Carbon|null $subscription_expires_at
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Subscription> $subscriptions
 */
#[Fillable(['name', 'username', 'email', 'bio', 'phone', 'password', 'role', 'tier', 'subscription_expires_at'])]
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
            'subscription_expires_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
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

    /**
     * Determine if user has an active paid subscription.
     */
    public function hasActiveSubscription(): bool
    {
        if (($this->tier ?? UserTier::Free) === UserTier::Free) {
            return false;
        }

        if ($this->subscription_expires_at === null) {
            return true;
        }

        return $this->subscription_expires_at->isFuture();
    }

    /**
     * Get effective tier considering expiration.
     */
    public function effectiveTier(): UserTier
    {
        return $this->hasActiveSubscription() ? ($this->tier ?? UserTier::Free) : UserTier::Free;
    }

    public function canAccessTier(UserTier|string $tier): bool
    {
        return $this->effectiveTier()->canAccess($tier);
    }

    /**
     * Check if user is allowed to subscribe/switch to the target tier.
     *
     * @return array{allowed: bool, reason: string|null}
     */
    public function canSubscribeTo(UserTier $targetTier): array
    {
        // If target is Free: cannot downgrade while Pro or VIP is actively running
        if ($targetTier === UserTier::Free) {
            if ($this->hasActiveSubscription()) {
                $date = $this->subscription_expires_at?->format('d M Y') ?? 'aktif';

                return [
                    'allowed' => false,
                    'reason' => "Anda sedang memiliki langganan {$this->tier->label()} aktif hingga {$date}. Tidak dapat berpindah ke Free hingga masa langganan berakhir.",
                ];
            }

            return ['allowed' => true, 'reason' => null];
        }

        // If user is currently VIP with active subscription
        if (($this->tier ?? UserTier::Free) === UserTier::Vip && $this->hasActiveSubscription()) {
            if ($targetTier === UserTier::Pro) {
                $date = $this->subscription_expires_at?->format('d M Y') ?? 'aktif';

                return [
                    'allowed' => false,
                    'reason' => "Anda sedang memiliki langganan VIP aktif hingga {$date}. Anda tidak dapat berpindah ke kasta Pro hingga masa langganan VIP berakhir.",
                ];
            }

            // Extending VIP is allowed
            return ['allowed' => true, 'reason' => null];
        }

        // If user is currently Pro with active subscription:
        // - upgrading to VIP is allowed at normal price
        // - extending Pro is allowed
        // - downgrading to Free is handled above
        return ['allowed' => true, 'reason' => null];
    }
}
