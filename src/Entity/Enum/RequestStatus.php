<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum RequestStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case AWARDED = 'awarded';
    case COMPLETED = 'completed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publiée',
            self::AWARDED => 'Attribuée',
            self::COMPLETED => 'Terminée',
            self::EXPIRED => 'Expirée',
            self::CANCELLED => 'Annulée',
        };
    }
}
