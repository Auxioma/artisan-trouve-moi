<?php

declare(strict_types=1);

namespace App\Entity\Users;

use App\Entity\Enum\QualificationType;
use App\Entity\Enum\VerificationStatus;
use App\Repository\User\ArtisanProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArtisanProfileRepository::class)]
#[ORM\Table(name: 'artisan_profile')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['siret'],
    message: 'Ce numéro SIRET est déjà utilisé par un autre artisan.'
)]
#[UniqueEntity(
    fields: ['slug'],
    message: 'Cette adresse publique est déjà utilisée.'
)]
class ArtisanProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(
        inversedBy: 'artisanProfile',
        targetEntity: User::class
    )]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        nullable: false,
        unique: true,
        onDelete: 'CASCADE'
    )]
    private ?User $user = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'La dénomination de l’entreprise est obligatoire.')]
    #[Assert\Length(max: 180)]
    private ?string $legalName = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $commercialName = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L’adresse publique de l’artisan est obligatoire.')]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: 'Le slug doit uniquement contenir des lettres minuscules, des chiffres et des tirets.'
    )]
    private ?string $slug = null;

    /**
     * Numéro SIREN français sur 9 chiffres.
     */
    #[ORM\Column(length: 9, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d{9}$/',
        message: 'Le numéro SIREN doit contenir exactement 9 chiffres.'
    )]
    private ?string $siren = null;

    /**
     * Numéro SIRET français sur 14 chiffres.
     */
    #[ORM\Column(length: 14, unique: true, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d{14}$/',
        message: 'Le numéro SIRET doit contenir exactement 14 chiffres.'
    )]
    private ?string $siret = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $vatNumber = null;

    /**
     * Exemple : 43.22A.
     */
    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d{2}\.\d{2}[A-Z]$/',
        message: 'Le code APE doit respecter un format comme 43.22A.'
    )]
    private ?string $apeCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $legalForm = null;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $identityVerificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $companyVerificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $rneVerificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    #[ORM\Column]
    private bool $isRegisteredInRne = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $rneVerifiedAt = null;

    #[ORM\Column(enumType: QualificationType::class, nullable: true)]
    private ?QualificationType $qualificationType = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $qualificationTitle = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $qualificationNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $qualificationObtainedAt = null;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $qualificationVerificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    /**
     * Indique si l'activité est exercée sous le contrôle effectif
     * d'une autre personne professionnellement qualifiée.
     */
    #[ORM\Column]
    private bool $underQualifiedPersonControl = false;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $qualifiedPersonFirstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $qualifiedPersonLastName = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\Length(max: 150)]
    private ?string $qualifiedPersonPosition = null;

    #[ORM\Column]
    private int $experienceYears = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 10000)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $professionalLiabilityInsuranceRequired = false;

    #[ORM\Column]
    private bool $hasProfessionalLiabilityInsurance = false;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $professionalLiabilityInsurer = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $professionalLiabilityPolicyNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $professionalLiabilityStartsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $professionalLiabilityExpiresAt = null;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $professionalLiabilityVerificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    #[ORM\Column]
    private bool $decennialInsuranceRequired = false;

    #[ORM\Column]
    private bool $hasDecennialInsurance = false;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $decennialInsurer = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $decennialPolicyNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $decennialInsuranceStartsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $decennialInsuranceExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $decennialGeographicalCoverage = null;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $decennialInsuranceVerificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    #[ORM\Column]
    private bool $isPublished = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        if ($user->getArtisanProfile() !== $this) {
            $user->setArtisanProfile($this);
        }

        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): static
    {
        $this->legalName = trim($legalName);

        return $this;
    }

    public function getCommercialName(): ?string
    {
        return $this->commercialName;
    }

    public function setCommercialName(?string $commercialName): static
    {
        $this->commercialName = $commercialName !== null
            ? trim($commercialName)
            : null;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->commercialName ?: (string) $this->legalName;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = mb_strtolower(trim($slug));

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren !== null
            ? preg_replace('/\D/', '', $siren)
            : null;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret !== null
            ? preg_replace('/\D/', '', $siret)
            : null;

        if ($this->siret !== null && strlen($this->siret) >= 9) {
            $this->siren = substr($this->siret, 0, 9);
        }

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber !== null
            ? strtoupper(str_replace(' ', '', trim($vatNumber)))
            : null;

        return $this;
    }

    public function getApeCode(): ?string
    {
        return $this->apeCode;
    }

    public function setApeCode(?string $apeCode): static
    {
        $this->apeCode = $apeCode !== null
            ? strtoupper(trim($apeCode))
            : null;

        return $this;
    }

    public function getLegalForm(): ?string
    {
        return $this->legalForm;
    }

    public function setLegalForm(?string $legalForm): static
    {
        $this->legalForm = $legalForm !== null
            ? trim($legalForm)
            : null;

        return $this;
    }

    public function getIdentityVerificationStatus(): VerificationStatus
    {
        return $this->identityVerificationStatus;
    }

    public function setIdentityVerificationStatus(
        VerificationStatus $status
    ): static {
        $this->identityVerificationStatus = $status;

        return $this;
    }

    public function getCompanyVerificationStatus(): VerificationStatus
    {
        return $this->companyVerificationStatus;
    }

    public function setCompanyVerificationStatus(
        VerificationStatus $status
    ): static {
        $this->companyVerificationStatus = $status;

        return $this;
    }

    public function getRneVerificationStatus(): VerificationStatus
    {
        return $this->rneVerificationStatus;
    }

    public function setRneVerificationStatus(
        VerificationStatus $status
    ): static {
        $this->rneVerificationStatus = $status;

        if ($status === VerificationStatus::VERIFIED) {
            $this->isRegisteredInRne = true;
            $this->rneVerifiedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isRegisteredInRne(): bool
    {
        return $this->isRegisteredInRne;
    }

    public function setIsRegisteredInRne(bool $isRegisteredInRne): static
    {
        $this->isRegisteredInRne = $isRegisteredInRne;

        return $this;
    }

    public function getRneVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->rneVerifiedAt;
    }

    public function setRneVerifiedAt(
        ?\DateTimeImmutable $rneVerifiedAt
    ): static {
        $this->rneVerifiedAt = $rneVerifiedAt;

        return $this;
    }

    public function getQualificationType(): ?QualificationType
    {
        return $this->qualificationType;
    }

    public function setQualificationType(
        ?QualificationType $qualificationType
    ): static {
        $this->qualificationType = $qualificationType;

        return $this;
    }

    public function getQualificationTitle(): ?string
    {
        return $this->qualificationTitle;
    }

    public function setQualificationTitle(
        ?string $qualificationTitle
    ): static {
        $this->qualificationTitle = $qualificationTitle;

        return $this;
    }

    public function getQualificationNumber(): ?string
    {
        return $this->qualificationNumber;
    }

    public function setQualificationNumber(
        ?string $qualificationNumber
    ): static {
        $this->qualificationNumber = $qualificationNumber;

        return $this;
    }

    public function getQualificationObtainedAt(): ?\DateTimeImmutable
    {
        return $this->qualificationObtainedAt;
    }

    public function setQualificationObtainedAt(
        ?\DateTimeImmutable $qualificationObtainedAt
    ): static {
        $this->qualificationObtainedAt = $qualificationObtainedAt;

        return $this;
    }

    public function getQualificationVerificationStatus(): VerificationStatus
    {
        return $this->qualificationVerificationStatus;
    }

    public function setQualificationVerificationStatus(
        VerificationStatus $status
    ): static {
        $this->qualificationVerificationStatus = $status;

        return $this;
    }

    public function isUnderQualifiedPersonControl(): bool
    {
        return $this->underQualifiedPersonControl;
    }

    public function setUnderQualifiedPersonControl(
        bool $underQualifiedPersonControl
    ): static {
        $this->underQualifiedPersonControl = $underQualifiedPersonControl;

        return $this;
    }

    public function getQualifiedPersonFirstName(): ?string
    {
        return $this->qualifiedPersonFirstName;
    }

    public function setQualifiedPersonFirstName(
        ?string $qualifiedPersonFirstName
    ): static {
        $this->qualifiedPersonFirstName = $qualifiedPersonFirstName;

        return $this;
    }

    public function getQualifiedPersonLastName(): ?string
    {
        return $this->qualifiedPersonLastName;
    }

    public function setQualifiedPersonLastName(
        ?string $qualifiedPersonLastName
    ): static {
        $this->qualifiedPersonLastName = $qualifiedPersonLastName;

        return $this;
    }

    public function getQualifiedPersonPosition(): ?string
    {
        return $this->qualifiedPersonPosition;
    }

    public function setQualifiedPersonPosition(
        ?string $qualifiedPersonPosition
    ): static {
        $this->qualifiedPersonPosition = $qualifiedPersonPosition;

        return $this;
    }

    public function getExperienceYears(): int
    {
        return $this->experienceYears;
    }

    public function setExperienceYears(int $experienceYears): static
    {
        $this->experienceYears = max(0, $experienceYears);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isProfessionalLiabilityInsuranceRequired(): bool
    {
        return $this->professionalLiabilityInsuranceRequired;
    }

    public function setProfessionalLiabilityInsuranceRequired(
        bool $required
    ): static {
        $this->professionalLiabilityInsuranceRequired = $required;

        return $this;
    }

    public function hasProfessionalLiabilityInsurance(): bool
    {
        return $this->hasProfessionalLiabilityInsurance;
    }

    public function setHasProfessionalLiabilityInsurance(
        bool $hasInsurance
    ): static {
        $this->hasProfessionalLiabilityInsurance = $hasInsurance;

        return $this;
    }

    public function getProfessionalLiabilityInsurer(): ?string
    {
        return $this->professionalLiabilityInsurer;
    }

    public function setProfessionalLiabilityInsurer(
        ?string $insurer
    ): static {
        $this->professionalLiabilityInsurer = $insurer;

        return $this;
    }

    public function getProfessionalLiabilityPolicyNumber(): ?string
    {
        return $this->professionalLiabilityPolicyNumber;
    }

    public function setProfessionalLiabilityPolicyNumber(
        ?string $policyNumber
    ): static {
        $this->professionalLiabilityPolicyNumber = $policyNumber;

        return $this;
    }

    public function getProfessionalLiabilityStartsAt(): ?\DateTimeImmutable
    {
        return $this->professionalLiabilityStartsAt;
    }

    public function setProfessionalLiabilityStartsAt(
        ?\DateTimeImmutable $startsAt
    ): static {
        $this->professionalLiabilityStartsAt = $startsAt;

        return $this;
    }

    public function getProfessionalLiabilityExpiresAt(): ?\DateTimeImmutable
    {
        return $this->professionalLiabilityExpiresAt;
    }

    public function setProfessionalLiabilityExpiresAt(
        ?\DateTimeImmutable $expiresAt
    ): static {
        $this->professionalLiabilityExpiresAt = $expiresAt;

        return $this;
    }

    public function getProfessionalLiabilityVerificationStatus(): VerificationStatus
    {
        return $this->professionalLiabilityVerificationStatus;
    }

    public function setProfessionalLiabilityVerificationStatus(
        VerificationStatus $status
    ): static {
        $this->professionalLiabilityVerificationStatus = $status;

        return $this;
    }

    public function isDecennialInsuranceRequired(): bool
    {
        return $this->decennialInsuranceRequired;
    }

    public function setDecennialInsuranceRequired(bool $required): static
    {
        $this->decennialInsuranceRequired = $required;

        return $this;
    }

    public function hasDecennialInsurance(): bool
    {
        return $this->hasDecennialInsurance;
    }

    public function setHasDecennialInsurance(bool $hasInsurance): static
    {
        $this->hasDecennialInsurance = $hasInsurance;

        return $this;
    }

    public function getDecennialInsurer(): ?string
    {
        return $this->decennialInsurer;
    }

    public function setDecennialInsurer(?string $insurer): static
    {
        $this->decennialInsurer = $insurer;

        return $this;
    }

    public function getDecennialPolicyNumber(): ?string
    {
        return $this->decennialPolicyNumber;
    }

    public function setDecennialPolicyNumber(
        ?string $policyNumber
    ): static {
        $this->decennialPolicyNumber = $policyNumber;

        return $this;
    }

    public function getDecennialInsuranceStartsAt(): ?\DateTimeImmutable
    {
        return $this->decennialInsuranceStartsAt;
    }

    public function setDecennialInsuranceStartsAt(
        ?\DateTimeImmutable $startsAt
    ): static {
        $this->decennialInsuranceStartsAt = $startsAt;

        return $this;
    }

    public function getDecennialInsuranceExpiresAt(): ?\DateTimeImmutable
    {
        return $this->decennialInsuranceExpiresAt;
    }

    public function setDecennialInsuranceExpiresAt(
        ?\DateTimeImmutable $expiresAt
    ): static {
        $this->decennialInsuranceExpiresAt = $expiresAt;

        return $this;
    }

    public function getDecennialGeographicalCoverage(): ?string
    {
        return $this->decennialGeographicalCoverage;
    }

    public function setDecennialGeographicalCoverage(
        ?string $coverage
    ): static {
        $this->decennialGeographicalCoverage = $coverage;

        return $this;
    }

    public function getDecennialInsuranceVerificationStatus(): VerificationStatus
    {
        return $this->decennialInsuranceVerificationStatus;
    }

    public function setDecennialInsuranceVerificationStatus(
        VerificationStatus $status
    ): static {
        $this->decennialInsuranceVerificationStatus = $status;

        return $this;
    }

    public function isProfessionalLiabilityInsuranceCurrentlyValid(): bool
    {
        if (!$this->hasProfessionalLiabilityInsurance) {
            return false;
        }

        if (
            $this->professionalLiabilityVerificationStatus
            !== VerificationStatus::VERIFIED
        ) {
            return false;
        }

        return $this->professionalLiabilityExpiresAt === null
            || $this->professionalLiabilityExpiresAt > new \DateTimeImmutable();
    }

    public function isDecennialInsuranceCurrentlyValid(): bool
    {
        if (!$this->hasDecennialInsurance) {
            return false;
        }

        if (
            $this->decennialInsuranceVerificationStatus
            !== VerificationStatus::VERIFIED
        ) {
            return false;
        }

        return $this->decennialInsuranceExpiresAt === null
            || $this->decennialInsuranceExpiresAt > new \DateTimeImmutable();
    }

    public function isLegallyReadyForPublication(): bool
    {
        if (
            $this->companyVerificationStatus
            !== VerificationStatus::VERIFIED
        ) {
            return false;
        }

        if (
            $this->rneVerificationStatus
            !== VerificationStatus::VERIFIED
        ) {
            return false;
        }

        $hasQualification = $this->qualificationVerificationStatus
            === VerificationStatus::VERIFIED;

        if (!$hasQualification && !$this->underQualifiedPersonControl) {
            return false;
        }

        if (
            $this->professionalLiabilityInsuranceRequired
            && !$this->isProfessionalLiabilityInsuranceCurrentlyValid()
        ) {
            return false;
        }

        if (
            $this->decennialInsuranceRequired
            && !$this->isDecennialInsuranceCurrentlyValid()
        ) {
            return false;
        }

        return true;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function publish(): static
    {
        if (!$this->isLegallyReadyForPublication()) {
            throw new \LogicException(
                'Le profil artisan ne respecte pas toutes les conditions nécessaires à sa publication.'
            );
        }

        $this->isPublished = true;
        $this->publishedAt = new \DateTimeImmutable();
        $this->rejectionReason = null;

        return $this;
    }

    public function unpublish(): static
    {
        $this->isPublished = false;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function validateProfile(): static
    {
        $this->validatedAt = new \DateTimeImmutable();
        $this->rejectionReason = null;

        return $this;
    }

    public function rejectProfile(string $reason): static
    {
        $this->isPublished = false;
        $this->validatedAt = null;
        $this->rejectionReason = trim($reason);

        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
