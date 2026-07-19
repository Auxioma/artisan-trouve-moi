<?php

declare(strict_types=1);

namespace App\Entity\Requests;

use App\Entity\Catalog\Category;
use App\Entity\Enum\RequestStatus;
use App\Entity\Quotes\Quote;
use App\Entity\Users\User;
use App\Repository\Requests\ServiceRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Demande de travaux publiée par un particulier. Seuls code postal, ville et quartier sont publics.
 */
#[ORM\Entity(repositoryClass: ServiceRequestRepository::class)]
#[ORM\Table(name: 'service_request')]
#[ORM\HasLifecycleCallbacks]
class ServiceRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Category $category = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Le titre de la demande est obligatoire.')]
    #[Assert\Length(max: 180)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    #[ORM\Column(enumType: RequestStatus::class)]
    private RequestStatus $status = RequestStatus::DRAFT;

    #[ORM\Column]
    private bool $isUrgent = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budgetMin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budgetMax = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $desiredStartAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $propertyType = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $surfaceM2 = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $accessDetails = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $availabilityNote = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $addressLine1 = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    private ?string $postalCode = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private ?string $city = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\OneToOne(targetEntity: Quote::class)]
    #[ORM\JoinColumn(nullable: true, unique: true, onDelete: 'SET NULL')]
    private ?Quote $awardedQuote = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10)]
    private int $maxQuotes = 5;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column]
    private int $quotesCount = 0;

    #[ORM\Column]
    private int $viewsCount = 0;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $cancellationReason = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $moderatedAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $source = null;

    /** @var Collection<int, RequestPhoto> */
    #[ORM\OneToMany(mappedBy: 'request', targetEntity: RequestPhoto::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $photos;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->photos = new ArrayCollection();
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

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): static
    {
        $this->client = $client;

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

    public function getStatus(): RequestStatus
    {
        return $this->status;
    }

    public function setStatus(RequestStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isUrgent(): bool
    {
        return $this->isUrgent;
    }

    public function setIsUrgent(bool $isUrgent): static
    {
        $this->isUrgent = $isUrgent;

        return $this;
    }

    public function getBudgetMin(): ?string
    {
        return $this->budgetMin;
    }

    public function setBudgetMin(?string $budgetMin): static
    {
        $this->budgetMin = $budgetMin;

        return $this;
    }

    public function getBudgetMax(): ?string
    {
        return $this->budgetMax;
    }

    public function setBudgetMax(?string $budgetMax): static
    {
        $this->budgetMax = $budgetMax;

        return $this;
    }

    public function getDesiredStartAt(): ?\DateTimeImmutable
    {
        return $this->desiredStartAt;
    }

    public function setDesiredStartAt(?\DateTimeImmutable $desiredStartAt): static
    {
        $this->desiredStartAt = $desiredStartAt;

        return $this;
    }

    public function getPropertyType(): ?string
    {
        return $this->propertyType;
    }

    public function setPropertyType(?string $propertyType): static
    {
        $this->propertyType = $propertyType;

        return $this;
    }

    public function getSurfaceM2(): ?string
    {
        return $this->surfaceM2;
    }

    public function setSurfaceM2(?string $surfaceM2): static
    {
        $this->surfaceM2 = $surfaceM2;

        return $this;
    }

    public function getAccessDetails(): ?string
    {
        return $this->accessDetails;
    }

    public function setAccessDetails(?string $accessDetails): static
    {
        $this->accessDetails = $accessDetails;

        return $this;
    }

    public function getAvailabilityNote(): ?string
    {
        return $this->availabilityNote;
    }

    public function setAvailabilityNote(?string $availabilityNote): static
    {
        $this->availabilityNote = $availabilityNote;

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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getAwardedQuote(): ?Quote
    {
        return $this->awardedQuote;
    }

    public function setAwardedQuote(?Quote $awardedQuote): static
    {
        $this->awardedQuote = $awardedQuote;

        return $this;
    }

    public function getMaxQuotes(): int
    {
        return $this->maxQuotes;
    }

    public function setMaxQuotes(int $maxQuotes): static
    {
        $this->maxQuotes = $maxQuotes;

        return $this;
    }

    public function getQuotesCount(): int
    {
        return $this->quotesCount;
    }

    public function setQuotesCount(int $quotesCount): static
    {
        $this->quotesCount = $quotesCount;

        return $this;
    }

    public function getViewsCount(): int
    {
        return $this->viewsCount;
    }

    public function setViewsCount(int $viewsCount): static
    {
        $this->viewsCount = $viewsCount;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function setModeratedAt(?\DateTimeImmutable $moderatedAt): static
    {
        $this->moderatedAt = $moderatedAt;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    /** @return Collection<int, RequestPhoto> */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(RequestPhoto $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setRequest($this);
        }

        return $this;
    }

    public function removePhoto(RequestPhoto $photo): static
    {
        $this->photos->removeElement($photo);

        return $this;
    }

    public function publish(): static
    {
        $this->status = RequestStatus::PUBLISHED;
        $this->publishedAt = new \DateTimeImmutable();

        if ($this->expiresAt === null) {
            $this->expiresAt = $this->publishedAt->modify('+30 days');
        }

        return $this;
    }

    public function award(Quote $quote): static
    {
        $this->status = RequestStatus::AWARDED;
        $this->awardedQuote = $quote;

        return $this;
    }

    public function canReceiveMoreQuotes(): bool
    {
        return $this->status === RequestStatus::PUBLISHED
            && $this->quotesCount < $this->maxQuotes;
    }

    /**
     * Localisation visible publiquement avant acceptation d’un devis
     * (l’adresse exacte n’est transmise qu’à l’artisan retenu).
     */
    public function getPublicLocation(): string
    {
        return trim(sprintf('%s %s', (string) $this->postalCode, (string) $this->city));
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
