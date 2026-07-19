<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum AppointmentStatus: string
{
    case PROPOSED = 'proposed';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PROPOSED => 'Proposé',
            self::CONFIRMED => 'Confirmé',
            self::COMPLETED => 'Réalisé',
            self::CANCELLED => 'Annulé',
        };
    }
}
