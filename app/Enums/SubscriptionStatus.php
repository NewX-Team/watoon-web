<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Settlement = 'settlement';
    case Capture = 'capture';
    case Deny = 'deny';
    case Cancel = 'cancel';
    case Expire = 'expire';
    case Failure = 'failure';

    public function isPaid(): bool
    {
        return in_array($this, [self::Settlement, self::Capture], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Settlement, self::Capture => 'Berhasil Dibayar',
            self::Deny => 'Ditolak',
            self::Cancel => 'Dibatalkan',
            self::Expire => 'Kedaluwarsa',
            self::Failure => 'Gagal',
        };
    }
}
