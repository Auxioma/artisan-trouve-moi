<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\ResetPasswordRequest;
use App\Entity\Users\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ResetPasswordRequestFixtures extends AbstractFrenchFixture implements DependentFixtureInterface
{
    protected const ENTITY_CLASS = ResetPasswordRequest::class;
    protected const DEPENDENCIES = [UserFixtures::class];

    public function getDependencies(): array
    {
        return self::DEPENDENCIES;
    }

    protected function createEntity(ObjectManager $manager, int $index): object
    {
        $user = $this->getReference($this->reference(User::class, $index), User::class);

        return new ResetPasswordRequest(
            $user,
            new \DateTimeImmutable('+2 days'),
            sprintf('fr-%06d', $index),
            hash('sha256', sprintf('jeton-francais-%d', $index)),
        );
    }
}
