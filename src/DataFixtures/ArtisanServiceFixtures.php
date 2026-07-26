<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Catalog\ArtisanService
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ArtisanServiceFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ArtisanService::class;
    protected const DEPENDENCIES = [ArtisanProfileFixtures::class, CategoryFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
