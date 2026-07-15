<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum VerificationStatus: string
{
    case NOT_SUBMITTED = 'not_submitted';
    case VERIFIED = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::NOT_SUBMITTED => 'Non soumis',
            self::VERIFIED => 'Verifie',
        };
    }
}
