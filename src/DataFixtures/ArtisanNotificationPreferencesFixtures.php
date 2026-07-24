<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\ArtisanNotificationPreferences;
use App\Entity\Billing\SubscriptionPlan;
use App\Entity\Users\UserProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Users\ArtisanProfile;

final class ArtisanNotificationPreferencesFixtures extends Fixture implements \Doctrine\Common\DataFixtures\DependentFixtureInterface
{
    private const ENTITY_CLASS = ArtisanNotificationPreferences::class;
    private const RECORDS_PER_ENTITY = 1000;
    public function getDependencies(): array
    {
        return [ArtisanProfileFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        for ($index = 1; $index <= self::RECORDS_PER_ENTITY; ++$index) {
            /** @var ArtisanProfile $profile */
            $profile = $this->getReference($this->reference(ArtisanProfile::class, $index), ArtisanProfile::class);
            $preferences = $profile->getNotificationPreferences();
            if (null === $preferences) {
                throw new \LogicException('Un profil artisan doit avoir ses preferences.');
            }
            $this->addReference($this->reference(self::ENTITY_CLASS, $index), $preferences);
        }
    }

    private function reference(string $class, int $index): string
    {
        return sprintf('%s.%06d', (new \ReflectionClass($class))->getShortName(), $index);
    }
}

