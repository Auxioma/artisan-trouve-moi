<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Catalog\ArtisanPhoto
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ArtisanPhotoFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ArtisanPhoto::class;
    protected const DEPENDENCIES = [ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
