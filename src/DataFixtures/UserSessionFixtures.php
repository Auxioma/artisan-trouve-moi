<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\UserSession
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class UserSessionFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = UserSession::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
