<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum PriceUnit: string
{
    case FLAT = 'flat';
    case PER_HOUR = 'per_hour';
    case PER_SQM = 'per_sqm';
    case PER_LINEAR_METER = 'per_linear_meter';
    case PER_UNIT = 'per_unit';

    public function label(): string
    {
        return match ($this) {
            self::FLAT => 'Forfait',
            self::PER_HOUR => 'Par heure',
            self::PER_SQM => 'Par m²',
            self::PER_LINEAR_METER => 'Par mètre linéaire',
            self::PER_UNIT => 'Par unité',
        };
    }
}
