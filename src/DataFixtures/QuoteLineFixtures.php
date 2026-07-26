<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Quotes\QuoteLine
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class QuoteLineFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = QuoteLine::class;
    protected const DEPENDENCIES = [QuoteFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
