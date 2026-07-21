<?php

declare(strict_types=1);

namespace App\Entity\Users;

use App\Repository\User\ArtisanNotificationPreferencesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtisanNotificationPreferencesRepository::class)]
#[ORM\Table(name: 'artisan_notification_preferences')]
#[ORM\HasLifecycleCallbacks]
class ArtisanNotificationPreferences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Profil artisan propriétaire des préférences.
     */
    #[ORM\OneToOne(
        inversedBy: 'notificationPreferences',
        targetEntity: ArtisanProfile::class
    )]
    #[ORM\JoinColumn(
        name: 'artisan_profile_id',
        referencedColumnName: 'id',
        nullable: false,
        unique: true,
        onDelete: 'CASCADE'
    )]
    private ?ArtisanProfile $artisanProfile = null;

    /**
     * E-mail et notification push lorsqu'une nouvelle demande
     * correspond à la zone d'intervention de l'artisan.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $newRequestsEnabled = true;

    /**
     * SMS immédiat lorsqu'une demande est marquée urgente.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $urgentRequestsSmsEnabled = true;

    /**
     * Notification lorsqu'un client envoie un nouveau message.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $clientMessagesEnabled = true;

    /**
     * Notification lorsqu'un client publie un avis.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $newReviewsEnabled = true;

    /**
     * Rappel avant expiration d'un devis sans réponse.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $quoteRemindersEnabled = true;

    /**
     * Envoi du récapitulatif hebdomadaire.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $weeklySummaryEnabled = true;

    /**
     * Conseils, bonnes pratiques et nouveautés TrouveMoi.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $tipsAndNewsEnabled = false;

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

        if (
            null !== $artisanProfile
            && $artisanProfile->getNotificationPreferences() !== $this
        ) {
            $artisanProfile->setNotificationPreferences($this);
        }

        return $this;
    }

    public function isNewRequestsEnabled(): bool
    {
        return $this->newRequestsEnabled;
    }

    public function setNewRequestsEnabled(bool $newRequestsEnabled): static
    {
        $this->newRequestsEnabled = $newRequestsEnabled;

        return $this;
    }

    public function isUrgentRequestsSmsEnabled(): bool
    {
        return $this->urgentRequestsSmsEnabled;
    }

    public function setUrgentRequestsSmsEnabled(
        bool $urgentRequestsSmsEnabled,
    ): static {
        $this->urgentRequestsSmsEnabled = $urgentRequestsSmsEnabled;

        return $this;
    }

    public function isClientMessagesEnabled(): bool
    {
        return $this->clientMessagesEnabled;
    }

    public function setClientMessagesEnabled(
        bool $clientMessagesEnabled,
    ): static {
        $this->clientMessagesEnabled = $clientMessagesEnabled;

        return $this;
    }

    public function isNewReviewsEnabled(): bool
    {
        return $this->newReviewsEnabled;
    }

    public function setNewReviewsEnabled(bool $newReviewsEnabled): static
    {
        $this->newReviewsEnabled = $newReviewsEnabled;

        return $this;
    }

    public function isQuoteRemindersEnabled(): bool
    {
        return $this->quoteRemindersEnabled;
    }

    public function setQuoteRemindersEnabled(
        bool $quoteRemindersEnabled,
    ): static {
        $this->quoteRemindersEnabled = $quoteRemindersEnabled;

        return $this;
    }

    public function isWeeklySummaryEnabled(): bool
    {
        return $this->weeklySummaryEnabled;
    }

    public function setWeeklySummaryEnabled(
        bool $weeklySummaryEnabled,
    ): static {
        $this->weeklySummaryEnabled = $weeklySummaryEnabled;

        return $this;
    }

    public function isTipsAndNewsEnabled(): bool
    {
        return $this->tipsAndNewsEnabled;
    }

    public function setTipsAndNewsEnabled(
        bool $tipsAndNewsEnabled,
    ): static {
        $this->tipsAndNewsEnabled = $tipsAndNewsEnabled;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
