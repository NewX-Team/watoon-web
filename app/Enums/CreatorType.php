<?php

namespace App\Enums;

enum CreatorType: string
{
    case Film = 'film';
    case Novel = 'novel';

    public function label(): string
    {
        return match ($this) {
            self::Film => 'Kreator Film',
            self::Novel => 'Kreator Novel',
        };
    }
}
