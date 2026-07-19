<?php

declare(strict_types=1);

namespace App\Entity\Projects;

use App\Entity\Enum\ProjectStatus;
use App\Entity\Quotes\Quote;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Repository\Projects\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Chantier créé à l’acceptation d’un devis. Montant et adresse figés en snapshot au démarrage.
 */
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
#[ORM\UniqueConstraint(name: 'uniq_project_reference', columns: ['reference'])]
#[ORM\UniqueConstraint(name: 'uniq_project_quote', columns: ['quote_id'])]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Quote::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?Quote $quote = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: ArtisanProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    private ?string $title = null;

    #[ORM\Column(enumType: ProjectStatus::class)]
    private ProjectStatus $status = ProjectStatus::PLANNED;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 100)]
    private int $progressPercent = 0;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amountTtc = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $addressLine1 = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endsAt = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $actualStartedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $cancellationReason = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $internalNotes = null;

    /** @var Collection<int, ProjectStep> */
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ProjectStep::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $steps;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->steps = new ArrayCollection();
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

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): static
    {
        $this->quote = $quote;

        return $this;
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

    public function getArtisanProfile(): ?ArtisanProfile
    {
        return $this->artisanProfile;
    }

    public function setArtisanProfile(?ArtisanProfile $artisanProfile): static
    {
        $this->artisanProfile = $artisanProfile;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

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

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getProgressPercent(): int
    {
        return $this->progressPercent;
    }

    public function setProgressPercent(int $progressPercent): static
    {
        $this->progressPercent = $progressPercent;

        return $this;
    }

    public function getAmountTtc(): ?string
    {
        return $this->amountTtc;
    }

    public function setAmountTtc(?string $amountTtc): static
    {
        $this->amountTtc = $amountTtc;

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

    public function getStartsAt(): ?\DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getEndsAt(): ?\DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeImmutable $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function getActualStartedAt(): ?\DateTimeImmutable
    {
        return $this->actualStartedAt;
    }

    public function setActualStartedAt(?\DateTimeImmutable $actualStartedAt): static
    {
        $this->actualStartedAt = $actualStartedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

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

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): static
    {
        $this->internalNotes = $internalNotes;

        return $this;
    }

    /** @return Collection<int, ProjectStep> */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(ProjectStep $step): static
    {
        if (!$this->steps->contains($step)) {
            $this->steps->add($step);
            $step->setProject($this);
        }

        return $this;
    }

    public function removeStep(ProjectStep $step): static
    {
        $this->steps->removeElement($step);

        return $this;
    }

    public function start(): static
    {
        $this->status = ProjectStatus::IN_PROGRESS;
        $this->actualStartedAt = new \DateTimeImmutable();

        return $this;
    }

    public function complete(): static
    {
        $this->status = ProjectStatus::COMPLETED;
        $this->completedAt = new \DateTimeImmutable();
        $this->progressPercent = 100;

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
