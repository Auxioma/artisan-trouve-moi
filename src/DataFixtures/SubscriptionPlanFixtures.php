<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Billing\SubscriptionPlan
;

final class SubscriptionPlanFixtures extends AbstractFrenchFixture
{
    protected const ENTITY_CLASS = SubscriptionPlan::class;
    protected const DEPENDENCIES = [];
}
