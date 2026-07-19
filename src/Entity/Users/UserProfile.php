<?php

namespace App\Entity\Users;

use App\Repository\Users\UserProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
class UserProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userProfile', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    /**
     * HOME, BILLING, WORK, HEADQUARTERS...
     */
    #[ORM\Column(length: 30)]
    private string $type = 'HOME';

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $addressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $addressLine2 = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private ?string $city = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $department = null;

    #[ORM\Column(length: 2)]
    #[Assert\Country]
    private string $countryCode = 'FR';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formattedAddress = null;

    /**
     * Identifiant provenant de Mapbox, Google, OSM, etc.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $providerPlaceId = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $providerName = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 7,
        nullable: true
    )]
    private ?string $latitude = null;

    #[ORM\Column(
        type: Types::DECIMAL,
        precision: 10,
        scale: 7,
        nullable: true
    )]
    private ?string $longitude = null;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isBillingAddress = false;

    #[ORM\Column]
    private bool $isPublic = false;

    /**
     * Indique si l'adresse a été validée par un service de géocodage.
     */
    #[ORM\Column]
    private bool $isGeocoded = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $geocodedAt = null;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function setAddressLine1(?string $addressLine1): static
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    public function getAddressLine2(): ?string
    {
        return $this->addressLine2;
    }

    public function setAddressLine2(?string $addressLine2): static
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): static
    {
        $this->district = $district;

        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function getFormattedAddress(): ?string
    {
        return $this->formattedAddress;
    }

    public function setFormattedAddress(?string $formattedAddress): static
    {
        $this->formattedAddress = $formattedAddress;

        return $this;
    }

    public function getProviderPlaceId(): ?string
    {
        return $this->providerPlaceId;
    }

    public function setProviderPlaceId(?string $providerPlaceId): static
    {
        $this->providerPlaceId = $providerPlaceId;

        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(?string $providerName): static
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function isBillingAddress(): bool
    {
        return $this->isBillingAddress;
    }

    public function setIsBillingAddress(bool $isBillingAddress): static
    {
        $this->isBillingAddress = $isBillingAddress;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function isGeocoded(): bool
    {
        return $this->isGeocoded;
    }

    public function setIsGeocoded(bool $isGeocoded): static
    {
        $this->isGeocoded = $isGeocoded;

        return $this;
    }

    public function getGeocodedAt(): ?\DateTimeImmutable
    {
        return $this->geocodedAt;
    }

    public function setGeocodedAt(?\DateTimeImmutable $geocodedAt): static
    {
        $this->geocodedAt = $geocodedAt;

        return $this;
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
