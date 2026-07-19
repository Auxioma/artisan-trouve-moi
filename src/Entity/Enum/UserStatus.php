<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case BLOCKED = 'blocked';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'Actif',
            self::SUSPENDED => 'Suspendu',
            self::BLOCKED => 'Bloqué',
            self::DELETED => 'Supprimé',
        };
    }

    public function canLogin(): bool
    {
        return self::ACTIVE === $this;
    }
}
