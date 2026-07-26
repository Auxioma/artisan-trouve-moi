<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\CommercialPartnerProfile
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class CommercialPartnerProfileFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = CommercialPartnerProfile::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
