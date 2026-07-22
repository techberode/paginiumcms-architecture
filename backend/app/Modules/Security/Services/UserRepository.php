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
    private UserIndexService $index;

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        string $storagePath = 'data/users',
        ?EncryptionService $encryption = null,
        ?UserIndexService $index = null
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->storagePath = $storagePath;
        $this->encryption = $encryption;
        $this->index = $index ?? new UserIndexService($reader);
    }

    public function findByEmail(string $email): ?User
    {
        $this->ensureIndex();

        $id = $this->index->resolveIdByEmail($email);
        if ($id === null) {
            return null;
        }

        return $this->readUserById($id);
    }

    public function findById(string $id): ?User
    {
        $this->ensureIndex();

        if (!$this->index->hasId($id) && !$this->userFileExists($id)) {
            return null;
        }

        return $this->readUserById($id);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function findAll(): array
    {
        $this->ensureIndex();

        $users = [];
        foreach ($this->index->listIds() as $id) {
            $user = $this->readUserById($id);
            if ($user === null) {
                continue;
            }
            $users[] = $user;
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
        $this->syncIndexFromDisk($id);
    }

    public function exists(string $id): bool
    {
        $this->ensureIndex();

        return $this->index->hasId($id) || $this->userFileExists($id);
    }

    public function existsByEmail(string $email): bool
    {
        $this->ensureIndex();

        return $this->index->resolveIdByEmail($email) !== null;
    }

    public function existsByUsername(string $username, ?string $exceptUserId = null): bool
    {
        $this->ensureIndex();

        $id = $this->index->resolveIdByUsername($username);
        if ($id === null) {
            return false;
        }

        return $exceptUserId === null || $id !== $exceptUserId;
    }

    public function delete(string $id): void
    {
        $path = $this->storagePath . '/' . $id . '.json';
        try {
            $this->writer->delete($path);
        } catch (FlatFileException) {
            // Súbor už neexistuje, ignorujeme
        }

        $this->index->remove($id);
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
        $this->syncIndexFromDisk($user->getId());
    }

    public function findByResetToken(string $token): ?User
    {
        $this->ensureIndex();

        $tokenHash = $this->hashResetToken($token);
        $id = $this->index->resolveIdByResetTokenHash($tokenHash, time());
        if ($id === null) {
            return null;
        }

        return $this->readUserById($id);
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
        $this->syncIndexFromDisk($user->getId());
    }

    private function ensureIndex(): void
    {
        $this->index->ensureBuilt($this->storagePath);
    }

    private function userFileExists(string $id): bool
    {
        return $this->reader->exists($this->storagePath . '/' . $id . '.json');
    }

    private function readUserById(string $id): ?User
    {
        try {
            $content = $this->reader->read($this->storagePath . '/' . $id . '.json');
            $data = json_decode($content, true);
            if (!is_array($data)) {
                return null;
            }

            return $this->hydrate($data);
        } catch (FlatFileException) {
            return null;
        }
    }

    private function syncIndexFromDisk(string $id): void
    {
        try {
            $content = $this->reader->read($this->storagePath . '/' . $id . '.json');
            $data = json_decode($content, true);
            if (!is_array($data)) {
                $this->index->remove($id);

                return;
            }

            $this->index->upsertFromRaw($data);
        } catch (FlatFileException) {
            $this->index->remove($id);
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
