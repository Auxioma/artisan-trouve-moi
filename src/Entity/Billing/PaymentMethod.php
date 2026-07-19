<?php

declare(strict_types=1);

namespace App\Entity\Billing;

use App\Entity\Users\User;
use App\Repository\Billing\PaymentMethodRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Moyen de paiement tokenisé (Stripe, etc.). Seuls la marque et les 4 derniers chiffres sont stockés.
 */
#[ORM\Entity(repositoryClass: PaymentMethodRepository::class)]
#[ORM\Table(name: 'payment_method')]
#[ORM\HasLifecycleCallbacks]
class PaymentMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 4)]
    #[Assert\Regex(pattern: '/^\d{4}$/', message: 'Les 4 derniers chiffres de la carte sont invalides.')]
    private ?string $last4 = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 12)]
    private int $expiresMonth = 0;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $expiresYear = 0;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $holderName = null;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $providerPaymentMethodId = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getLast4(): ?string
    {
        return $this->last4;
    }

    public function setLast4(?string $last4): static
    {
        $this->last4 = $last4;

        return $this;
    }

    public function getExpiresMonth(): int
    {
        return $this->expiresMonth;
    }

    public function setExpiresMonth(int $expiresMonth): static
    {
        $this->expiresMonth = $expiresMonth;

        return $this;
    }

    public function getExpiresYear(): int
    {
        return $this->expiresYear;
    }

    public function setExpiresYear(int $expiresYear): static
    {
        $this->expiresYear = $expiresYear;

        return $this;
    }

    public function getHolderName(): ?string
    {
        return $this->holderName;
    }

    public function setHolderName(?string $holderName): static
    {
        $this->holderName = $holderName;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getProviderPaymentMethodId(): ?string
    {
        return $this->providerPaymentMethodId;
    }

    public function setProviderPaymentMethodId(?string $providerPaymentMethodId): static
    {
        $this->providerPaymentMethodId = $providerPaymentMethodId;

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
