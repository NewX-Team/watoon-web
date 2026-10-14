<?php

namespace App\Enums;

enum UserTier: string
{
    case Free = 'free';
    case Pro = 'pro';
    case Vip = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Pro => 'Pro',
            self::Vip => 'VIP',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Free => 1,
            self::Pro => 2,
            self::Vip => 3,
        };
    }

    /**
     * Determine if this tier can access equal or lower target tier.
     */
    public function canAccess(self|string $targetTier): bool
    {
        $target = is_string($targetTier)
            ? (self::tryFrom(strtolower($targetTier)) ?? self::Free)
            : $targetTier;

        return $this->rank() >= $target->rank();
    }
}
