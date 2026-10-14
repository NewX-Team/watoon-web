<?php

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Kreator = 'kreator';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Kreator => 'Kreator',
            self::Admin => 'Admin',
        };
    }
}
