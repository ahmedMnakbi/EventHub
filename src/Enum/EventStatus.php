<?php

namespace App\Enum;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Published => 'Publié',
            self::Cancelled => 'Annulé',
        };
    }

    /** Bootstrap color used for the status badge. */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Published => 'success',
            self::Cancelled => 'danger',
        };
    }
}
