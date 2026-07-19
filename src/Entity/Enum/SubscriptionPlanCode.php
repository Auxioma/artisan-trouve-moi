<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum SubscriptionPlanCode: string
{
    case ESSENTIEL = 'essentiel';
    case PREMIUM = 'premium';
    case EXCELLENCE = 'excellence';

    public function label(): string
    {
        return match ($this) {
            self::ESSENTIEL => 'Essentiel',
            self::PREMIUM => 'Premium',
            self::EXCELLENCE => 'Excellence',
        };
    }
}
