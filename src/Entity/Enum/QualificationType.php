<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum QualificationType: string
{
    case CAP = 'cap';
    case BEP = 'bep';
    case BP = 'bp';
    case BAC_PRO = 'bac_pro';
    case BTS = 'bts';
    case PROFESSIONAL_TITLE = 'professional_title';
    case RNCP_CERTIFICATION = 'rncp_certification';
    case PROFESSIONAL_EXPERIENCE = 'professional_experience';
    case EUROPEAN_EQUIVALENCE = 'european_equivalence';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CAP => 'Certificat d’aptitude professionnelle',
            self::BEP => 'Brevet d’études professionnelles',
            self::BP => 'Brevet professionnel',
            self::BAC_PRO => 'Baccalauréat professionnel',
            self::BTS => 'Brevet de technicien supérieur',
            self::PROFESSIONAL_TITLE => 'Titre professionnel',
            self::RNCP_CERTIFICATION => 'Certification RNCP',
            self::PROFESSIONAL_EXPERIENCE => 'Expérience professionnelle',
            self::EUROPEAN_EQUIVALENCE => 'Équivalence européenne',
            self::OTHER => 'Autre qualification',
        };
    }
}
