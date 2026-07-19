<?php

declare(strict_types=1);

namespace App\Entity\Billing;

use App\Entity\Enum\SubscriptionPlanCode;
use App\Repository\Billing\SubscriptionPlanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Plan artisan (Essentiel 19 €, Premium 39 €, Excellence 69 €). Les limites null = illimité.
 */
#[ORM\Entity(repositoryClass: SubscriptionPlanRepository::class)]
#[ORM\Table(name: 'subscription_plan')]
#[ORM\UniqueConstraint(name: 'uniq_plan_code', columns: ['code'])]
#[ORM\HasLifecycleCallbacks]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: SubscriptionPlanCode::class)]
    private SubscriptionPlanCode $code;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $monthlyPriceHt = '0.00';

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $yearlyPriceHt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    private string $vatRate = '20.00';

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column]
    private int $trialDays = 30;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $maxQuotesPerMonth = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?int $maxCategories = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(nullable: true)]
    private ?int $maxPhotos = null;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column]
    private bool $hasUrgentAccess = false;

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column]
    private bool $hasPriorityRanking = false;

    #[ORM\Column(type: Types::JSON)]
    private array $features = [];

        // ── Champ optionnel ajouté lors de l’audit SaaS ──
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $providerPriceId = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isPopular = false;

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

    public function getCode(): SubscriptionPlanCode
    {
        return $this->code;
    }

    public function setCode(SubscriptionPlanCode $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMonthlyPriceHt(): string
    {
        return $this->monthlyPriceHt;
    }

    public function setMonthlyPriceHt(string $monthlyPriceHt): static
    {
        $this->monthlyPriceHt = $monthlyPriceHt;

        return $this;
    }

    public function getYearlyPriceHt(): ?string
    {
        return $this->yearlyPriceHt;
    }

    public function setYearlyPriceHt(?string $yearlyPriceHt): static
    {
        $this->yearlyPriceHt = $yearlyPriceHt;

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

    public function getTrialDays(): int
    {
        return $this->trialDays;
    }

    public function setTrialDays(int $trialDays): static
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    public function getMaxQuotesPerMonth(): ?int
    {
        return $this->maxQuotesPerMonth;
    }

    public function setMaxQuotesPerMonth(?int $maxQuotesPerMonth): static
    {
        $this->maxQuotesPerMonth = $maxQuotesPerMonth;

        return $this;
    }

    public function getMaxCategories(): ?int
    {
        return $this->maxCategories;
    }

    public function setMaxCategories(?int $maxCategories): static
    {
        $this->maxCategories = $maxCategories;

        return $this;
    }

    public function getMaxPhotos(): ?int
    {
        return $this->maxPhotos;
    }

    public function setMaxPhotos(?int $maxPhotos): static
    {
        $this->maxPhotos = $maxPhotos;

        return $this;
    }

    public function hasUrgentAccess(): bool
    {
        return $this->hasUrgentAccess;
    }

    public function setHasUrgentAccess(bool $hasUrgentAccess): static
    {
        $this->hasUrgentAccess = $hasUrgentAccess;

        return $this;
    }

    public function hasPriorityRanking(): bool
    {
        return $this->hasPriorityRanking;
    }

    public function setHasPriorityRanking(bool $hasPriorityRanking): static
    {
        $this->hasPriorityRanking = $hasPriorityRanking;

        return $this;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function setFeatures(array $features): static
    {
        $this->features = $features;

        return $this;
    }

    public function getProviderPriceId(): ?string
    {
        return $this->providerPriceId;
    }

    public function setProviderPriceId(?string $providerPriceId): static
    {
        $this->providerPriceId = $providerPriceId;

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

    public function isPopular(): bool
    {
        return $this->isPopular;
    }

    public function setIsPopular(bool $isPopular): static
    {
        $this->isPopular = $isPopular;

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
