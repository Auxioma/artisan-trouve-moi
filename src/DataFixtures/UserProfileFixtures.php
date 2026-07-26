<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\User;
use App\Entity\Users\UserProfile;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class UserProfileFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = UserProfile::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }

    protected function afterPopulate(object $entity, int $index): void
    {
        \assert($entity instanceof UserProfile);

        /** @var User $user */
        $user = $this->getReference($this->reference(User::class, $index), User::class);
        $entity->setUser($user);
        $user->setUserProfile($entity);
    }
}
