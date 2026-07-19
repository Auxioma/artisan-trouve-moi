<?php

declare(strict_types=1);

namespace App\Entity\Users;

use App\Entity\Enum\UserStatus;
use App\Entity\Enum\UserType;
use App\Repository\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_USER_EMAIL',
    columns: ['email']
)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[UniqueEntity(
    fields: ['email'],
    message: 'Un compte utilise déjà cette adresse e-mail.'
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L’adresse e-mail est obligatoire.')]
    #[Assert\Email(message: 'L’adresse e-mail renseignée n’est pas valide.')]
    #[Assert\Length(
        max: 180,
        maxMessage: 'L’adresse e-mail ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $email = null;

    /**
     * Rôles Symfony supplémentaires.
     *
     * ROLE_USER et le rôle correspondant au type de compte
     * sont automatiquement ajoutés dans getRoles().
     *
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * Mot de passe haché.
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(enumType: UserType::class)]
    private UserType $type = UserType::CUSTOMER;

    #[ORM\Column(enumType: UserStatus::class)]
    private UserStatus $status = UserStatus::PENDING;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $lastName = null;

    #[ORM\Column(length: 30, nullable: true)]
    #[Assert\Length(
        max: 30,
        maxMessage: 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s().-]{6,30}$/',
        message: 'Le numéro de téléphone renseigné n’est pas valide.'
    )]
    private ?string $phoneNumber = null;

    /**
     * Code ISO 639-1, par exemple : fr, en, de.
     */
    #[ORM\Column(length: 10)]
    #[Assert\NotBlank]
    #[Assert\Locale(message: 'La langue sélectionnée n’est pas valide.')]
    private string $locale = 'fr';

    /**
     * Code ISO 3166-1 alpha-2, par exemple : FR, BE, DE.
     */
    #[ORM\Column(length: 2)]
    #[Assert\NotBlank]
    #[Assert\Country(message: 'Le pays sélectionné n’est pas valide.')]
    private string $countryCode = 'FR';

    #[ORM\Column(length: 100)]
    #[Assert\Timezone(message: 'Le fuseau horaire sélectionné n’est pas valide.')]
    private string $timezone = 'Europe/Paris';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    #[Vich\UploadableField(mapping: 'user_avatar', fileNameProperty: 'avatarFilename')]
    private ?File $avatarFile = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private bool $isPhoneVerified = false;

    #[ORM\Column]
    private bool $hasAcceptedTerms = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $termsAcceptedAt = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $termsVersion = null;

    #[ORM\Column]
    private bool $hasAcceptedPrivacyPolicy = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $privacyPolicyAcceptedAt = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $privacyPolicyVersion = null;

    #[ORM\Column]
    private bool $marketingConsent = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $marketingConsentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $suspendedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $suspensionReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(
        mappedBy: 'user',
        targetEntity: ArtisanProfile::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?ArtisanProfile $artisanProfile = null;

    #[ORM\OneToOne(
        mappedBy: 'user',
        targetEntity: CommercialPartnerProfile::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?CommercialPartnerProfile $commercialPartnerProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?UserProfile $userProfile = null;

    #[ORM\OneToOne(
        mappedBy: 'user',
        targetEntity: UserPreferences::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?UserPreferences $preferences = null;

    /**
     * @var Collection<int, UserSession>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserSession::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $sessions;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->sessions = new ArrayCollection();
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';
        $roles[] = $this->type->securityRole();

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $normalizedRoles = [];

        foreach ($roles as $role) {
            $role = strtoupper(trim($role));

            if ('' === $role) {
                continue;
            }

            if (!str_starts_with($role, 'ROLE_')) {
                $role = 'ROLE_'.$role;
            }

            $normalizedRoles[] = $role;
        }

        $this->roles = array_values(array_unique($normalizedRoles));

        return $this;
    }

    public function addRole(string $role): static
    {
        $role = strtoupper(trim($role));

        if (!str_starts_with($role, 'ROLE_')) {
            $role = 'ROLE_'.$role;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function removeRole(string $role): static
    {
        $this->roles = array_values(
            array_filter(
                $this->roles,
                static fn (string $existingRole): bool => $existingRole !== $role
            )
        );

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Empêche le stockage direct du hash réel du mot de passe
     * dans certaines données de session sérialisées.
     */
    public function __serialize(): array
    {
        $data = (array) $this;

        $passwordKey = "\0".self::class."\0password";
        $avatarFileKey = "\0".self::class."\0avatarFile";

        if (isset($data[$passwordKey]) && is_string($data[$passwordKey])) {
            $data[$passwordKey] = hash('crc32c', $data[$passwordKey]);
        }

        /*
         * Un UploadedFile ne peut pas être sérialisé dans la session Symfony.
         * VichUploaderBundle continue de gérer avatarFile normalement pendant
         * la soumission du formulaire, mais le fichier temporaire n'est pas
         * conservé dans le token de sécurité.
         */
        unset($data[$avatarFileKey]);

        return $data;
    }

    public function eraseCredentials(): void
    {
        // Aucun mot de passe en clair n'est stocké dans cette entité.
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function setType(UserType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = trim($firstName);

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = trim($lastName);

        return $this;
    }

    public function getFullName(): string
    {
        return trim(
            sprintf(
                '%s %s',
                (string) $this->firstName,
                (string) $this->lastName
            )
        );
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = null !== $phoneNumber
            ? trim($phoneNumber)
            : null;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = mb_strtolower(trim($locale));

        return $this;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): static
    {
        $this->countryCode = strtoupper(trim($countryCode));

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = trim($timezone);

        return $this;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $avatarFilename): static
    {
        $this->avatarFilename = $avatarFilename;

        return $this;
    }

    public function getAvatarFile(): ?File
    {
        return $this->avatarFile;
    }

    public function setAvatarFile(?File $avatarFile): static
    {
        $this->avatarFile = $avatarFile;

        if (null !== $avatarFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isPhoneVerified(): bool
    {
        return $this->isPhoneVerified;
    }

    public function setIsPhoneVerified(bool $isPhoneVerified): static
    {
        $this->isPhoneVerified = $isPhoneVerified;

        return $this;
    }

    public function hasAcceptedTerms(): bool
    {
        return $this->hasAcceptedTerms;
    }

    public function setHasAcceptedTerms(bool $hasAcceptedTerms): static
    {
        $this->hasAcceptedTerms = $hasAcceptedTerms;

        if (!$hasAcceptedTerms) {
            $this->termsAcceptedAt = null;
            $this->termsVersion = null;
        }

        return $this;
    }

    public function acceptTerms(string $version): static
    {
        $this->hasAcceptedTerms = true;
        $this->termsVersion = trim($version);
        $this->termsAcceptedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getTermsAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->termsAcceptedAt;
    }

    public function setTermsAcceptedAt(
        ?\DateTimeImmutable $termsAcceptedAt,
    ): static {
        $this->termsAcceptedAt = $termsAcceptedAt;

        return $this;
    }

    public function getTermsVersion(): ?string
    {
        return $this->termsVersion;
    }

    public function setTermsVersion(?string $termsVersion): static
    {
        $this->termsVersion = $termsVersion;

        return $this;
    }

    public function hasAcceptedPrivacyPolicy(): bool
    {
        return $this->hasAcceptedPrivacyPolicy;
    }

    public function setHasAcceptedPrivacyPolicy(
        bool $hasAcceptedPrivacyPolicy,
    ): static {
        $this->hasAcceptedPrivacyPolicy = $hasAcceptedPrivacyPolicy;

        if (!$hasAcceptedPrivacyPolicy) {
            $this->privacyPolicyAcceptedAt = null;
            $this->privacyPolicyVersion = null;
        }

        return $this;
    }

    public function acceptPrivacyPolicy(string $version): static
    {
        $this->hasAcceptedPrivacyPolicy = true;
        $this->privacyPolicyVersion = trim($version);
        $this->privacyPolicyAcceptedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPrivacyPolicyAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->privacyPolicyAcceptedAt;
    }

    public function setPrivacyPolicyAcceptedAt(
        ?\DateTimeImmutable $privacyPolicyAcceptedAt,
    ): static {
        $this->privacyPolicyAcceptedAt = $privacyPolicyAcceptedAt;

        return $this;
    }

    public function getPrivacyPolicyVersion(): ?string
    {
        return $this->privacyPolicyVersion;
    }

    public function setPrivacyPolicyVersion(
        ?string $privacyPolicyVersion,
    ): static {
        $this->privacyPolicyVersion = $privacyPolicyVersion;

        return $this;
    }

    public function hasMarketingConsent(): bool
    {
        return $this->marketingConsent;
    }

    public function setMarketingConsent(bool $marketingConsent): static
    {
        $this->marketingConsent = $marketingConsent;
        $this->marketingConsentAt = $marketingConsent
            ? new \DateTimeImmutable()
            : null;

        return $this;
    }

    public function getMarketingConsentAt(): ?\DateTimeImmutable
    {
        return $this->marketingConsentAt;
    }

    public function setMarketingConsentAt(
        ?\DateTimeImmutable $marketingConsentAt,
    ): static {
        $this->marketingConsentAt = $marketingConsentAt;

        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(
        ?\DateTimeImmutable $lastLoginAt,
    ): static {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getSuspendedAt(): ?\DateTimeImmutable
    {
        return $this->suspendedAt;
    }

    public function getSuspensionReason(): ?string
    {
        return $this->suspensionReason;
    }

    public function suspend(?string $reason = null): static
    {
        $this->status = UserStatus::SUSPENDED;
        $this->suspendedAt = new \DateTimeImmutable();
        $this->suspensionReason = $reason;

        return $this;
    }

    public function reactivate(): static
    {
        $this->status = UserStatus::ACTIVE;
        $this->suspendedAt = null;
        $this->suspensionReason = null;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function softDelete(): static
    {
        $this->status = UserStatus::DELETED;
        $this->deletedAt = new \DateTimeImmutable();

        return $this;
    }

    public function restore(): static
    {
        $this->status = UserStatus::ACTIVE;
        $this->deletedAt = null;

        return $this;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function canLogin(): bool
    {
        return $this->isVerified
            && !$this->isDeleted()
            && $this->status->canLogin();
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

    public function getArtisanProfile(): ?ArtisanProfile
    {
        return $this->artisanProfile;
    }

    public function setArtisanProfile(
        ?ArtisanProfile $artisanProfile,
    ): static {
        if (
            null !== $artisanProfile
            && $artisanProfile->getUser() !== $this
        ) {
            $artisanProfile->setUser($this);
        }

        $this->artisanProfile = $artisanProfile;

        return $this;
    }

    public function getCommercialPartnerProfile(): ?CommercialPartnerProfile
    {
        return $this->commercialPartnerProfile;
    }

    public function setCommercialPartnerProfile(
        ?CommercialPartnerProfile $commercialPartnerProfile,
    ): static {
        if (
            null !== $commercialPartnerProfile
            && $commercialPartnerProfile->getUser() !== $this
        ) {
            $commercialPartnerProfile->setUser($this);
        }

        $this->commercialPartnerProfile = $commercialPartnerProfile;

        return $this;
    }

    public function getUserProfile(): ?UserProfile
    {
        return $this->userProfile;
    }

    public function setUserProfile(?UserProfile $userProfile): static
    {
        // unset the owning side of the relation if necessary
        if (null === $userProfile && null !== $this->userProfile) {
            $this->userProfile->setUser(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $userProfile && $userProfile->getUser() !== $this) {
            $userProfile->setUser($this);
        }

        $this->userProfile = $userProfile;

        return $this;
    }

    public function getPreferences(): ?UserPreferences
    {
        return $this->preferences;
    }

    public function setPreferences(?UserPreferences $preferences): static
    {
        if (null !== $preferences && $preferences->getUser() !== $this) {
            $preferences->setUser($this);
        }

        $this->preferences = $preferences;

        return $this;
    }

    /**
     * Garantit qu'un objet de préférences existe (valeurs par défaut).
     */
    public function getOrCreatePreferences(): UserPreferences
    {
        if (null === $this->preferences) {
            $this->setPreferences(new UserPreferences());
        }

        return $this->preferences;
    }

    /**
     * @return Collection<int, UserSession>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(UserSession $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setUser($this);
        }

        return $this;
    }

    public function removeSession(UserSession $session): static
    {
        $this->sessions->removeElement($session);

        return $this;
    }
}
