<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Billing\Subscription
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class SubscriptionFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Subscription::class;
    protected const DEPENDENCIES = [ArtisanProfileFixtures::class, SubscriptionPlanFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
