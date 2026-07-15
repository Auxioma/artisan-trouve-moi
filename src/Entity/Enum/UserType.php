<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum UserType: string
{
    case CUSTOMER = 'customer';
    case ARTISAN = 'artisan';
    case COMMERCIAL_PARTNER = 'commercial_partner';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Utilisateur particulier',
            self::ARTISAN => 'Artisan',
            self::COMMERCIAL_PARTNER => 'Partenaire commercial',
        };
    }

    public function securityRole(): string
    {
        return match ($this) {
            self::CUSTOMER => 'ROLE_USER',
            self::ARTISAN => 'ROLE_ARTISAN',
            self::COMMERCIAL_PARTNER => 'ROLE_COMMERCIAL_PARTNER',
        };
    }
}
