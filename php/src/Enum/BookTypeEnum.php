<?php

declare(strict_types=1);

namespace App\Enum;

enum BookTypeEnum: string
{
    case FICTION = 'fiction';
    case NON_FICTION = 'non-fiction';
    case RARE_EDITIONS = 'rare-editions';
    case DRAMES = 'drame';

    public function label(): string
    {
        return match ($this) {
            self::FICTION => 'Fiction',
            self::NON_FICTION => 'Non-Fiction',
            self::RARE_EDITIONS => 'Rare Editions',
            self::DRAMES => 'Drama',
        };
    }
}
