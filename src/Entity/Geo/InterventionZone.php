<?php

declare(strict_types=1);

namespace App\Entity\Geo;

use App\Entity\Users\ArtisanProfile;
use App\Repository\Geo\InterventionZoneRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Zone d’intervention : base + rayon + communes, et forfait de déplacement au-delà du rayon.
 */
#[ORM\Entity(repositoryClass: InterventionZoneRepository::class)]
#[ORM\Table(name: 'intervention_zone')]
#[ORM\UniqueConstraint(name: 'uniq_zone_artisan', columns: ['artisan_profile_id'])]
#[ORM\HasLifecycleCallbacks]
class InterventionZone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: ArtisanProfile::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $baseCity = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $basePostalCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 200)]
    private int $radiusKm = 25;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $extraTravelFeeHt = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $travelNote = null;

    #[ORM\Column]
    private bool $isActive = true;

    /** @var Collection<int, InterventionCity> */
    #[ORM\OneToMany(mappedBy: 'zone', targetEntity: InterventionCity::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $cities;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->cities = new ArrayCollection();
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

    public function getArtisanProfile(): ?ArtisanProfile
    {
        return $this->artisanProfile;
    }

    public function setArtisanProfile(?ArtisanProfile $artisanProfile): static
    {
        $this->artisanProfile = $artisanProfile;

        return $this;
    }

    public function getBaseCity(): ?string
    {
        return $this->baseCity;
    }

    public function setBaseCity(?string $baseCity): static
    {
        $this->baseCity = $baseCity;

        return $this;
    }

    public function getBasePostalCode(): ?string
    {
        return $this->basePostalCode;
    }

    public function setBasePostalCode(?string $basePostalCode): static
    {
        $this->basePostalCode = $basePostalCode;

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

    public function getRadiusKm(): int
    {
        return $this->radiusKm;
    }

    public function setRadiusKm(int $radiusKm): static
    {
        $this->radiusKm = $radiusKm;

        return $this;
    }

    public function getExtraTravelFeeHt(): ?string
    {
        return $this->extraTravelFeeHt;
    }

    public function setExtraTravelFeeHt(?string $extraTravelFeeHt): static
    {
        $this->extraTravelFeeHt = $extraTravelFeeHt;

        return $this;
    }

    public function getTravelNote(): ?string
    {
        return $this->travelNote;
    }

    public function setTravelNote(?string $travelNote): static
    {
        $this->travelNote = $travelNote;

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

    /** @return Collection<int, InterventionCity> */
    public function getCities(): Collection
    {
        return $this->cities;
    }

    public function addCity(InterventionCity $city): static
    {
        if (!$this->cities->contains($city)) {
            $this->cities->add($city);
            $city->setZone($this);
        }

        return $this;
    }

    public function removeCity(InterventionCity $city): static
    {
        $this->cities->removeElement($city);

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
