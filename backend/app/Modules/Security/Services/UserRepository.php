<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Support\JsonHelper;

class UserRepository
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private string $storagePath;

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        string $storagePath = 'data/users'
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->storagePath = $storagePath;
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
                if ($data) {
                    $users[] = $this->hydrate($data);
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return $users;
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
     * @return array<int|string, mixed>
     */
    private function getAllUserFiles(): array
    {
        try {
            $files = $this->reader->listFiles($this->storagePath, '*.json');
            return array_map(function($file) {
                return basename($file);
            }, $files);
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
            } elseif ($key === 'twoFactorEnabled') {
                $user->setTwoFactorEnabled($value);
            } elseif ($key === 'twoFactorSecret') {
                $user->setTwoFactorSecret($value);
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

    public function saveResetToken(User $user, string $token): void
    {
        $data = $this->extract($user);
        $data['resetToken'] = $token;
        $data['resetTokenExpires'] = time() + 86400; // 24 hodín

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

        foreach ($files as $file) {
            try {
                $content = $this->reader->read($this->storagePath . '/' . basename($file));
                $data = json_decode($content, true);

                if (isset($data['resetToken']) && $data['resetToken'] === $token) {
                    if (isset($data['resetTokenExpires']) && $data['resetTokenExpires'] > time()) {
                        return $this->hydrate($data);
                    }
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
        unset($data['resetToken']);
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
            'passwordHash' => $user->getPasswordHash(),
            'roles' => $user->getRoles(),
            'name' => $user->getName(),
            'twoFactorEnabled' => $user->isTwoFactorEnabled(),
            'twoFactorSecret' => $user->getTwoFactorSecret(),
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
        ];
    }
}

