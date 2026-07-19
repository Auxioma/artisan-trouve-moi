<?php

declare(strict_types=1);

namespace App\Entity\Billing;

use App\Entity\Enum\InvoiceStatus;
use App\Repository\Billing\InvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Facture d’abonnement. Nom et adresse de facturation figés en snapshot (obligation légale).
 */
#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
#[ORM\UniqueConstraint(name: 'uniq_invoice_reference', columns: ['reference'])]
#[ORM\HasLifecycleCallbacks]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'invoices', targetEntity: Subscription::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Subscription $subscription = null;

    #[ORM\Column(length: 30, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(enumType: InvoiceStatus::class)]
    private InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amountHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amountVat = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $amountTtc = '0.00';

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $periodStartsAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $periodEndsAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingName = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $providerInvoiceId = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfFilename = null;

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

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): static
    {
        $this->subscription = $subscription;

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

    public function getStatus(): InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAmountHt(): string
    {
        return $this->amountHt;
    }

    public function setAmountHt(string $amountHt): static
    {
        $this->amountHt = $amountHt;

        return $this;
    }

    public function getAmountVat(): string
    {
        return $this->amountVat;
    }

    public function setAmountVat(string $amountVat): static
    {
        $this->amountVat = $amountVat;

        return $this;
    }

    public function getAmountTtc(): string
    {
        return $this->amountTtc;
    }

    public function setAmountTtc(string $amountTtc): static
    {
        $this->amountTtc = $amountTtc;

        return $this;
    }

    public function getPeriodStartsAt(): ?\DateTimeImmutable
    {
        return $this->periodStartsAt;
    }

    public function setPeriodStartsAt(?\DateTimeImmutable $periodStartsAt): static
    {
        $this->periodStartsAt = $periodStartsAt;

        return $this;
    }

    public function getPeriodEndsAt(): ?\DateTimeImmutable
    {
        return $this->periodEndsAt;
    }

    public function setPeriodEndsAt(?\DateTimeImmutable $periodEndsAt): static
    {
        $this->periodEndsAt = $periodEndsAt;

        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): static
    {
        $this->billingName = $billingName;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?\DateTimeImmutable $issuedAt): static
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getProviderInvoiceId(): ?string
    {
        return $this->providerInvoiceId;
    }

    public function setProviderInvoiceId(?string $providerInvoiceId): static
    {
        $this->providerInvoiceId = $providerInvoiceId;

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

    public function markPaid(): static
    {
        $this->status = InvoiceStatus::PAID;
        $this->paidAt = new \DateTimeImmutable();

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
