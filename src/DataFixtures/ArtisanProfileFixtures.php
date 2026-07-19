<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Enum\QualificationType;
use App\Entity\Enum\VerificationStatus;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

final class ArtisanProfileFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $faker->seed(FixtureReferences::ARTISAN_PROFILE_FIXTURES_SEED);

        $trades = $this->tradeCatalog();
        $legalForms = ['EI', 'EURL', 'SASU', 'SARL'];

        for ($index = 1; $index <= FixtureReferences::ARTISAN_PROFILE_COUNT; ++$index) {
            $user = $this->getReference(
                FixtureReferences::artisanUser($index),
                User::class
            );
            $trade = $trades[($index - 1) % count($trades)];
            $createdAt = $this->randomDateBetween(
                $faker,
                '2025-10-01 08:00:00',
                '2026-06-20 18:00:00'
            );

            if ($index <= FixtureReferences::PUBLISHED_ARTISAN_PROFILE_COUNT) {
                $underQualifiedControl = 0 === $index % 4;

                $profile = $this->createPublishedArtisanProfile(
                    user: $user,
                    legalName: sprintf(
                        'Atelier %s %s',
                        $user->getLastName(),
                        $trade['company_suffix']
                    ),
                    commercialName: sprintf(
                        '%s %s',
                        $trade['commercial_prefix'],
                        $user->getLastName()
                    ),
                    slug: sprintf('artisan-%02d-%s', $index, $trade['slug']),
                    siret: $this->generateSiret($index),
                    apeCode: $trade['ape_code'],
                    legalForm: $legalForms[($index - 1) % count($legalForms)],
                    qualificationType: $underQualifiedControl
                        ? null
                        : $trade['qualification_type'],
                    qualificationTitle: $underQualifiedControl
                        ? null
                        : $trade['qualification_title'],
                    qualificationNumber: $underQualifiedControl
                        ? null
                        : sprintf('QUAL-%04d', 3000 + $index),
                    experienceYears: 3 + ($index % 15),
                    description: $faker->paragraphs(2, true),
                    createdAt: $createdAt,
                    qualificationVerified: !$underQualifiedControl,
                    underQualifiedPersonControl: $underQualifiedControl,
                    qualifiedPersonFirstName: $underQualifiedControl
                        ? $faker->firstName()
                        : null,
                    qualifiedPersonLastName: $underQualifiedControl
                        ? $faker->lastName()
                        : null,
                    qualifiedPersonPosition: $underQualifiedControl
                        ? 'Responsable technique'
                        : null
                );
            } else {
                $profile = $this->createDraftArtisanProfile(
                    user: $user,
                    legalName: sprintf(
                        'Entreprise %s %s',
                        $user->getLastName(),
                        $trade['company_suffix']
                    ),
                    commercialName: sprintf(
                        '%s %s',
                        $trade['commercial_prefix'],
                        $user->getLastName()
                    ),
                    slug: sprintf('artisan-%02d-%s', $index, $trade['slug']),
                    siret: $this->generateSiret($index),
                    apeCode: $trade['ape_code'],
                    legalForm: $legalForms[($index - 1) % count($legalForms)],
                    experienceYears: 2 + ($index % 8),
                    description: $faker->paragraphs(2, true),
                    createdAt: $createdAt
                );
            }

            $this->persistProfile(
                $manager,
                FixtureReferences::artisanProfile($index),
                $profile
            );
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }

    /**
     * @return list<array{
     *     slug: string,
     *     company_suffix: string,
     *     commercial_prefix: string,
     *     ape_code: string,
     *     qualification_type: QualificationType,
     *     qualification_title: string
     * }>
     */
    private function tradeCatalog(): array
    {
        return [
            [
                'slug' => 'plomberie',
                'company_suffix' => 'Plomberie',
                'commercial_prefix' => 'Plomberie Habitat',
                'ape_code' => '43.22A',
                'qualification_type' => QualificationType::CAP,
                'qualification_title' => 'CAP Installateur sanitaire',
            ],
            [
                'slug' => 'electricite',
                'company_suffix' => 'Electricite',
                'commercial_prefix' => 'Electricite Service',
                'ape_code' => '43.21A',
                'qualification_type' => QualificationType::BP,
                'qualification_title' => 'BP Electricien',
            ],
            [
                'slug' => 'menuiserie',
                'company_suffix' => 'Menuiserie',
                'commercial_prefix' => 'Menuiserie Interieur',
                'ape_code' => '43.32A',
                'qualification_type' => QualificationType::BEP,
                'qualification_title' => 'BEP Menuiserie agencement',
            ],
            [
                'slug' => 'maconnerie',
                'company_suffix' => 'Maconnerie',
                'commercial_prefix' => 'Maconnerie Renov',
                'ape_code' => '43.99C',
                'qualification_type' => QualificationType::BAC_PRO,
                'qualification_title' => 'Bac pro Technicien du batiment',
            ],
            [
                'slug' => 'peinture',
                'company_suffix' => 'Peinture',
                'commercial_prefix' => 'Peinture Deco',
                'ape_code' => '43.34Z',
                'qualification_type' => QualificationType::CAP,
                'qualification_title' => 'CAP Peintre applicateur',
            ],
            [
                'slug' => 'carrelage',
                'company_suffix' => 'Carrelage',
                'commercial_prefix' => 'Carrelage Design',
                'ape_code' => '43.33Z',
                'qualification_type' => QualificationType::BTS,
                'qualification_title' => 'BTS Amenagement finition',
            ],
            [
                'slug' => 'couverture',
                'company_suffix' => 'Couverture',
                'commercial_prefix' => 'Couverture Toiture',
                'ape_code' => '43.91A',
                'qualification_type' => QualificationType::PROFESSIONAL_TITLE,
                'qualification_title' => 'Titre professionnel Couvreur zingueur',
            ],
            [
                'slug' => 'renovation',
                'company_suffix' => 'Renovation',
                'commercial_prefix' => 'Renovation Habitat',
                'ape_code' => '43.39Z',
                'qualification_type' => QualificationType::RNCP_CERTIFICATION,
                'qualification_title' => 'Certification RNCP conduite de chantier',
            ],
        ];
    }

    private function persistProfile(
        ObjectManager $manager,
        string $reference,
        ArtisanProfile $profile,
    ): void {
        $manager->persist($profile);
        $this->addReference($reference, $profile);
    }

    private function createPublishedArtisanProfile(
        User $user,
        string $legalName,
        ?string $commercialName,
        string $slug,
        string $siret,
        string $apeCode,
        string $legalForm,
        ?QualificationType $qualificationType,
        ?string $qualificationTitle,
        ?string $qualificationNumber,
        int $experienceYears,
        string $description,
        \DateTimeImmutable $createdAt,
        bool $qualificationVerified = true,
        bool $underQualifiedPersonControl = false,
        ?string $qualifiedPersonFirstName = null,
        ?string $qualifiedPersonLastName = null,
        ?string $qualifiedPersonPosition = null,
    ): ArtisanProfile {
        $profile = (new ArtisanProfile())
            ->setUser($user)
            ->setLegalName($legalName)
            ->setCommercialName($commercialName)
            ->setSlug($slug)
            ->setSiret($siret)
            ->setVatNumber($this->buildFrenchVatNumber($siret))
            ->setApeCode($apeCode)
            ->setLegalForm($legalForm)
            ->setIdentityVerificationStatus(VerificationStatus::VERIFIED)
            ->setCompanyVerificationStatus(VerificationStatus::VERIFIED)
            ->setRneVerificationStatus(VerificationStatus::VERIFIED)
            ->setQualificationType($qualificationType)
            ->setQualificationTitle($qualificationTitle)
            ->setQualificationNumber($qualificationNumber)
            ->setQualificationObtainedAt(
                null !== $qualificationType
                    ? $createdAt->modify('-10 years')
                    : null
            )
            ->setQualificationVerificationStatus(
                $qualificationVerified
                    ? VerificationStatus::VERIFIED
                    : VerificationStatus::NOT_SUBMITTED
            )
            ->setUnderQualifiedPersonControl($underQualifiedPersonControl)
            ->setQualifiedPersonFirstName($qualifiedPersonFirstName)
            ->setQualifiedPersonLastName($qualifiedPersonLastName)
            ->setQualifiedPersonPosition($qualifiedPersonPosition)
            ->setExperienceYears($experienceYears)
            ->setDescription($description)
            ->setProfessionalLiabilityInsuranceRequired(true)
            ->setHasProfessionalLiabilityInsurance(true)
            ->setProfessionalLiabilityInsurer('MAAF Pro')
            ->setProfessionalLiabilityPolicyNumber(
                sprintf('RCP-%s', substr($siret, -6))
            )
            ->setProfessionalLiabilityStartsAt($createdAt->modify('-1 month'))
            ->setProfessionalLiabilityExpiresAt($createdAt->modify('+2 years'))
            ->setProfessionalLiabilityVerificationStatus(
                VerificationStatus::VERIFIED
            )
            ->setDecennialInsuranceRequired(true)
            ->setHasDecennialInsurance(true)
            ->setDecennialInsurer('AXA Construction')
            ->setDecennialPolicyNumber(
                sprintf('DEC-%s', substr($siret, -6))
            )
            ->setDecennialInsuranceStartsAt($createdAt->modify('-1 month'))
            ->setDecennialInsuranceExpiresAt($createdAt->modify('+2 years'))
            ->setDecennialGeographicalCoverage('France metropolitaine')
            ->setDecennialInsuranceVerificationStatus(
                VerificationStatus::VERIFIED
            )
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($createdAt->modify('+15 days'));

        $profile->setRneVerifiedAt($createdAt->modify('+10 days'));

        $profile
            ->validateProfile()
            ->publish();

        $profile->setUpdatedAt($createdAt->modify('+20 days'));

        return $profile;
    }

    private function createDraftArtisanProfile(
        User $user,
        string $legalName,
        ?string $commercialName,
        string $slug,
        string $siret,
        string $apeCode,
        string $legalForm,
        int $experienceYears,
        string $description,
        \DateTimeImmutable $createdAt,
    ): ArtisanProfile {
        return (new ArtisanProfile())
            ->setUser($user)
            ->setLegalName($legalName)
            ->setCommercialName($commercialName)
            ->setSlug($slug)
            ->setSiret($siret)
            ->setVatNumber($this->buildFrenchVatNumber($siret))
            ->setApeCode($apeCode)
            ->setLegalForm($legalForm)
            ->setIdentityVerificationStatus(VerificationStatus::VERIFIED)
            ->setCompanyVerificationStatus(VerificationStatus::NOT_SUBMITTED)
            ->setRneVerificationStatus(VerificationStatus::NOT_SUBMITTED)
            ->setQualificationVerificationStatus(
                VerificationStatus::NOT_SUBMITTED
            )
            ->setExperienceYears($experienceYears)
            ->setDescription($description)
            ->setProfessionalLiabilityInsuranceRequired(true)
            ->setHasProfessionalLiabilityInsurance(false)
            ->setDecennialInsuranceRequired(true)
            ->setHasDecennialInsurance(false)
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($createdAt->modify('+7 days'));
    }

    private function generateSiret(int $index): string
    {
        return sprintf('80%07d%05d', $index, $index);
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
        string $end,
    ): \DateTimeImmutable {
        return \DateTimeImmutable::createFromMutable(
            $faker->dateTimeBetween($start, $end)
        );
    }
}
