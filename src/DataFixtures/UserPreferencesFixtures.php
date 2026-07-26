<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\UserPreferences
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class UserPreferencesFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = UserPreferences::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
