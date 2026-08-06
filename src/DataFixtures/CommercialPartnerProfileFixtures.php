<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\CommercialPartnerProfile
;
use App\Entity\Users\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

final class CommercialPartnerProfileFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = CommercialPartnerProfile::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }

    protected function afterPopulate(object $entity, int $index): void
    {
        \assert($entity instanceof CommercialPartnerProfile);

        /** @var User $user */
        $userIndex = 1 === $index ? 4 : 3 * $index;
        $user = $this->getReference($this->reference(User::class, $userIndex), User::class);
        $entity->setUser($user);
        $user->setCommercialPartnerProfile($entity);
    }
}
