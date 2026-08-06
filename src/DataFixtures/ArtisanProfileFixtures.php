<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\ArtisanProfile
;
use App\Entity\Users\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class ArtisanProfileFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ArtisanProfile::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }

    protected function afterPopulate(object $entity, int $index): void
    {
        \assert($entity instanceof ArtisanProfile);

        /** @var User $user */
        $user = $this->getReference($this->reference(User::class, 2 + 3 * ($index - 1)), User::class);
        $entity->setUser($user);
        $user->setArtisanProfile($entity);
    }
}
