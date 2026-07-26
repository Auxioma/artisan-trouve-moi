<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Requests\RequestPhoto
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class RequestPhotoFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = RequestPhoto::class;
    protected const DEPENDENCIES = [ServiceRequestFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
