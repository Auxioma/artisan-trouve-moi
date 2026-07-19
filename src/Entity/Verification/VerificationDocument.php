<?php

declare(strict_types=1);

namespace App\Entity\Verification;

use App\Entity\Enum\VerificationDocumentType;
use App\Entity\Enum\VerificationStatus;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Repository\Verification\VerificationDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Pièce justificative du wizard. Les assurances portent une date d’expiration pour le renouvellement annuel.
 */
#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: VerificationDocumentRepository::class)]
#[ORM\Table(name: 'verification_document')]
#[ORM\HasLifecycleCallbacks]
class VerificationDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ArtisanProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\Column(enumType: VerificationDocumentType::class)]
    private VerificationDocumentType $type;

    #[ORM\Column(enumType: VerificationStatus::class)]
    private VerificationStatus $status = VerificationStatus::NOT_SUBMITTED;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewedBy = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $rejectionReason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentName = null;

    #[ORM\Column(nullable: true)]
    private ?int $documentSize = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $documentMimeType = null;

    #[Vich\UploadableField(mapping: 'verification_documents', fileNameProperty: 'documentName', size: 'documentSize', mimeType: 'documentMimeType')]
    private ?File $documentFile = null;

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

    public function getType(): VerificationDocumentType
    {
        return $this->type;
    }

    public function setType(VerificationDocumentType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): VerificationStatus
    {
        return $this->status;
    }

    public function setStatus(VerificationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;

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

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;

        return $this;
    }

    public function getDocumentName(): ?string
    {
        return $this->documentName;
    }

    public function setDocumentName(?string $documentName): static
    {
        $this->documentName = $documentName;

        return $this;
    }

    public function getDocumentSize(): ?int
    {
        return $this->documentSize;
    }

    public function setDocumentSize(?int $documentSize): static
    {
        $this->documentSize = $documentSize;

        return $this;
    }

    public function getDocumentMimeType(): ?string
    {
        return $this->documentMimeType;
    }

    public function setDocumentMimeType(?string $documentMimeType): static
    {
        $this->documentMimeType = $documentMimeType;

        return $this;
    }

    public function setDocumentFile(?File $documentFile): static
    {
        $this->documentFile = $documentFile;

        if ($documentFile !== null) {
            // Force Doctrine à détecter un changement pour déclencher l’upload Vich.
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getDocumentFile(): ?File
    {
        return $this->documentFile;
    }

    public function submit(): static
    {
        $this->status = VerificationStatus::PENDING;
        $this->submittedAt = new \DateTimeImmutable();

        return $this;
    }

    public function approve(?User $reviewer = null): static
    {
        $this->status = VerificationStatus::VERIFIED;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->reviewedBy = $reviewer;

        return $this;
    }

    public function reject(string $reason, ?User $reviewer = null): static
    {
        $this->status = VerificationStatus::REJECTED;
        $this->reviewedAt = new \DateTimeImmutable();
        $this->reviewedBy = $reviewer;
        $this->rejectionReason = $reason;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
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
