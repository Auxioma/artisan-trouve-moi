<?php

declare(strict_types=1);

namespace App\Entity\Reviews;

use App\Entity\Projects\Project;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Repository\Reviews\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Avis vérifié avec sous-notes optionnelles (qualité, ponctualité, propreté du chantier).
 */
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\UniqueConstraint(name: 'uniq_review_project', columns: ['project_id'])]
#[ORM\HasLifecycleCallbacks]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: ArtisanProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\OneToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: true, unique: true, onDelete: 'SET NULL')]
    private ?Project $project = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}.')]
    private int $rating = 5;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $qualityRating = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $punctualityRating = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $cleanlinessRating = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?bool $wouldRecommend = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(max: 3000)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $response = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    #[ORM\Column]
    private bool $isPublished = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $moderatedAt = null;

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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

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

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getQualityRating(): ?int
    {
        return $this->qualityRating;
    }

    public function setQualityRating(?int $qualityRating): static
    {
        $this->qualityRating = $qualityRating;

        return $this;
    }

    public function getPunctualityRating(): ?int
    {
        return $this->punctualityRating;
    }

    public function setPunctualityRating(?int $punctualityRating): static
    {
        $this->punctualityRating = $punctualityRating;

        return $this;
    }

    public function getCleanlinessRating(): ?int
    {
        return $this->cleanlinessRating;
    }

    public function setCleanlinessRating(?int $cleanlinessRating): static
    {
        $this->cleanlinessRating = $cleanlinessRating;

        return $this;
    }

    public function getWouldRecommend(): ?bool
    {
        return $this->wouldRecommend;
    }

    public function setWouldRecommend(?bool $wouldRecommend): static
    {
        $this->wouldRecommend = $wouldRecommend;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(?string $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function setRespondedAt(?\DateTimeImmutable $respondedAt): static
    {
        $this->respondedAt = $respondedAt;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

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

    public function getModeratedAt(): ?\DateTimeImmutable
    {
        return $this->moderatedAt;
    }

    public function setModeratedAt(?\DateTimeImmutable $moderatedAt): static
    {
        $this->moderatedAt = $moderatedAt;

        return $this;
    }

    public function respond(string $response): static
    {
        $this->response = $response;
        $this->respondedAt = new \DateTimeImmutable();

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
