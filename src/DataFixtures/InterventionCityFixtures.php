<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Geo\InterventionCity
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class InterventionCityFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = InterventionCity::class;
    protected const DEPENDENCIES = [InterventionZoneFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
