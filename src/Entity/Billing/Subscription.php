<?php

declare(strict_types=1);

namespace App\Entity\Billing;

use App\Entity\Enum\SubscriptionStatus;
use App\Entity\Users\ArtisanProfile;
use App\Repository\Billing\SubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abonnement d’un artisan. Porte le compteur de devis consommés sur la période (cœur du forfait).
 */
#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscription')]
#[ORM\HasLifecycleCallbacks]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ArtisanProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?SubscriptionPlan $plan = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\ManyToOne(targetEntity: PaymentMethod::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PaymentMethod $paymentMethod = null;

    #[ORM\Column(enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status = SubscriptionStatus::TRIALING;

    #[ORM\Column]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $currentPeriodStartsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $currentPeriodEndsAt = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column]
    private int $quotesUsedInPeriod = 0;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column]
    private bool $cancelAtPeriodEnd = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $providerSubscriptionId = null;

    /** @var Collection<int, Invoice> */
    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: Invoice::class, cascade: ['persist'], orphanRemoval: false)]
    #[ORM\OrderBy(['issuedAt' => 'DESC'])]
    private Collection $invoices;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->startsAt = $now;
        $this->invoices = new ArrayCollection();
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

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeImmutable $startsAt): static
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;

        return $this;
    }

    public function getCurrentPeriodStartsAt(): ?\DateTimeImmutable
    {
        return $this->currentPeriodStartsAt;
    }

    public function setCurrentPeriodStartsAt(?\DateTimeImmutable $currentPeriodStartsAt): static
    {
        $this->currentPeriodStartsAt = $currentPeriodStartsAt;

        return $this;
    }

    public function getCurrentPeriodEndsAt(): ?\DateTimeImmutable
    {
        return $this->currentPeriodEndsAt;
    }

    public function setCurrentPeriodEndsAt(?\DateTimeImmutable $currentPeriodEndsAt): static
    {
        $this->currentPeriodEndsAt = $currentPeriodEndsAt;

        return $this;
    }

    public function getQuotesUsedInPeriod(): int
    {
        return $this->quotesUsedInPeriod;
    }

    public function setQuotesUsedInPeriod(int $quotesUsedInPeriod): static
    {
        $this->quotesUsedInPeriod = $quotesUsedInPeriod;

        return $this;
    }

    public function isCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function setCancelAtPeriodEnd(bool $cancelAtPeriodEnd): static
    {
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;

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

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getProviderSubscriptionId(): ?string
    {
        return $this->providerSubscriptionId;
    }

    public function setProviderSubscriptionId(?string $providerSubscriptionId): static
    {
        $this->providerSubscriptionId = $providerSubscriptionId;

        return $this;
    }

    /** @return Collection<int, Invoice> */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setSubscription($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        $this->invoices->removeElement($invoice);

        return $this;
    }

    public function isCurrentlyActive(): bool
    {
        return \in_array(
            $this->status,
            [SubscriptionStatus::TRIALING, SubscriptionStatus::ACTIVE],
            true
        );
    }

    public function canSendQuote(): bool
    {
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        $max = $this->plan?->getMaxQuotesPerMonth();

        return $max === null || $this->quotesUsedInPeriod < $max;
    }

    public function incrementQuotesUsed(): static
    {
        ++$this->quotesUsedInPeriod;

        return $this;
    }

    public function resetPeriodUsage(): static
    {
        $this->quotesUsedInPeriod = 0;

        return $this;
    }

    public function cancel(): static
    {
        $this->status = SubscriptionStatus::CANCELLED;
        $this->cancelledAt = new \DateTimeImmutable();

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
