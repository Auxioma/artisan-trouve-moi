<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Users\CommercialPartnerProfile;
use App\Entity\Users\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

final class CommercialPartnerProfileFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(FixtureReferences::COMMERCIAL_PARTNER_PROFILE_FIXTURES_SEED);

        $areas = [
            'Ile-de-France',
            'Auvergne-Rhone-Alpes',
            'Nouvelle-Aquitaine',
            'Provence-Alpes-Cote d Azur',
            'Occitanie',
        ];
        $companyPrefixes = [
            'LeadBoost',
            'Prospection',
            'Acquisition',
            'RendezVous',
            'Habitat',
            'Reseau',
            'Performance',
            'Croissance',
            'Courtage',
            'Conversion',
        ];

        for (
            $index = 1;
            $index <= FixtureReferences::COMMERCIAL_PARTNER_PROFILE_COUNT;
            ++$index
        ) {
            $createdAt = $this->randomDateBetween(
                $faker,
                '2025-11-01 08:00:00',
                '2026-07-05 18:00:00'
            );
            $validated = $index <= FixtureReferences::VALIDATED_COMMERCIAL_PARTNER_PROFILE_COUNT;

            $profile = $this->createCommercialPartnerProfile(
                user: $this->getReference(
                    FixtureReferences::commercialPartnerUser($index),
                    User::class
                ),
                companyName: sprintf(
                    '%s %s',
                    $companyPrefixes[($index - 1) % count($companyPrefixes)],
                    $faker->companySuffix()
                ),
                contactJobTitle: $validated
                    ? 'Responsable partenariats'
                    : 'Charge d affaires',
                businessEmail: sprintf(
                    'partner-business-%02d@example.test',
                    $index
                ),
                businessPhone: $this->businessPhoneFromIndex($index),
                siret: $this->generateSiret($index),
                description: $faker->paragraphs(2, true),
                commercialArea: $areas[($index - 1) % count($areas)],
                commissionRate: 6.5 + ($index * 0.75),
                createdAt: $createdAt,
                validated: $validated
            );

            $this->persistProfile(
                $manager,
                FixtureReferences::commercialPartnerProfile($index),
                $profile
            );
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    private function persistProfile(
        ObjectManager $manager,
        string $reference,
        CommercialPartnerProfile $profile
    ): void {
        $manager->persist($profile);
        $this->addReference($reference, $profile);
    }

    private function createCommercialPartnerProfile(
        User $user,
        string $companyName,
        ?string $contactJobTitle,
        ?string $businessEmail,
        ?string $businessPhone,
        string $siret,
        string $description,
        string $commercialArea,
        float $commissionRate,
        \DateTimeImmutable $createdAt,
        bool $validated
    ): CommercialPartnerProfile {
        $profile = (new CommercialPartnerProfile())
            ->setUser($user)
            ->setCompanyName($companyName)
            ->setContactJobTitle($contactJobTitle)
            ->setBusinessEmail($businessEmail)
            ->setBusinessPhone($businessPhone)
            ->setSiret($siret)
            ->setVatNumber($this->buildFrenchVatNumber($siret))
            ->setCountryCode('FR')
            ->setDescription($description)
            ->setCommercialArea($commercialArea)
            ->setContractReference(sprintf('CP-%s', substr($siret, -6)))
            ->setContractStartsAt($createdAt)
            ->setContractEndsAt($createdAt->modify('+2 years'))
            ->setCommissionRate($commissionRate)
            ->setInternalNotes(
                $validated
                    ? 'Partenaire actif avec contrat valide.'
                    : 'Dossier en attente de validation commerciale.'
            )
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($createdAt->modify('+10 days'));

        if ($validated) {
            $profile->validateProfile();
            $profile->setUpdatedAt($createdAt->modify('+18 days'));
        }

        return $profile;
    }

    private function generateSiret(int $index): string
    {
        return sprintf('81%07d%05d', $index, $index);
    }

    private function buildFrenchVatNumber(string $siret): string
    {
        $digits = preg_replace('/\D/', '', $siret);
        $siren = substr($digits, 0, 9);
        $key = (12 + 3 * ((int) $siren % 97)) % 97;

        return sprintf('FR%02d%s', $key, $siren);
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

    private function businessPhoneFromIndex(int $index): string
    {
        return sprintf(
            '+33 %d %02d %02d %02d %02d',
            (($index - 1) % 8) + 1,
            10 + $index,
            20 + $index,
            30 + $index,
            40 + $index
        );
    }
}
