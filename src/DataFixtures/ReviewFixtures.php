<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Reviews\Review
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ReviewFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Review::class;
    protected const DEPENDENCIES = [UserFixtures::class, ArtisanProfileFixtures::class, ProjectFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
