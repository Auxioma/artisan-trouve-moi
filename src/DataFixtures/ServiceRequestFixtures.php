<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Requests\ServiceRequest
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ServiceRequestFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ServiceRequest::class;
    protected const DEPENDENCIES = [UserFixtures::class, CategoryFixtures::class, ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
