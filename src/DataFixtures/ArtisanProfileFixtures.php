<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\ArtisanProfile
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ArtisanProfileFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ArtisanProfile::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
