<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ISSUED => 'Émise',
            self::PAID => 'Payée',
            self::CANCELLED => 'Annulée',
        };
    }
}
