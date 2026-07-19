<?php

declare(strict_types=1);

namespace App\Entity\Quotes;

use App\Entity\Enum\QuoteStatus;
use App\Entity\Requests\ServiceRequest;
use App\Entity\Users\ArtisanProfile;
use App\Repository\Quotes\QuoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Devis émis par un artisan en réponse à une demande. Totaux recalculés depuis les lignes (bcmath).
 */
#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'quote')]
#[ORM\UniqueConstraint(name: 'uniq_quote_reference', columns: ['reference'])]
#[ORM\HasLifecycleCallbacks]
class Quote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ServiceRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ServiceRequest $serviceRequest = null;

    #[ORM\ManyToOne(targetEntity: ArtisanProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(enumType: QuoteStatus::class)]
    private QuoteStatus $status = QuoteStatus::DRAFT;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 3000)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $vatRate = '20.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalVat = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalTtc = '0.00';

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $discountHt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $depositPercent = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $workDurationDays = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $canStartAt = null;

    #[ORM\Column]
    private int $warrantyMonths = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $viewedByClientAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $remindedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $signedAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $signatureIp = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $refusedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000)]
    private ?string $refusalReason = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfFilename = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $termsAndConditions = null;

    /** @var Collection<int, QuoteLine> */
    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteLine::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lines;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->lines = new ArrayCollection();
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

    public function getServiceRequest(): ?ServiceRequest
    {
        return $this->serviceRequest;
    }

    public function setServiceRequest(?ServiceRequest $serviceRequest): static
    {
        $this->serviceRequest = $serviceRequest;

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

    public function getStatus(): QuoteStatus
    {
        return $this->status;
    }

    public function setStatus(QuoteStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getVatRate(): string
    {
        return $this->vatRate;
    }

    public function setVatRate(string $vatRate): static
    {
        $this->vatRate = $vatRate;

        return $this;
    }

    public function getTotalHt(): string
    {
        return $this->totalHt;
    }

    public function setTotalHt(string $totalHt): static
    {
        $this->totalHt = $totalHt;

        return $this;
    }

    public function getTotalVat(): string
    {
        return $this->totalVat;
    }

    public function setTotalVat(string $totalVat): static
    {
        $this->totalVat = $totalVat;

        return $this;
    }

    public function getTotalTtc(): string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(string $totalTtc): static
    {
        $this->totalTtc = $totalTtc;

        return $this;
    }

    public function getDiscountHt(): ?string
    {
        return $this->discountHt;
    }

    public function setDiscountHt(?string $discountHt): static
    {
        $this->discountHt = $discountHt;

        return $this;
    }

    public function getDepositPercent(): ?string
    {
        return $this->depositPercent;
    }

    public function setDepositPercent(?string $depositPercent): static
    {
        $this->depositPercent = $depositPercent;

        return $this;
    }

    public function getWorkDurationDays(): int
    {
        return $this->workDurationDays;
    }

    public function setWorkDurationDays(int $workDurationDays): static
    {
        $this->workDurationDays = $workDurationDays;

        return $this;
    }

    public function getCanStartAt(): ?\DateTimeImmutable
    {
        return $this->canStartAt;
    }

    public function setCanStartAt(?\DateTimeImmutable $canStartAt): static
    {
        $this->canStartAt = $canStartAt;

        return $this;
    }

    public function getWarrantyMonths(): int
    {
        return $this->warrantyMonths;
    }

    public function setWarrantyMonths(int $warrantyMonths): static
    {
        $this->warrantyMonths = $warrantyMonths;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getViewedByClientAt(): ?\DateTimeImmutable
    {
        return $this->viewedByClientAt;
    }

    public function setViewedByClientAt(?\DateTimeImmutable $viewedByClientAt): static
    {
        $this->viewedByClientAt = $viewedByClientAt;

        return $this;
    }

    public function getRemindedAt(): ?\DateTimeImmutable
    {
        return $this->remindedAt;
    }

    public function setRemindedAt(?\DateTimeImmutable $remindedAt): static
    {
        $this->remindedAt = $remindedAt;

        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function getSignedAt(): ?\DateTimeImmutable
    {
        return $this->signedAt;
    }

    public function setSignedAt(?\DateTimeImmutable $signedAt): static
    {
        $this->signedAt = $signedAt;

        return $this;
    }

    public function getSignatureIp(): ?string
    {
        return $this->signatureIp;
    }

    public function setSignatureIp(?string $signatureIp): static
    {
        $this->signatureIp = $signatureIp;

        return $this;
    }

    public function getRefusedAt(): ?\DateTimeImmutable
    {
        return $this->refusedAt;
    }

    public function setRefusedAt(?\DateTimeImmutable $refusedAt): static
    {
        $this->refusedAt = $refusedAt;

        return $this;
    }

    public function getRefusalReason(): ?string
    {
        return $this->refusalReason;
    }

    public function setRefusalReason(?string $refusalReason): static
    {
        $this->refusalReason = $refusalReason;

        return $this;
    }

    public function getPdfFilename(): ?string
    {
        return $this->pdfFilename;
    }

    public function setPdfFilename(?string $pdfFilename): static
    {
        $this->pdfFilename = $pdfFilename;

        return $this;
    }

    public function getTermsAndConditions(): ?string
    {
        return $this->termsAndConditions;
    }

    public function setTermsAndConditions(?string $termsAndConditions): static
    {
        $this->termsAndConditions = $termsAndConditions;

        return $this;
    }

    /** @return Collection<int, QuoteLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(QuoteLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setQuote($this);
        }

        return $this;
    }

    public function removeLine(QuoteLine $line): static
    {
        $this->lines->removeElement($line);

        return $this;
    }

    public function recalculateTotals(): static
    {
        $totalHt = '0.00';

        foreach ($this->lines as $line) {
            $totalHt = bcadd($totalHt, $line->getTotalHt(), 2);
        }

        if ($this->discountHt !== null) {
            $totalHt = bcsub($totalHt, $this->discountHt, 2);
        }

        $this->totalHt = $totalHt;
        $this->totalVat = bcmul($totalHt, bcdiv($this->vatRate, '100', 4), 2);
        $this->totalTtc = bcadd($this->totalHt, $this->totalVat, 2);

        return $this;
    }

    public function markAsSent(): static
    {
        $this->status = QuoteStatus::SENT;
        $this->sentAt = new \DateTimeImmutable();

        if ($this->validUntil === null) {
            $this->validUntil = $this->sentAt->modify('+30 days');
        }

        return $this;
    }

    public function markViewedByClient(): static
    {
        if ($this->viewedByClientAt === null) {
            $this->viewedByClientAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function accept(): static
    {
        $this->status = QuoteStatus::ACCEPTED;
        $this->acceptedAt = new \DateTimeImmutable();

        return $this;
    }

    public function refuse(?string $reason = null): static
    {
        $this->status = QuoteStatus::REFUSED;
        $this->refusedAt = new \DateTimeImmutable();
        $this->refusalReason = $reason;

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
