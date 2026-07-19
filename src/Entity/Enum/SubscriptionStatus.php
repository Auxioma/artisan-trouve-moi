<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum SubscriptionStatus: string
{
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case PAST_DUE = 'past_due';
    case CANCELLED = 'cancelled';
    case ENDED = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::TRIALING => 'Essai en cours',
            self::ACTIVE => 'Actif',
            self::PAST_DUE => 'Paiement en retard',
            self::CANCELLED => 'Résilié',
            self::ENDED => 'Terminé',
        };
    }
}
