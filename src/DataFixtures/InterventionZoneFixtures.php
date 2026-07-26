<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Geo\InterventionZone
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class InterventionZoneFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = InterventionZone::class;
    protected const DEPENDENCIES = [ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
