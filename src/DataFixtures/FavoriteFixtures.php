<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Favorites\Favorite
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class FavoriteFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Favorite::class;
    protected const DEPENDENCIES = [UserFixtures::class, ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
