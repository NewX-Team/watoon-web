<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Bulanan',
            self::Yearly => 'Tahunan',
        };
    }

    public function durationDays(): int
    {
        return match ($this) {
            self::Monthly => 30,
            self::Yearly => 365,
        };
    }

    public static function priceFor(UserTier $tier, self $cycle): int
    {
        return match ($tier) {
            UserTier::Pro => match ($cycle) {
                self::Monthly => 80000,
                self::Yearly => 768000,
            },
            UserTier::Vip => match ($cycle) {
                self::Monthly => 150000,
                self::Yearly => 1440000,
            },
            default => 0,
        };
    }
}
