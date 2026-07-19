<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum QuoteStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SENT => 'Envoyé',
            self::ACCEPTED => 'Accepté',
            self::REFUSED => 'Refusé',
            self::EXPIRED => 'Expiré',
        };
    }
}
