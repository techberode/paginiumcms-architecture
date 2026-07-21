<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Models;

use JsonSerializable;

/**
 * Model pre používateľa systému.
 */
class User implements JsonSerializable
{
    private string $id;
    private string $email;
    private string $username = '';
    private string $passwordHash;
    /** @var array<int|string, mixed> */
    private array $roles = [];
    private string $name = '';
    private ?string $avatarUrl = null;
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
            'avatarUrl' => $this->avatarUrl,
            'roles' => $this->roles,
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
