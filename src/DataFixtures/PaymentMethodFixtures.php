<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Billing\PaymentMethod
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class PaymentMethodFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = PaymentMethod::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
