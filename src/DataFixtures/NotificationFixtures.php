<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Notifications\Notification
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class NotificationFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Notification::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
