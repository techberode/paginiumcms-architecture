<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Models;

use JsonSerializable;

/**
 * Model pre používateľa systému.
 */
class User implements JsonSerializable
{
    /** @var list<string> */
    public const DESK_BUBBLE_ANCHORS = [
        'top-left',
        'top',
        'top-right',
        'left',
        'center',
        'right',
        'bottom-left',
        'bottom',
        'bottom-right',
        'custom',
    ];

    private string $id;
    private string $email;
    private string $username = '';
    private string $passwordHash;
    /** @var array<int|string, mixed> */
    private array $roles = [];
    private string $name = '';
    private string $bio = '';
    private string $jobTitle = '';
    private string $phone = '';
    private string $timezone = '';
    private string $locale = '';
    private bool $notifyFailedLogin = true;
    private bool $notifySecurityIncident = true;
    /** @var array{street: string, city: string, postal: string, country: string} */
    private array $address = ['street' => '', 'city' => '', 'postal' => '', 'country' => ''];
    /** @var list<array{id: string, org: string, role: string, years: string}> */
    private array $experience = [];
    /** @var list<array{id: string, school: string, field: string, years: string}> */
    private array $education = [];
    /** @var list<array{id: string, platform: string, url: string, label: string, directChat: bool, notify: bool, verifiedAt?: int}> */
    private array $socialAccounts = [];
    /** @var array{address: bool, experience: bool, education: bool, phone: bool, email: bool, socials: bool, contact: bool, support: bool} */
    private array $publish = [
        'address' => false,
        'experience' => false,
        'education' => false,
        'phone' => false,
        'email' => false,
        'socials' => false,
        'contact' => false,
        'support' => false,
    ];
    private ?string $avatarUrl = null;
    private bool $chatEnabled = false;
    private bool $deskMailEnabled = false;
    private bool $deskBubbleEnabled = true;
    private string $deskBubbleAnchor = 'right';
    private int $deskBubbleX = 92;
    private int $deskBubbleY = 50;
    private string $registrationOptionId = '';
    private bool $active = true;
    private bool $twoFactorEnabled = false;
    private ?string $twoFactorSecret = null;
    private ?int $twoFactorVerifiedAt = null;
    private int $createdAt;
    private int $updatedAt;

    public function __construct()
    {
        $this->id = uniqid('user_', true);
        $this->createdAt = time();
        $this->updatedAt = time();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): string
    {
        if ($this->username !== '') {
            return $this->username;
        }

        $parts = explode('@', $this->email);
        $local = $parts[0];

        return strtolower((string) preg_replace('/[^a-z0-9_-]/', '', $local) ?: 'user');
    }

