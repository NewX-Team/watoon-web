<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
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

    /**
     * Get detailed subscription duration statistics.
     *
     * @return array{
     *     has_subscription: bool,
     *     tier: string,
     *     tier_label: string,
     *     remaining_days: int,
     *     remaining_hours: int,
     *     remaining_seconds: int,
     *     total_days: int,
     *     percentage: int,
     *     is_expiring_soon: bool,
     *     formatted_remaining: string,
     *     expires_at: string|null,
     *     expires_formatted: string|null
     * }
     */
    public function subscriptionStats(): array
    {
        if (! $this->hasActiveSubscription()) {
            return [
                'has_subscription' => false,
                'tier' => 'free',
                'tier_label' => 'Free',
                'remaining_days' => 0,
                'remaining_hours' => 0,
                'remaining_seconds' => 0,
                'total_days' => 0,
                'percentage' => 0,
                'is_expiring_soon' => false,
                'formatted_remaining' => 'Tidak aktif',
                'expires_at' => null,
                'expires_formatted' => null,
            ];
        }

        $expiresAt = $this->subscription_expires_at;

        if ($expiresAt === null) {
            return [
                'has_subscription' => true,
                'tier' => $this->tier->value,
                'tier_label' => $this->tier->label(),
                'remaining_days' => 9999,
                'remaining_hours' => 9999 * 24,
                'remaining_seconds' => 9999 * 86400,
                'total_days' => 9999,
                'percentage' => 100,
                'is_expiring_soon' => false,
                'formatted_remaining' => 'Akses Permanen',
                'expires_at' => null,
                'expires_formatted' => 'Akses Permanen',
            ];
        }

        $now = now();
        $remainingSeconds = max(0, $now->diffInSeconds($expiresAt, false));
        $remainingDays = (int) ceil($remainingSeconds / 86400);
        $remainingHours = (int) ceil($remainingSeconds / 3600);

        $latestSub = $this->subscriptions()
            ->where('status', SubscriptionStatus::Settlement)
            ->latest('expires_at')
            ->first();

        $totalDays = $latestSub?->billing_cycle?->durationDays() ?? 30;
        if ($totalDays <= 0) {
            $totalDays = 30;
        }

        $effectiveTotalDays = max($totalDays, $remainingDays);
        $totalSeconds = $effectiveTotalDays * 86400;

        $percentage = (int) min(100, max(0, round(($remainingSeconds / $totalSeconds) * 100)));
        $isExpiringSoon = $remainingSeconds <= (3 * 86400) && $remainingSeconds > 0;

        if ($remainingDays > 1) {
            $formattedRemaining = "{$remainingDays} hari tersisa";
        } elseif ($remainingHours > 1) {
            $formattedRemaining = "{$remainingHours} jam tersisa";
        } elseif ($remainingSeconds > 0) {
            $minutes = max(1, (int) ceil($remainingSeconds / 60));
            $formattedRemaining = "{$minutes} menit tersisa";
        } else {
            $formattedRemaining = 'Kedaluwarsa';
        }

        return [
            'has_subscription' => true,
            'tier' => $this->tier->value,
            'tier_label' => $this->tier->label(),
            'remaining_days' => $remainingDays,
            'remaining_hours' => $remainingHours,
            'remaining_seconds' => $remainingSeconds,
            'total_days' => $effectiveTotalDays,
            'percentage' => $percentage,
            'is_expiring_soon' => $isExpiringSoon,
            'formatted_remaining' => $formattedRemaining,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_formatted' => $expiresAt->translatedFormat('d F Y, H:i').' WIB',
        ];
    }
}
