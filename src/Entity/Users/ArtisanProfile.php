<?php

declare(strict_types=1);

namespace App\Entity\Users;

use App\Entity\Enum\QualificationType;
use App\Entity\Enum\VerificationStatus;
use App\Repository\User\ArtisanProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: ArtisanProfileRepository::class)]
#[ORM\Table(name: 'artisan_profile')]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
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
    #[Gedmo\Slug(
        fields: ['legalName'],
        updatable: true,
        unique: true
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionalLiabilityDocumentName = null;

    #[ORM\Column(nullable: true)]
    private ?int $professionalLiabilityDocumentSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $professionalLiabilityDocumentMimeType = null;

    #[Vich\UploadableField(
        mapping: 'artisan_insurance_documents',
        fileNameProperty: 'professionalLiabilityDocumentName',
        size: 'professionalLiabilityDocumentSize',
        mimeType: 'professionalLiabilityDocumentMimeType'
    )]
    private ?File $professionalLiabilityDocumentFile = null;

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
    private ?string $decennialInsuranceDocumentName = null;

    #[ORM\Column(nullable: true)]
    private ?int $decennialInsuranceDocumentSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $decennialInsuranceDocumentMimeType = null;

    #[Vich\UploadableField(
        mapping: 'artisan_insurance_documents',
        fileNameProperty: 'decennialInsuranceDocumentName',
        size: 'decennialInsuranceDocumentSize',
        mimeType: 'decennialInsuranceDocumentMimeType'
    )]
    private ?File $decennialInsuranceDocumentFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $decennialGeographicalCoverage = null;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $decennialInsuranceVerificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    /*
     * Coordonnées postales et géographiques OpenStreetMap / Nominatim.
     */

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $houseNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $road = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $addressComplement = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $neighbourhood = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $suburb = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $cityDistrict = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $hamlet = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $village = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $town = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $city = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $municipality = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $county = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $stateDistrict = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $state = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $region = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $country = null;

    #[ORM\Column(length: 2, nullable: true)]
    #[Assert\Country]
    private ?string $countryCode = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $osmDisplayName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $osmId = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(choices: ['node', 'way', 'relation'])]
    private ?string $osmType = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $osmCategory = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $osmPlaceType = null;

    #[ORM\Column(nullable: true)]
    private ?int $nominatimPlaceId = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $travelRadiusKm = null;

    #[ORM\Column]
    private bool $worksAtCustomerAddress = true;

    #[ORM\Column]
    private bool $receivesCustomers = false;

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

    #[ORM\OneToOne(
        mappedBy: 'artisanProfile',
        targetEntity: ArtisanNotificationPreferences::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?ArtisanNotificationPreferences $notificationPreferences = null;


    /**
     * @var Collection<int, \App\Entity\Catalog\ArtisanService>
     */
    #[ORM\OneToMany(
        mappedBy: 'artisanProfile',
        targetEntity: \App\Entity\Catalog\ArtisanService::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $services;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->services = new ArrayCollection();

        $this->setNotificationPreferences(new ArtisanNotificationPreferences());
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Les fichiers temporaires Vich ne doivent jamais être stockés dans la
     * session Symfony ou dans le token de sécurité.
     */
    public function __serialize(): array
    {
        $data = (array) $this;

        unset(
            $data["\0".self::class."\0professionalLiabilityDocumentFile"],
            $data["\0".self::class."\0decennialInsuranceDocumentFile"],
        );

        return $data;
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
        $this->commercialName = null !== $commercialName
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
        $this->siren = null !== $siren
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
        $this->siret = null !== $siret
            ? preg_replace('/\D/', '', $siret)
            : null;

        if (null !== $this->siret && strlen($this->siret) >= 9) {
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
        $this->vatNumber = null !== $vatNumber
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
        $this->apeCode = null !== $apeCode
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
        $this->legalForm = null !== $legalForm
            ? trim($legalForm)
            : null;

        return $this;
    }

    public function getIdentityVerificationStatus(): VerificationStatus
    {
        return $this->identityVerificationStatus;
    }

    public function setIdentityVerificationStatus(
        VerificationStatus $status,
    ): static {
        $this->identityVerificationStatus = $status;

        return $this;
    }

    public function getCompanyVerificationStatus(): VerificationStatus
    {
        return $this->companyVerificationStatus;
    }

    public function setCompanyVerificationStatus(
        VerificationStatus $status,
    ): static {
        $this->companyVerificationStatus = $status;

        return $this;
    }

    public function getRneVerificationStatus(): VerificationStatus
    {
        return $this->rneVerificationStatus;
    }

    public function setRneVerificationStatus(
        VerificationStatus $status,
    ): static {
        $this->rneVerificationStatus = $status;

        if (VerificationStatus::VERIFIED === $status) {
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
        ?\DateTimeImmutable $rneVerifiedAt,
    ): static {
        $this->rneVerifiedAt = $rneVerifiedAt;

        return $this;
    }

    public function getQualificationType(): ?QualificationType
    {
        return $this->qualificationType;
    }

    public function setQualificationType(
        ?QualificationType $qualificationType,
    ): static {
        $this->qualificationType = $qualificationType;

        return $this;
    }

    public function getQualificationTitle(): ?string
    {
        return $this->qualificationTitle;
    }

    public function setQualificationTitle(
        ?string $qualificationTitle,
    ): static {
        $this->qualificationTitle = $qualificationTitle;

        return $this;
    }

    public function getQualificationNumber(): ?string
    {
        return $this->qualificationNumber;
    }

    public function setQualificationNumber(
        ?string $qualificationNumber,
    ): static {
        $this->qualificationNumber = $qualificationNumber;

        return $this;
    }

    public function getQualificationObtainedAt(): ?\DateTimeImmutable
    {
        return $this->qualificationObtainedAt;
    }

    public function setQualificationObtainedAt(
        ?\DateTimeImmutable $qualificationObtainedAt,
    ): static {
        $this->qualificationObtainedAt = $qualificationObtainedAt;

        return $this;
    }

    public function getQualificationVerificationStatus(): VerificationStatus
    {
        return $this->qualificationVerificationStatus;
    }

    public function setQualificationVerificationStatus(
        VerificationStatus $status,
    ): static {
        $this->qualificationVerificationStatus = $status;

        return $this;
    }

    public function isUnderQualifiedPersonControl(): bool
    {
        return $this->underQualifiedPersonControl;
    }

    public function setUnderQualifiedPersonControl(
        bool $underQualifiedPersonControl,
    ): static {
        $this->underQualifiedPersonControl = $underQualifiedPersonControl;

        return $this;
    }

    public function getQualifiedPersonFirstName(): ?string
    {
        return $this->qualifiedPersonFirstName;
    }

    public function setQualifiedPersonFirstName(
        ?string $qualifiedPersonFirstName,
    ): static {
        $this->qualifiedPersonFirstName = $qualifiedPersonFirstName;

        return $this;
    }

    public function getQualifiedPersonLastName(): ?string
    {
        return $this->qualifiedPersonLastName;
    }

    public function setQualifiedPersonLastName(
        ?string $qualifiedPersonLastName,
    ): static {
        $this->qualifiedPersonLastName = $qualifiedPersonLastName;

        return $this;
    }

    public function getQualifiedPersonPosition(): ?string
    {
        return $this->qualifiedPersonPosition;
    }

    public function setQualifiedPersonPosition(
        ?string $qualifiedPersonPosition,
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
        bool $required,
    ): static {
        $this->professionalLiabilityInsuranceRequired = $required;

        return $this;
    }

    public function hasProfessionalLiabilityInsurance(): bool
    {
        return $this->hasProfessionalLiabilityInsurance;
    }

    public function setHasProfessionalLiabilityInsurance(
        bool $hasInsurance,
    ): static {
        $this->hasProfessionalLiabilityInsurance = $hasInsurance;

        return $this;
    }

    public function getProfessionalLiabilityInsurer(): ?string
    {
        return $this->professionalLiabilityInsurer;
    }

    public function setProfessionalLiabilityInsurer(
        ?string $insurer,
    ): static {
        $this->professionalLiabilityInsurer = $insurer;

        return $this;
    }

    public function getProfessionalLiabilityPolicyNumber(): ?string
    {
        return $this->professionalLiabilityPolicyNumber;
    }

    public function setProfessionalLiabilityPolicyNumber(
        ?string $policyNumber,
    ): static {
        $this->professionalLiabilityPolicyNumber = $policyNumber;

        return $this;
    }

    public function getProfessionalLiabilityStartsAt(): ?\DateTimeImmutable
    {
        return $this->professionalLiabilityStartsAt;
    }

    public function setProfessionalLiabilityStartsAt(
        ?\DateTimeImmutable $startsAt,
    ): static {
        $this->professionalLiabilityStartsAt = $startsAt;

        return $this;
    }

    public function getProfessionalLiabilityExpiresAt(): ?\DateTimeImmutable
    {
        return $this->professionalLiabilityExpiresAt;
    }

    public function setProfessionalLiabilityExpiresAt(
        ?\DateTimeImmutable $expiresAt,
    ): static {
        $this->professionalLiabilityExpiresAt = $expiresAt;

        return $this;
    }

    public function getProfessionalLiabilityVerificationStatus(): VerificationStatus
    {
        return $this->professionalLiabilityVerificationStatus;
    }

    public function setProfessionalLiabilityVerificationStatus(
        VerificationStatus $status,
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
        ?string $policyNumber,
    ): static {
        $this->decennialPolicyNumber = $policyNumber;

        return $this;
    }

    public function getDecennialInsuranceStartsAt(): ?\DateTimeImmutable
    {
        return $this->decennialInsuranceStartsAt;
    }

    public function setDecennialInsuranceStartsAt(
        ?\DateTimeImmutable $startsAt,
    ): static {
        $this->decennialInsuranceStartsAt = $startsAt;

        return $this;
    }

    public function getDecennialInsuranceExpiresAt(): ?\DateTimeImmutable
    {
        return $this->decennialInsuranceExpiresAt;
    }

    public function setDecennialInsuranceExpiresAt(
        ?\DateTimeImmutable $expiresAt,
    ): static {
        $this->decennialInsuranceExpiresAt = $expiresAt;

        return $this;
    }

    public function getDecennialGeographicalCoverage(): ?string
    {
        return $this->decennialGeographicalCoverage;
    }

    public function setDecennialGeographicalCoverage(
        ?string $coverage,
    ): static {
        $this->decennialGeographicalCoverage = $coverage;

        return $this;
    }

    public function getDecennialInsuranceVerificationStatus(): VerificationStatus
    {
        return $this->decennialInsuranceVerificationStatus;
    }

    public function setDecennialInsuranceVerificationStatus(
        VerificationStatus $status,
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
            VerificationStatus::VERIFIED
            !== $this->professionalLiabilityVerificationStatus
        ) {
            return false;
        }

        return null === $this->professionalLiabilityExpiresAt
            || $this->professionalLiabilityExpiresAt > new \DateTimeImmutable();
    }

    public function isDecennialInsuranceCurrentlyValid(): bool
    {
        if (!$this->hasDecennialInsurance) {
            return false;
        }

        if (
            VerificationStatus::VERIFIED
            !== $this->decennialInsuranceVerificationStatus
        ) {
            return false;
        }

        return null === $this->decennialInsuranceExpiresAt
            || $this->decennialInsuranceExpiresAt > new \DateTimeImmutable();
    }

    public function isLegallyReadyForPublication(): bool
    {
        if (
            VerificationStatus::VERIFIED
            !== $this->companyVerificationStatus
        ) {
            return false;
        }

        if (
            VerificationStatus::VERIFIED
            !== $this->rneVerificationStatus
        ) {
            return false;
        }

        $hasQualification = VerificationStatus::VERIFIED
            === $this->qualificationVerificationStatus;

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
            throw new \LogicException('Le profil artisan ne respecte pas toutes les conditions nécessaires à sa publication.');
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

    public function getHouseNumber(): ?string
    {
        return $this->houseNumber;
    }

    public function setHouseNumber(?string $houseNumber): static
    {
        $this->houseNumber = null !== $houseNumber ? trim($houseNumber) : null;

        return $this;
    }

    public function getRoad(): ?string
    {
        return $this->road;
    }

    public function setRoad(?string $road): static
    {
        $this->road = null !== $road ? trim($road) : null;

        return $this;
    }

    public function getAddressComplement(): ?string
    {
        return $this->addressComplement;
    }

    public function setAddressComplement(?string $addressComplement): static
    {
        $this->addressComplement = null !== $addressComplement ? trim($addressComplement) : null;

        return $this;
    }

    public function getNeighbourhood(): ?string
    {
        return $this->neighbourhood;
    }

    public function setNeighbourhood(?string $neighbourhood): static
    {
        $this->neighbourhood = null !== $neighbourhood ? trim($neighbourhood) : null;

        return $this;
    }

    public function getSuburb(): ?string
    {
        return $this->suburb;
    }

    public function setSuburb(?string $suburb): static
    {
        $this->suburb = null !== $suburb ? trim($suburb) : null;

        return $this;
    }

    public function getCityDistrict(): ?string
    {
        return $this->cityDistrict;
    }

    public function setCityDistrict(?string $cityDistrict): static
    {
        $this->cityDistrict = null !== $cityDistrict ? trim($cityDistrict) : null;

        return $this;
    }

    public function getHamlet(): ?string
    {
        return $this->hamlet;
    }

    public function setHamlet(?string $hamlet): static
    {
        $this->hamlet = null !== $hamlet ? trim($hamlet) : null;

        return $this;
    }

    public function getVillage(): ?string
    {
        return $this->village;
    }

    public function setVillage(?string $village): static
    {
        $this->village = null !== $village ? trim($village) : null;

        return $this;
    }

    public function getTown(): ?string
    {
        return $this->town;
    }

    public function setTown(?string $town): static
    {
        $this->town = null !== $town ? trim($town) : null;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = null !== $city ? trim($city) : null;

        return $this;
    }

    public function getMunicipality(): ?string
    {
        return $this->municipality;
    }

    public function setMunicipality(?string $municipality): static
    {
        $this->municipality = null !== $municipality ? trim($municipality) : null;

        return $this;
    }

    public function getCounty(): ?string
    {
        return $this->county;
    }

    public function setCounty(?string $county): static
    {
        $this->county = null !== $county ? trim($county) : null;

        return $this;
    }

    public function getStateDistrict(): ?string
    {
        return $this->stateDistrict;
    }

    public function setStateDistrict(?string $stateDistrict): static
    {
        $this->stateDistrict = null !== $stateDistrict ? trim($stateDistrict) : null;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = null !== $state ? trim($state) : null;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = null !== $region ? trim($region) : null;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = null !== $postalCode ? strtoupper(trim($postalCode)) : null;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = null !== $country ? trim($country) : null;

        return $this;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): static
    {
        $this->countryCode = null !== $countryCode ? strtoupper(trim($countryCode)) : null;

        return $this;
    }

    public function getOsmDisplayName(): ?string
    {
        return $this->osmDisplayName;
    }

    public function setOsmDisplayName(?string $osmDisplayName): static
    {
        $this->osmDisplayName = null !== $osmDisplayName ? trim($osmDisplayName) : null;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string|float|int|null $latitude): static
    {
        $this->latitude = null !== $latitude ? number_format((float) $latitude, 7, '.', '') : null;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string|float|int|null $longitude): static
    {
        $this->longitude = null !== $longitude ? number_format((float) $longitude, 7, '.', '') : null;

        return $this;
    }

    public function getOsmId(): ?int
    {
        return $this->osmId;
    }

    public function setOsmId(?int $osmId): static
    {
        $this->osmId = $osmId;

        return $this;
    }

    public function getOsmType(): ?string
    {
        return $this->osmType;
    }

    public function setOsmType(?string $osmType): static
    {
        $this->osmType = null !== $osmType ? mb_strtolower(trim($osmType)) : null;

        return $this;
    }

    public function getOsmCategory(): ?string
    {
        return $this->osmCategory;
    }

    public function setOsmCategory(?string $osmCategory): static
    {
        $this->osmCategory = null !== $osmCategory ? trim($osmCategory) : null;

        return $this;
    }

    public function getOsmPlaceType(): ?string
    {
        return $this->osmPlaceType;
    }

    public function setOsmPlaceType(?string $osmPlaceType): static
    {
        $this->osmPlaceType = null !== $osmPlaceType ? trim($osmPlaceType) : null;

        return $this;
    }

    public function getNominatimPlaceId(): ?int
    {
        return $this->nominatimPlaceId;
    }

    public function setNominatimPlaceId(?int $nominatimPlaceId): static
    {
        $this->nominatimPlaceId = $nominatimPlaceId;

        return $this;
    }

    public function getTravelRadiusKm(): ?int
    {
        return $this->travelRadiusKm;
    }

    public function setTravelRadiusKm(?int $travelRadiusKm): static
    {
        $this->travelRadiusKm = null !== $travelRadiusKm ? max(0, $travelRadiusKm) : null;

        return $this;
    }

    public function worksAtCustomerAddress(): bool
    {
        return $this->worksAtCustomerAddress;
    }

    public function setWorksAtCustomerAddress(bool $worksAtCustomerAddress): static
    {
        $this->worksAtCustomerAddress = $worksAtCustomerAddress;

        return $this;
    }

    public function receivesCustomers(): bool
    {
        return $this->receivesCustomers;
    }

    public function setReceivesCustomers(bool $receivesCustomers): static
    {
        $this->receivesCustomers = $receivesCustomers;

        return $this;
    }

    public function getLocality(): ?string
    {
        return $this->city
            ?? $this->town
            ?? $this->village
            ?? $this->municipality
            ?? $this->hamlet;
    }

    public function getShortAddress(): string
    {
        $street = trim(sprintf('%s %s', $this->houseNumber ?? '', $this->road ?? ''));

        return implode(', ', array_filter([
            '' !== $street ? $street : null,
            $this->postalCode,
            $this->getLocality(),
            $this->country,
        ]));
    }

    public function hasCoordinates(): bool
    {
        return null !== $this->latitude && null !== $this->longitude;
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

    public function getProfessionalLiabilityDocumentName(): ?string
    {
        return $this->professionalLiabilityDocumentName;
    }

    public function setProfessionalLiabilityDocumentName(?string $documentName): static
    {
        $this->professionalLiabilityDocumentName = $documentName;

        return $this;
    }

    public function getProfessionalLiabilityDocumentSize(): ?int
    {
        return $this->professionalLiabilityDocumentSize;
    }

    public function setProfessionalLiabilityDocumentSize(?int $documentSize): static
    {
        $this->professionalLiabilityDocumentSize = $documentSize;

        return $this;
    }

    public function getProfessionalLiabilityDocumentMimeType(): ?string
    {
        return $this->professionalLiabilityDocumentMimeType;
    }

    public function setProfessionalLiabilityDocumentMimeType(?string $mimeType): static
    {
        $this->professionalLiabilityDocumentMimeType = $mimeType;

        return $this;
    }

    public function getProfessionalLiabilityDocumentFile(): ?File
    {
        return $this->professionalLiabilityDocumentFile;
    }

    public function setProfessionalLiabilityDocumentFile(?File $documentFile): static
    {
        $this->professionalLiabilityDocumentFile = $documentFile;

        if (null !== $documentFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getDecennialInsuranceDocumentName(): ?string
    {
        return $this->decennialInsuranceDocumentName;
    }

    public function setDecennialInsuranceDocumentName(?string $documentName): static
    {
        $this->decennialInsuranceDocumentName = $documentName;

        return $this;
    }

    public function getDecennialInsuranceDocumentSize(): ?int
    {
        return $this->decennialInsuranceDocumentSize;
    }

    public function setDecennialInsuranceDocumentSize(?int $documentSize): static
    {
        $this->decennialInsuranceDocumentSize = $documentSize;

        return $this;
    }

    public function getDecennialInsuranceDocumentMimeType(): ?string
    {
        return $this->decennialInsuranceDocumentMimeType;
    }

    public function setDecennialInsuranceDocumentMimeType(?string $mimeType): static
    {
        $this->decennialInsuranceDocumentMimeType = $mimeType;

        return $this;
    }

    public function getDecennialInsuranceDocumentFile(): ?File
    {
        return $this->decennialInsuranceDocumentFile;
    }

    public function setDecennialInsuranceDocumentFile(?File $documentFile): static
    {
        $this->decennialInsuranceDocumentFile = $documentFile;

        if (null !== $documentFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

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

    public function getNotificationPreferences(): ?ArtisanNotificationPreferences
    {
        return $this->notificationPreferences;
    }

    public function setNotificationPreferences(
        ?ArtisanNotificationPreferences $notificationPreferences,
    ): static {
        if (
            null !== $notificationPreferences
            && $notificationPreferences->getArtisanProfile() !== $this
        ) {
            $notificationPreferences->setArtisanProfile($this);
        }

        $this->notificationPreferences = $notificationPreferences;

        return $this;
    }

    /**
     * Retourne les préférences existantes ou les crée avec
     * les valeurs par défaut.
     */
    public function getOrCreateNotificationPreferences(): ArtisanNotificationPreferences
    {
        if (null === $this->notificationPreferences) {
            $preferences = new ArtisanNotificationPreferences();
            $this->setNotificationPreferences($preferences);
        }

        return $this->notificationPreferences;
    }

    /**
     * @return Collection<int, \App\Entity\Catalog\ArtisanService>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(\App\Entity\Catalog\ArtisanService $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setArtisanProfile($this);
        }

        return $this;
    }

    public function removeService(\App\Entity\Catalog\ArtisanService $service): static
    {
        if ($this->services->removeElement($service) && $service->getArtisanProfile() === $this) {
            $service->setArtisanProfile(null);
        }

        return $this;
    }
}
