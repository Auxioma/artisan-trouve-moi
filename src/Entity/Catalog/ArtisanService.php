<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Entity\Enum\PriceUnit;
use App\Entity\Users\ArtisanProfile;
use App\Repository\Catalog\ArtisanServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Prestation proposée par un artisan, affichée sur sa fiche publique avec un prix « à partir de ».
 */
#[ORM\Entity(repositoryClass: ArtisanServiceRepository::class)]
#[ORM\Table(name: 'artisan_service')]
#[ORM\HasLifecycleCallbacks]
class ArtisanService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ArtisanProfile::class, inversedBy: 'services')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Category $category = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Le titre de la prestation est obligatoire.')]
    #[Assert\Length(max: 180)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $priceFrom = null;

    #[ORM\Column(enumType: PriceUnit::class)]
    private PriceUnit $priceUnit = PriceUnit::FLAT;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $estimatedDurationHours = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isActive = true;

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

    public function getArtisanProfile(): ?ArtisanProfile
    {
        return $this->artisanProfile;
    }

    public function setArtisanProfile(?ArtisanProfile $artisanProfile): static
    {
        $this->artisanProfile = $artisanProfile;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

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

    public function getPriceFrom(): ?string
    {
        return $this->priceFrom;
    }

    public function setPriceFrom(?string $priceFrom): static
    {
        $this->priceFrom = $priceFrom;

        return $this;
    }

    public function getPriceUnit(): PriceUnit
    {
        return $this->priceUnit;
    }

    public function setPriceUnit(PriceUnit $priceUnit): static
    {
        $this->priceUnit = $priceUnit;

        return $this;
    }

    public function getEstimatedDurationHours(): ?int
    {
        return $this->estimatedDurationHours;
    }

    public function setEstimatedDurationHours(?int $estimatedDurationHours): static
    {
        $this->estimatedDurationHours = $estimatedDurationHours;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
