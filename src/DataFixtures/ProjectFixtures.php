<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Projects\Project
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ProjectFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Project::class;
    protected const DEPENDENCIES = [QuoteFixtures::class, UserFixtures::class, ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
