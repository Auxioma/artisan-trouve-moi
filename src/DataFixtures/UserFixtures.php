<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Enum\UserStatus;
use App\Entity\Enum\UserType;
use App\Entity\Users\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public const DEFAULT_PASSWORD = 'ChangeMe123!';

    private const TERMS_VERSION = '2026.07';
    private const PRIVACY_VERSION = '2026.07';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        FixtureReferences::assertLimits();

        $faker = Factory::create('fr_FR');
        $faker->seed(FixtureReferences::USER_FIXTURES_SEED);

        $this->loadCustomers($manager, $faker);
        $this->loadArtisans($manager, $faker);
        $this->loadCommercialPartners($manager, $faker);
        $this->loadTestAccounts($manager);

        $manager->flush();
    }

    private function loadCustomers(
        ObjectManager $manager,
        Generator $faker
    ): void {
        for ($index = 1; $index <= FixtureReferences::CUSTOMER_USER_COUNT; ++$index) {
            $isActive = $index <= 20;
            $createdAt = $this->randomDateBetween(
                $faker,
                '2025-10-01 08:00:00',
                '2026-07-05 18:00:00'
            );
            $updatedAt = $createdAt->modify(sprintf('+%d days', ($index % 28) + 1));
            $lastLoginAt = $isActive
                ? $updatedAt->modify(sprintf('+%d hours', $index % 8))
                : null;

            $this->persistUser(
                $manager,
                FixtureReferences::customerUser($index),
                $this->createUser(
                    email: sprintf('customer%02d@example.test', $index),
                    firstName: $faker->firstName(),
                    lastName: $faker->lastName(),
                    type: UserType::CUSTOMER,
                    status: $isActive
                        ? UserStatus::ACTIVE
                        : UserStatus::PENDING,
                    isVerified: $isActive,
                    phoneNumber: $this->phoneNumberFromIndex($index),
                    createdAt: $createdAt,
                    updatedAt: $updatedAt,
                    lastLoginAt: $lastLoginAt,
                    marketingConsent: $index % 3 !== 0
                )
            );
        }
    }

    private function loadArtisans(
        ObjectManager $manager,
        Generator $faker
    ): void {
        for ($index = 1; $index <= FixtureReferences::ARTISAN_USER_COUNT; ++$index) {
            $createdAt = $this->randomDateBetween(
                $faker,
                '2025-09-15 08:00:00',
                '2026-06-15 18:00:00'
            );
            $updatedAt = $createdAt->modify(sprintf('+%d days', ($index % 35) + 5));

            $this->persistUser(
                $manager,
                FixtureReferences::artisanUser($index),
                $this->createUser(
                    email: sprintf('artisan%02d@example.test', $index),
                    firstName: $faker->firstName(),
                    lastName: $faker->lastName(),
                    type: UserType::ARTISAN,
                    status: UserStatus::ACTIVE,
                    isVerified: true,
                    phoneNumber: $this->phoneNumberFromIndex($index + 100),
                    createdAt: $createdAt,
                    updatedAt: $updatedAt,
                    lastLoginAt: $updatedAt->modify(sprintf('+%d hours', $index % 6)),
                    marketingConsent: $index % 2 === 0
                )
            );
        }
    }

    private function loadCommercialPartners(
        ObjectManager $manager,
        Generator $faker
    ): void {
        for (
            $index = 1;
            $index <= FixtureReferences::COMMERCIAL_PARTNER_USER_COUNT;
            ++$index
        ) {
            $isActive = $index <= FixtureReferences::VALIDATED_COMMERCIAL_PARTNER_PROFILE_COUNT;
            $createdAt = $this->randomDateBetween(
                $faker,
                '2025-11-01 08:00:00',
                '2026-07-01 18:00:00'
            );
            $updatedAt = $createdAt->modify(sprintf('+%d days', ($index % 24) + 3));
            $lastLoginAt = $isActive
                ? $updatedAt->modify('+2 hours')
                : null;

            $this->persistUser(
                $manager,
                FixtureReferences::commercialPartnerUser($index),
                $this->createUser(
                    email: sprintf('partner%02d@example.test', $index),
                    firstName: $faker->firstName(),
                    lastName: $faker->lastName(),
                    type: UserType::COMMERCIAL_PARTNER,
                    status: $isActive
                        ? UserStatus::ACTIVE
                        : UserStatus::PENDING,
                    isVerified: $isActive,
                    phoneNumber: $this->phoneNumberFromIndex($index + 200),
                    createdAt: $createdAt,
                    updatedAt: $updatedAt,
                    lastLoginAt: $lastLoginAt,
                    marketingConsent: false
                )
            );
        }
    }

    private function loadTestAccounts(ObjectManager $manager): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-18 09:00:00');

        foreach ([
            ['user', 'user@user.user', 'user', UserType::CUSTOMER, []],
            ['artisan', 'artisan@artisan.artisan', 'artisan', UserType::ARTISAN, []],
            ['admin', 'admin@admin.admin', 'admin', UserType::CUSTOMER, ['ROLE_ADMIN']],
            ['commercial', 'commercial@commercial.commercial', 'commercial', UserType::COMMERCIAL_PARTNER, []],
        ] as [$reference, $email, $password, $type, $roles]) {
            $this->persistUser(
                $manager,
                FixtureReferences::testUser($reference),
                $this->createUser(
                    email: $email,
                    firstName: ucfirst($reference),
                    lastName: 'Test',
                    type: $type,
                    status: UserStatus::ACTIVE,
                    isVerified: true,
                    phoneNumber: null,
                    createdAt: $createdAt,
                    updatedAt: $createdAt,
                    lastLoginAt: null,
                    roles: $roles,
                    password: $password
                )
            );
        }
    }

    private function persistUser(
        ObjectManager $manager,
        string $reference,
        User $user
    ): void {
        $manager->persist($user);
        $this->addReference($reference, $user);
    }

    private function createUser(
        string $email,
        string $firstName,
        string $lastName,
        UserType $type,
        UserStatus $status,
        bool $isVerified,
        ?string $phoneNumber,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $lastLoginAt = null,
        bool $marketingConsent = false,
        array $roles = [],
        string $password = self::DEFAULT_PASSWORD
    ): User {
        $user = new User();
        $acceptedAt = $createdAt->modify('+1 day');

        $user
            ->setEmail($email)
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $password
                )
            )
            ->setType($type)
            ->setStatus($status)
            ->setRoles($roles)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPhoneNumber($phoneNumber)
            ->setLocale('fr')
            ->setCountryCode('FR')
            ->setTimezone('Europe/Paris')
            ->setIsVerified($isVerified)
            ->setIsPhoneVerified($isVerified && $phoneNumber !== null)
            ->setHasAcceptedTerms(true)
            ->setTermsAcceptedAt($acceptedAt)
            ->setTermsVersion(self::TERMS_VERSION)
            ->setHasAcceptedPrivacyPolicy(true)
            ->setPrivacyPolicyAcceptedAt($acceptedAt)
            ->setPrivacyPolicyVersion(self::PRIVACY_VERSION)
            ->setMarketingConsent($marketingConsent)
            ->setLastLoginAt($lastLoginAt)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt);

        if ($marketingConsent) {
            $user->setMarketingConsentAt($acceptedAt->modify('+30 minutes'));
        }

        return $user;
    }

    private function randomDateBetween(
        Generator $faker,
        string $start,
        string $end
    ): \DateTimeImmutable {
        return \DateTimeImmutable::createFromMutable(
            $faker->dateTimeBetween($start, $end)
        );
    }

    private function phoneNumberFromIndex(int $index): string
    {
        $digits = str_pad((string) (($index * 7919) % 100000000), 8, '0', STR_PAD_LEFT);

        return sprintf(
            '+33 6 %s %s %s %s',
            substr($digits, 0, 2),
            substr($digits, 2, 2),
            substr($digits, 4, 2),
            substr($digits, 6, 2)
        );
    }
}
