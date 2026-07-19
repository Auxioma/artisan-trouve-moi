<?php

declare(strict_types=1);

namespace App\Entity\Messaging;

use App\Entity\Requests\ServiceRequest;
use App\Entity\Users\ArtisanProfile;
use App\Entity\Users\User;
use App\Repository\Messaging\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fil de discussion entre un particulier et un artisan, généralement rattaché à une demande.
 */
#[ORM\Entity(repositoryClass: ConversationRepository::class)]
#[ORM\Table(name: 'conversation')]
#[ORM\UniqueConstraint(name: 'uniq_conversation_participants_request', columns: ['client_id', 'artisan_profile_id', 'service_request_id'])]
#[ORM\HasLifecycleCallbacks]
class Conversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $client = null;

    #[ORM\ManyToOne(targetEntity: ArtisanProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\ManyToOne(targetEntity: ServiceRequest::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ServiceRequest $serviceRequest = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastMessageAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $clientReadAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $artisanReadAt = null;

    #[ORM\Column]
    private bool $isArchivedByClient = false;

    #[ORM\Column]
    private bool $isArchivedByArtisan = false;

    /** @var Collection<int, Message> */
    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: Message::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->messages = new ArrayCollection();
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

    public function getServiceRequest(): ?ServiceRequest
    {
        return $this->serviceRequest;
    }

    public function setServiceRequest(?ServiceRequest $serviceRequest): static
    {
        $this->serviceRequest = $serviceRequest;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getLastMessageAt(): ?\DateTimeImmutable
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(?\DateTimeImmutable $lastMessageAt): static
    {
        $this->lastMessageAt = $lastMessageAt;

        return $this;
    }

    public function getClientReadAt(): ?\DateTimeImmutable
    {
        return $this->clientReadAt;
    }

    public function setClientReadAt(?\DateTimeImmutable $clientReadAt): static
    {
        $this->clientReadAt = $clientReadAt;

        return $this;
    }

    public function getArtisanReadAt(): ?\DateTimeImmutable
    {
        return $this->artisanReadAt;
    }

    public function setArtisanReadAt(?\DateTimeImmutable $artisanReadAt): static
    {
        $this->artisanReadAt = $artisanReadAt;

        return $this;
    }

    public function isArchivedByClient(): bool
    {
        return $this->isArchivedByClient;
    }

    public function setIsArchivedByClient(bool $isArchivedByClient): static
    {
        $this->isArchivedByClient = $isArchivedByClient;

        return $this;
    }

    public function isArchivedByArtisan(): bool
    {
        return $this->isArchivedByArtisan;
    }

    public function setIsArchivedByArtisan(bool $isArchivedByArtisan): static
    {
        $this->isArchivedByArtisan = $isArchivedByArtisan;

        return $this;
    }

    /** @return Collection<int, Message> */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        $this->messages->removeElement($message);

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
