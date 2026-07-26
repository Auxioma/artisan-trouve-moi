<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Billing\Invoice
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class InvoiceFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Invoice::class;
    protected const DEPENDENCIES = [SubscriptionFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
