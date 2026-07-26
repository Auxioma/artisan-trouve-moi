<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Scheduling\Appointment
;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class AppointmentFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = Appointment::class;
    protected const DEPENDENCIES = [ArtisanProfileFixtures::class, UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }
}
