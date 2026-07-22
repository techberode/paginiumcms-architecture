<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Support\JsonHelper;

class UserRepository
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private string $storagePath;
    private ?EncryptionService $encryption;

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        string $storagePath = 'data/users',
        ?EncryptionService $encryption = null
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->storagePath = $storagePath;
        $this->encryption = $encryption;
    }

    public function findByEmail(string $email): ?User
    {
        $files = $this->getAllUserFiles();

        foreach ($files as $file) {
            try {
                $content = $this->reader->read($this->storagePath . '/' . basename($file));
                $data = json_decode($content, true);

                if (isset($data['email']) && $data['email'] === $email) {
                    return $this->hydrate($data);
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return null;
    }

    public function findById(string $id): ?User
    {
        $files = $this->getAllUserFiles();

        foreach ($files as $file) {
            try {
                $content = $this->reader->read($this->storagePath . '/' . basename($file));
                $data = json_decode($content, true);

                if (isset($data['id']) && $data['id'] === $id) {
                    return $this->hydrate($data);
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return null;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function findAll(): array
    {
        $users = [];
        $files = $this->getAllUserFiles();

        foreach ($files as $file) {
            try {
                $content = $this->reader->read($this->storagePath . '/' . basename($file));
                $data = json_decode($content, true);
                if (!is_array($data) || !isset($data['id'], $data['email'])) {
                    continue;
                }
                if (!is_string($data['id']) || !is_string($data['email']) || $data['id'] === '' || $data['email'] === '') {
                    continue;
                }
                $users[] = $this->hydrate($data);
            } catch (FlatFileException) {
                continue;
            }
        }

        return $this->dedupeById($users);
    }

    public function save(User $user): void
    {
        $id = $user->getId();

        if (empty($id)) {
            throw new \RuntimeException('Používateľ nemá nastavené ID');
        }

        $data = $this->extract($user);
        $path = $this->storagePath . '/' . $id . '.json';
        $this->writer->write($path, JsonHelper::encode($data, JSON_PRETTY_PRINT));
    }

    public function exists(string $id): bool
    {
        return $this->findById($id) !== null;
    }

    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function existsByUsername(string $username, ?string $exceptUserId = null): bool
    {
        $needle = strtolower(trim($username));
        foreach ($this->findAll() as $user) {
            if ($exceptUserId !== null && $user->getId() === $exceptUserId) {
                continue;
            }
            if ($user->getUsername() === $needle) {
                return true;
            }
        }

        return false;
    }

    public function delete(string $id): void
    {
        $path = $this->storagePath . '/' . $id . '.json';
        try {
            $this->writer->delete($path);
        } catch (FlatFileException) {
            // Súbor už neexistuje, ignorujeme
        }
    }

    /**
     * @param list<User> $users
     * @return list<User>
     */
    private function dedupeById(array $users): array
    {
        $byId = [];
        foreach ($users as $user) {
            $byId[$user->getId()] = $user;
        }

        return array_values($byId);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function getAllUserFiles(): array
    {
        try {
            $files = $this->reader->listFiles($this->storagePath, '*.json');
            $files = array_filter(
                $files,
                static fn (string $file): bool => str_ends_with($file, '.json')
                    && !str_contains($file, '.backup.')
            );

            return array_map(static fn (string $file): string => basename($file), $files);
        } catch (FlatFileException) {
            return [];
        }
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function hydrate(array $data): User
    {
        $user = new User();
        $reflection = new \ReflectionClass($user);

        foreach ($data as $key => $value) {
            if ($key === 'id') {
                $property = $reflection->getProperty('id');
                $property->setValue($user, $value);
            } elseif ($key === 'email') {
                $user->setEmail($value);
            } elseif ($key === 'passwordHash') {
                $property = $reflection->getProperty('passwordHash');
                $property->setValue($user, $value);
            } elseif ($key === 'roles') {
                $user->setRoles($value);
            } elseif ($key === 'name') {
                $user->setName($value);
            } elseif ($key === 'avatarUrl') {
                $user->setAvatarUrl(is_string($value) ? $value : null);
            } elseif ($key === 'username') {
                $user->setUsername((string) $value);
            } elseif ($key === 'active') {
                $user->setActive((bool) $value);
            } elseif ($key === 'twoFactorEnabled') {
                $user->setTwoFactorEnabled($value);
            } elseif ($key === 'twoFactorSecret') {
                // Dešifrovanie „at-rest" (audit A1). Transparentné pre plaintext
                // (staršie inštalácie) – EncryptionService vráti hodnotu bez
                // prefixu `enc:v1:` nezmenenú.
                $secret = is_string($value) ? $value : null;
                if ($secret !== null && $this->encryption !== null) {
                    $secret = $this->encryption->decryptNullable($secret);
                }
                $user->setTwoFactorSecret($secret);
            } elseif ($key === 'twoFactorVerifiedAt') {
                $user->setTwoFactorVerifiedAt($value !== null ? (int) $value : null);
            } elseif ($key === 'createdAt') {
                $property = $reflection->getProperty('createdAt');
                $property->setValue($user, $value);
            } elseif ($key === 'updatedAt') {
                $property = $reflection->getProperty('updatedAt');
                $property->setValue($user, $value);
            }
        }

        return $user;
    }

    /**
     * SHA-256 hash resetovacieho tokenu (v úložisku nikdy neukladáme plaintext).
     */
    private function hashResetToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function saveResetToken(User $user, string $token): void
    {
        $data = $this->extract($user);
        // Ukladáme iba hash – ak dôjde k úniku súborov, token nie je použiteľný.
        $data['resetTokenHash'] = $this->hashResetToken($token);
        $data['resetTokenExpires'] = time() + 86400; // 24 hodín
        unset($data['resetToken']); // migrácia zo staršieho plaintext formátu

        $path = $this->storagePath . '/' . $user->getId() . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Nepodarilo sa serializovať dáta');
        }

        $this->writer->write($path, $json);
    }

    public function findByResetToken(string $token): ?User
    {
        $files = $this->getAllUserFiles();
        $tokenHash = $this->hashResetToken($token);

        foreach ($files as $file) {
            try {
                $content = $this->reader->read($this->storagePath . '/' . basename($file));
                $data = json_decode($content, true);

                if (!is_array($data)) {
                    continue;
                }

                $storedHash = isset($data['resetTokenHash']) ? (string) $data['resetTokenHash'] : '';
                if ($storedHash === '' || !hash_equals($storedHash, $tokenHash)) {
                    continue;
                }

                if (isset($data['resetTokenExpires']) && $data['resetTokenExpires'] > time()) {
                    return $this->hydrate($data);
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return null;
    }

    public function clearResetToken(User $user): void
    {
        $data = $this->extract($user);
        unset($data['resetToken']); // staršie inštalácie
        unset($data['resetTokenHash']);
        unset($data['resetTokenExpires']);

        $path = $this->storagePath . '/' . $user->getId() . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Nepodarilo sa serializovať dáta');
        }

        $this->writer->write($path, $json);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function extract(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'passwordHash' => $user->getPasswordHash(),
            'roles' => $user->getRoles(),
            'name' => $user->getName(),
            'avatarUrl' => $user->getAvatarUrl(),
            'active' => $user->isActive(),
            'twoFactorEnabled' => $user->isTwoFactorEnabled(),
            // Šifrovanie TOTP seedu „at-rest" (audit A1). Ak EncryptionService
            // nie je nastavený alebo nemá kľúč, uloží sa plaintext (fail-open
            // rollout – aktivuje sa nastavením platného APP_KEY).
            'twoFactorSecret' => $this->encryption !== null
                ? $this->encryption->encryptNullable($user->getTwoFactorSecret())
                : $user->getTwoFactorSecret(),
            'twoFactorVerifiedAt' => $user->getTwoFactorVerifiedAt(),
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
        ];
    }
}