    public function setUsername(string $username): self
    {
        $this->username = strtolower(trim($username));
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getRegistrationOptionId(): string
    {
        return $this->registrationOptionId;
    }

    public function setRegistrationOptionId(string $optionId): self
    {
        $this->registrationOptionId = trim($optionId);
        return $this;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->passwordHash);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array<int|string, mixed> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function removeRole(string $role): self
    {
        $this->roles = array_values(array_filter($this->roles, fn($r) => $r !== $role));
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getBio(): string
    {
        return $this->bio;
    }

    public function setBio(string $bio): self
    {
        $this->bio = trim($bio);

        return $this;
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(string $jobTitle): self
    {
        $this->jobTitle = trim($jobTitle);

        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = trim($phone);

        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = trim($timezone);

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = strtolower(trim($locale));

        return $this;
    }

    public function getNotifyFailedLogin(): bool
    {
        return $this->notifyFailedLogin;
    }

    public function setNotifyFailedLogin(bool $enabled): self
    {
        $this->notifyFailedLogin = $enabled;

        return $this;
    }

    public function getNotifySecurityIncident(): bool
    {
        return $this->notifySecurityIncident;
    }

    public function setNotifySecurityIncident(bool $enabled): self
    {
        $this->notifySecurityIncident = $enabled;

        return $this;
    }

    /**
     * @return array{street: string, city: string, postal: string, country: string}
     */
    public function getAddress(): array
    {
        return $this->address;
    }

    /**
     * @param array{street: string, city: string, postal: string, country: string} $address
     */
    public function setAddress(array $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return list<array{id: string, org: string, role: string, years: string}>
     */
    public function getExperience(): array
    {
        return $this->experience;
    }

    /**
     * @param list<array{id: string, org: string, role: string, years: string}> $experience
     */
    public function setExperience(array $experience): self
    {
        $this->experience = $experience;

        return $this;
    }

    /**
     * @return list<array{id: string, school: string, field: string, years: string}>
     */
    public function getEducation(): array
    {
        return $this->education;
    }

    /**
     * @param list<array{id: string, school: string, field: string, years: string}> $education
     */
    public function setEducation(array $education): self
    {
        $this->education = $education;

        return $this;
    }

    /**
     * @return list<array{id: string, platform: string, url: string, label: string, directChat: bool, notify: bool, verifiedAt?: int}>
     */
    public function getSocialAccounts(): array
    {
        return $this->socialAccounts;
    }

    /**
     * @param list<array{id: string, platform: string, url: string, label: string, directChat: bool, notify: bool, verifiedAt?: int}> $socialAccounts
     */
    public function setSocialAccounts(array $socialAccounts): self
    {
        $this->socialAccounts = $socialAccounts;

        return $this;
    }

    /**
     * @return array{address: bool, experience: bool, education: bool, phone: bool, email: bool, socials: bool, contact: bool, support: bool}
     */
    public function getPublish(): array
    {
        return $this->publish;
    }

    /**
     * @param array{address: bool, experience: bool, education: bool, phone: bool, email: bool, socials: bool, contact: bool, support: bool} $publish
     */
    public function setPublish(array $publish): self
    {
        $this->publish = $publish;

        return $this;
    }

    public function isChatEnabled(): bool
    {
        return $this->chatEnabled;
    }

    public function setChatEnabled(bool $chatEnabled): self
    {
        $this->chatEnabled = $chatEnabled;

        return $this;
    }

    public function isDeskMailEnabled(): bool
    {
        return $this->deskMailEnabled;
    }

    public function setDeskMailEnabled(bool $enabled): self
    {
        $this->deskMailEnabled = $enabled;

        return $this;
    }

    public function isDeskBubbleEnabled(): bool
    {
        return $this->deskBubbleEnabled;
    }

    public function setDeskBubbleEnabled(bool $enabled): self
    {
        $this->deskBubbleEnabled = $enabled;

        return $this;
    }

    public function getDeskBubbleAnchor(): string
    {
        return $this->deskBubbleAnchor;
    }

    public function setDeskBubbleAnchor(string $anchor): self
    {
        $anchor = strtolower(trim($anchor));
        $this->deskBubbleAnchor = in_array($anchor, self::DESK_BUBBLE_ANCHORS, true) ? $anchor : 'right';

        return $this;
    }

    public function getDeskBubbleX(): int
    {
        return $this->deskBubbleX;
    }

    public function setDeskBubbleX(int $x): self
    {
        $this->deskBubbleX = max(0, min(100, $x));

        return $this;
    }

    public function getDeskBubbleY(): int
    {
        return $this->deskBubbleY;
    }

    public function setDeskBubbleY(int $y): self
    {
        $this->deskBubbleY = max(0, min(100, $y));

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl !== null && trim($avatarUrl) !== '' ? trim($avatarUrl) : null;

        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $enabled): self
    {
        $this->twoFactorEnabled = $enabled;
        return $this;
    }

    public function getTwoFactorSecret(): ?string
    {
        return $this->twoFactorSecret;
    }

    public function setTwoFactorSecret(?string $secret): self
    {
        $this->twoFactorSecret = $secret;
        return $this;
    }

    public function getTwoFactorVerifiedAt(): ?int
    {
        return $this->twoFactorVerifiedAt;
    }

    public function setTwoFactorVerifiedAt(?int $timestamp): self
    {
        $this->twoFactorVerifiedAt = $timestamp;
        return $this;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(int $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SUPER_ADMIN');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN') || $this->isSuperAdmin();
    }

    public function isEditor(): bool
    {
        return $this->hasRole('EDITOR') || $this->isAdmin();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->getUsername(),
            'name' => $this->name,
            'bio' => $this->bio,
            'jobTitle' => $this->jobTitle,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'notifyFailedLogin' => $this->notifyFailedLogin,
            'notifySecurityIncident' => $this->notifySecurityIncident,
            'address' => $this->address,
            'experience' => $this->experience,
            'education' => $this->education,
            'socialAccounts' => $this->socialAccounts,
            'publish' => $this->publish,
            'chatEnabled' => $this->chatEnabled,
            'deskMailEnabled' => $this->deskMailEnabled,
            'deskBubbleEnabled' => $this->deskBubbleEnabled,
            'deskBubbleAnchor' => $this->deskBubbleAnchor,
            'deskBubbleX' => $this->deskBubbleX,
            'deskBubbleY' => $this->deskBubbleY,
            'avatarUrl' => $this->avatarUrl,
            'roles' => $this->roles,
            'registrationOptionId' => $this->registrationOptionId,
            'active' => $this->active,
            'twoFactorEnabled' => $this->twoFactorEnabled,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    /**
     * Admin detail view – includes optional 2FA secret for support.
     *
     * @return array<string, mixed>
     */
    public function toAdminDetail(bool $includeSecret = false): array
    {
        $data = $this->jsonSerialize();
        $data['twoFactorVerifiedAt'] = $this->twoFactorVerifiedAt;

        if ($includeSecret && $this->twoFactorSecret !== null && $this->twoFactorSecret !== '') {
            $data['twoFactorSecret'] = $this->twoFactorSecret;
        }

        return $data;
    }
}
