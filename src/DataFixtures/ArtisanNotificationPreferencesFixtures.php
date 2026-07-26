<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\ArtisanNotificationPreferences;
use App\Entity\Users\ArtisanProfile;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ArtisanNotificationPreferencesFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ArtisanNotificationPreferences::class;
    protected const DEPENDENCIES = [ArtisanProfileFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }

    public function load(ObjectManager $manager): void
    {
        for ($index = 1; $index <= self::RECORDS_PER_ENTITY; ++$index) {
            $profile = $this->getReference($this->reference(ArtisanProfile::class, $index), ArtisanProfile::class);
            $preferences = $profile->getNotificationPreferences();
            if (null === $preferences) {
                throw new \LogicException('Un profil artisan doit avoir ses preferences.');
            }
            $this->addReference($this->reference(self::ENTITY_CLASS, $index), $preferences);
        }
    }
}
