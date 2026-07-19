<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum VerificationDocumentType: string
{
    case IDENTITY = 'identity';
    case KBIS = 'kbis';
    case RNE_EXTRACT = 'rne_extract';
    case DECENNIAL_INSURANCE = 'decennial_insurance';
    case LIABILITY_INSURANCE = 'liability_insurance';
    case QUALIFICATION = 'qualification';

    public function label(): string
    {
        return match ($this) {
            self::IDENTITY => 'Pièce d’identité',
            self::KBIS => 'Extrait Kbis',
            self::RNE_EXTRACT => 'Extrait RNE',
            self::DECENNIAL_INSURANCE => 'Assurance décennale',
            self::LIABILITY_INSURANCE => 'RC professionnelle',
            self::QUALIFICATION => 'Qualification',
        };
    }
}
