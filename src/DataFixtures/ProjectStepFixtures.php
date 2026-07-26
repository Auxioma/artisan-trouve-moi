<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Projects\ProjectStep
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ProjectStepFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ProjectStep::class;
    protected const DEPENDENCIES = [ProjectFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
