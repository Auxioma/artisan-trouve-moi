<?php

declare(strict_types=1);

namespace App\Entity\Users;

use App\Repository\Users\UserPreferencesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPreferencesRepository::class)]
#[ORM\HasLifecycleCallbacks]
class UserPreferences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'preferences', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // --- Notifications ("MES PROJETS") ---

    #[ORM\Column]
    private bool $newQuotesEnabled = true;

    #[ORM\Column]
    private bool $artisanMessagesEnabled = true;

    #[ORM\Column]
    private bool $appointmentRemindersEnabled = true;

    #[ORM\Column]
    private bool $reviewInvitationsEnabled = true;

    // --- Visibilité ---

    #[ORM\Column]
    private bool $profileVisibleToArtisans = true;

    #[ORM\Column]
    private bool $phoneSharedAfterAcceptance = true;

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

    public function isNewQuotesEnabled(): bool
    {
        return $this->newQuotesEnabled;
    }

    public function setNewQuotesEnabled(bool $newQuotesEnabled): static
    {
        $this->newQuotesEnabled = $newQuotesEnabled;

        return $this;
    }

    public function isArtisanMessagesEnabled(): bool
    {
        return $this->artisanMessagesEnabled;
    }

    public function setArtisanMessagesEnabled(bool $artisanMessagesEnabled): static
    {
        $this->artisanMessagesEnabled = $artisanMessagesEnabled;

        return $this;
    }

    public function isAppointmentRemindersEnabled(): bool
    {
        return $this->appointmentRemindersEnabled;
    }

    public function setAppointmentRemindersEnabled(bool $appointmentRemindersEnabled): static
    {
        $this->appointmentRemindersEnabled = $appointmentRemindersEnabled;

        return $this;
    }

    public function isReviewInvitationsEnabled(): bool
    {
        return $this->reviewInvitationsEnabled;
    }

    public function setReviewInvitationsEnabled(bool $reviewInvitationsEnabled): static
    {
        $this->reviewInvitationsEnabled = $reviewInvitationsEnabled;

        return $this;
    }

    public function isProfileVisibleToArtisans(): bool
    {
        return $this->profileVisibleToArtisans;
    }

    public function setProfileVisibleToArtisans(bool $profileVisibleToArtisans): static
    {
        $this->profileVisibleToArtisans = $profileVisibleToArtisans;

        return $this;
    }

    public function isPhoneSharedAfterAcceptance(): bool
    {
        return $this->phoneSharedAfterAcceptance;
    }

    public function setPhoneSharedAfterAcceptance(bool $phoneSharedAfterAcceptance): static
    {
        $this->phoneSharedAfterAcceptance = $phoneSharedAfterAcceptance;

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
