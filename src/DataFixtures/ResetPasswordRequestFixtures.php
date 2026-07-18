<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\ResetPasswordRequest;
use App\Entity\Users\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class ResetPasswordRequestFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for (
            $index = 1;
            $index <= FixtureReferences::RESET_PASSWORD_REQUEST_COUNT;
            ++$index
        ) {
            $request = new ResetPasswordRequest(
                $this->getReference(
                    FixtureReferences::customerUser($index),
                    User::class
                ),
                new \DateTimeImmutable(
                    sprintf('2026-07-%02d 18:00:00', 18 + $index)
                ),
                sprintf('reset-customer-%03d', $index),
                hash('sha256', sprintf('reset-token-customer-%03d', $index))
            );

            $manager->persist($request);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
