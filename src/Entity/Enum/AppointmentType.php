<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum AppointmentType: string
{
    case TECHNICAL_VISIT = 'technical_visit';
    case WORKS = 'works';
    case FOLLOW_UP = 'follow_up';
    case DELIVERY = 'delivery';

    public function label(): string
    {
        return match ($this) {
            self::TECHNICAL_VISIT => 'Visite technique',
            self::WORKS => 'Travaux',
            self::FOLLOW_UP => 'Suivi',
            self::DELIVERY => 'Livraison',
        };
    }
}
