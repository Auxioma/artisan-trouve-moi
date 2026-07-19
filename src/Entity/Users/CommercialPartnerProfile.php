<?php

declare(strict_types=1);

namespace App\Entity\Users;

use App\Entity\Enum\VerificationStatus;
use App\Repository\User\CommercialPartnerProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommercialPartnerProfileRepository::class)]
#[ORM\Table(name: 'commercial_partner_profile')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(
    fields: ['businessEmail'],
    message: 'Cette adresse e-mail professionnelle est déjà utilisée.'
)]
class CommercialPartnerProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(
        inversedBy: 'commercialPartnerProfile',
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
    #[Assert\NotBlank(message: 'Le nom de l’entreprise est obligatoire.')]
    #[Assert\Length(max: 180)]
    private ?string $companyName = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Length(max: 180)]
    private ?string $contactJobTitle = null;

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private ?string $businessEmail = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s().-]{6,30}$/',
        message: 'Le numéro de téléphone professionnel n’est pas valide.'
    )]
    private ?string $businessPhone = null;

    #[ORM\Column(length: 9, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d{9}$/',
        message: 'Le numéro SIREN doit contenir exactement 9 chiffres.'
    )]
    private ?string $siren = null;

    #[ORM\Column(length: 14, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d{14}$/',
        message: 'Le numéro SIRET doit contenir exactement 14 chiffres.'
    )]
    private ?string $siret = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column(length: 2)]
    #[Assert\Country]
    private string $countryCode = 'FR';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $commercialArea = null;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $verificationStatus =
        VerificationStatus::NOT_SUBMITTED;

    #[ORM\Column]
    private bool $isActive = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $contractStartsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $contractEndsAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $contractReference = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $commissionRate = '0.00';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $internalNotes = null;

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

        if ($user->getCommercialPartnerProfile() !== $this) {
            $user->setCommercialPartnerProfile($this);
        }

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = trim($companyName);

        return $this;
    }

    public function getContactJobTitle(): ?string
    {
        return $this->contactJobTitle;
    }

    public function setContactJobTitle(?string $contactJobTitle): static
    {
        $this->contactJobTitle = $contactJobTitle;

        return $this;
    }

    public function getBusinessEmail(): ?string
    {
        return $this->businessEmail;
    }

    public function setBusinessEmail(?string $businessEmail): static
    {
        $this->businessEmail = null !== $businessEmail
            ? mb_strtolower(trim($businessEmail))
            : null;

        return $this;
    }

    public function getBusinessPhone(): ?string
    {
        return $this->businessPhone;
    }

    public function setBusinessPhone(?string $businessPhone): static
    {
        $this->businessPhone = $businessPhone;

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
            ? strtoupper(str_replace(' ', '', $vatNumber))
            : null;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = strtoupper(trim($countryCode));

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

    public function getCommercialArea(): ?string
    {
        return $this->commercialArea;
    }

    public function setCommercialArea(?string $commercialArea): static
    {
        $this->commercialArea = $commercialArea;

        return $this;
    }

    public function getVerificationStatus(): VerificationStatus
    {
        return $this->verificationStatus;
    }

    public function setVerificationStatus(
        VerificationStatus $verificationStatus,
    ): static {
        $this->verificationStatus = $verificationStatus;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getContractStartsAt(): ?\DateTimeImmutable
    {
        return $this->contractStartsAt;
    }

    public function setContractStartsAt(
        ?\DateTimeImmutable $contractStartsAt,
    ): static {
        $this->contractStartsAt = $contractStartsAt;

        return $this;
    }

    public function getContractEndsAt(): ?\DateTimeImmutable
    {
        return $this->contractEndsAt;
    }

    public function setContractEndsAt(
        ?\DateTimeImmutable $contractEndsAt,
    ): static {
        $this->contractEndsAt = $contractEndsAt;

        return $this;
    }

    public function getContractReference(): ?string
    {
        return $this->contractReference;
    }

    public function setContractReference(
        ?string $contractReference,
    ): static {
        $this->contractReference = $contractReference;

        return $this;
    }

    public function getCommissionRate(): string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(
        string|float|int $commissionRate,
    ): static {
        $rate = (float) $commissionRate;

        if ($rate < 0 || $rate > 100) {
            throw new \InvalidArgumentException('Le taux de commission doit être compris entre 0 et 100.');
        }

        $this->commissionRate = number_format($rate, 2, '.', '');

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function validateProfile(): static
    {
        $this->verificationStatus = VerificationStatus::VERIFIED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->isActive = true;

        return $this;
    }

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): static
    {
        $this->internalNotes = $internalNotes;

        return $this;
    }

    public function isContractCurrentlyValid(): bool
    {
        $now = new \DateTimeImmutable();

        if (!$this->isActive) {
            return false;
        }

        if (
            null !== $this->contractStartsAt
            && $this->contractStartsAt > $now
        ) {
            return false;
        }

        if (
            null !== $this->contractEndsAt
            && $this->contractEndsAt < $now
        ) {
            return false;
        }

        return true;
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
