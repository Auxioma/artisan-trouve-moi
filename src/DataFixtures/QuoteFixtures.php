<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Quotes\Quote
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class QuoteFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Quote::class;
    protected const DEPENDENCIES = [ServiceRequestFixtures::class, ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
