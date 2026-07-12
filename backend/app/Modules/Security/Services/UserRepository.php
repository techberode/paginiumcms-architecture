<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

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

    public function save(User $user): void
    {
        $id = $user->getId();
    
    // Kontrola, či ID nie je prázdne
    if (empty($id)) {
        throw new \RuntimeException('Používateľ nemá nastavené ID');
    }
    
    $data = $this->extract($user);
    $path = $this->storagePath . '/' . $id . '.json';

//        $data = $this->extract($user);
//        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \RuntimeException('Nepodarilo sa serializovať dáta používateľa');
        }

        // UTF-8 normalizácia
        $json = utf8_normalize($json);

        // Skúsime zapísať cez FileWriter
        try {
            $this->writer->write($path, json_encode($data, JSON_PRETTY_PRINT));
        } catch (FlatFileException $e) {
            // Ak zlyhá, skúsime vytvoriť adresár a zapísať priamo
            $basePath = $this->reader->getBasePath();
            $fullPath = $basePath . '/' . $this->storagePath;
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
            }
            $this->writer->write($path, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    public function saveResetToken(User $user, string $token): void
    {
        $data = $this->extract($user);
        $data['resetToken'] = $token;
        $data['resetTokenExpires'] = time() + 86400;

        $path = $this->storagePath . '/' . $user->getId() . '.json';
        $this->writer->write($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function clearResetToken(User $user): void
    {
        $data = $this->extract($user);
        unset($data['resetToken']);
        unset($data['resetTokenExpires']);

        $path = $this->storagePath . '/' . $user->getId() . '.json';
        $this->writer->write($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function getAllUserFiles(): array
    {
        try {
            $files = $this->reader->listFiles($this->storagePath, '*.json');
            // Vrátime len názvy súborov
            return array_map(function($file) {
                return basename($file);
            }, $files);
        } catch (FlatFileException) {
            return [];
        }
    }

    private function hydrate(array $data): User
    {
        $user = new User();
        $reflection = new \ReflectionClass($user);

        foreach ($data as $key => $value) {
            if ($key === 'id') {
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($user, $value);
            } elseif ($key === 'email') {
                $user->setEmail($value);
            } elseif ($key === 'passwordHash') {
                $property = $reflection->getProperty('passwordHash');
                $property->setAccessible(true);
                $property->setValue($user, $value);
            } elseif ($key === 'roles') {
                $user->setRoles($value);
            } elseif ($key === 'name') {
                $user->setName($value);
            } elseif ($key === 'twoFactorEnabled') {
                $user->setTwoFactorEnabled($value);
            } elseif ($key === 'twoFactorSecret') {
                $user->setTwoFactorSecret($value);
            } elseif ($key === 'twoFactorVerifiedAt') {
                $user->setTwoFactorVerifiedAt($value);
            } elseif ($key === 'createdAt') {
                $property = $reflection->getProperty('createdAt');
                $property->setAccessible(true);
                $property->setValue($user, $value);
            } elseif ($key === 'updatedAt') {
                $property = $reflection->getProperty('updatedAt');
                $property->setAccessible(true);
                $property->setValue($user, $value);
            }
        }

        return $user;
    }

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
            'twoFactorVerifiedAt' => $user->getTwoFactorVerifiedAt(),
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
        ];
    }
}
