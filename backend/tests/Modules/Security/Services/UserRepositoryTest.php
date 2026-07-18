<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Support\FileHelper;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;
    private string $root;
    private FileWriter $writer;
    private FileReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $structure = [
            'data' => [
                'users' => [],
            ],
        ];

        $root = vfsStream::setup('storage', null, $structure);
        $this->root = vfsStream::url('storage');

        $validator = new FileValidator($this->root . '/data');
        $this->reader = new FileReader($validator);
        $this->writer = new FileWriter($validator);

        $this->repository = new UserRepository($this->reader, $this->writer, 'users');
    }

    /**
     * @param array<int|string, mixed> $data
     */
    private function createUserFile(string $id, array $data): void
    {
        $filePath = $this->root . '/data/users/' . $id . '.json';
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function testSaveAndFindByEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('StrongP@ssw0rd123!');
        $user->setName('Test User');
        $user->setRoles(['USER']);

        $this->repository->save($user);

        // Priamo skontrolujeme, či súbor existuje
        $filePath = $this->root . '/data/users/' . $user->getId() . '.json';
        $this->assertFileExists($filePath, 'Súbor nebol vytvorený');

        // Skúsime nájsť používateľa
        $found = $this->repository->findByEmail('test@example.com');
        $this->assertNotNull($found, 'Používateľ sa nenašiel podľa emailu');
        $this->assertEquals('test@example.com', $found->getEmail());
        $this->assertEquals('Test User', $found->getName());
        $this->assertEquals(['USER'], $found->getRoles());
        $this->assertTrue($found->verifyPassword('StrongP@ssw0rd123!'));
    }

    public function testFindByEmailNonExistent(): void
    {
        $found = $this->repository->findByEmail('nonexistent@example.com');
        $this->assertNull($found);
    }

    public function testSaveAndFindByResetToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('StrongP@ssw0rd123!');
        $user->setName('Test User');

        $this->repository->save($user);

        $token = 'reset_token_1234567890abcdef';
        $this->repository->saveResetToken($user, $token);

        // Súbor smie obsahovať iba hash tokenu, nikdy plaintext.
        $filePath = $this->root . '/data/users/' . $user->getId() . '.json';
        $this->assertFileExists($filePath);
        $userData = FileHelper::readJson($filePath);
        $this->assertArrayNotHasKey('resetToken', $userData, 'Plaintext token nesmie byť v súbore');
        $this->assertSame(
            hash('sha256', $token),
            $userData['resetTokenHash'] ?? null,
            'Hash tokenu nebol uložený v súbore'
        );

        $found = $this->repository->findByResetToken($token);
        $this->assertNotNull($found, 'Používateľ sa nenašiel podľa reset tokenu');
        $this->assertEquals('test@example.com', $found->getEmail());
    }

    public function testClearResetToken(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('StrongP@ssw0rd123!');

        $this->repository->save($user);

        $token = 'reset_token_1234567890abcdef';
        $this->repository->saveResetToken($user, $token);

        // Overenie, že token existuje
        $found = $this->repository->findByResetToken($token);
        $this->assertNotNull($found, 'Token nebol uložený');

        // Vymazanie tokenu
        $this->repository->clearResetToken($user);

        // Overenie, že token bol vymazaný
        $found = $this->repository->findByResetToken($token);
        $this->assertNull($found, 'Token nebol vymazaný');

        // Overenie, že token nie je v súbore
        $filePath = $this->root . '/data/users/' . $user->getId() . '.json';
        $userData = FileHelper::readJson($filePath);
        $this->assertArrayNotHasKey('resetToken', $userData);
        $this->assertArrayNotHasKey('resetTokenHash', $userData);
        $this->assertArrayNotHasKey('resetTokenExpires', $userData);
    }

    public function testFindByResetTokenExpired(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('StrongP@ssw0rd123!');

        $this->repository->save($user);

        // Uložíme token s expiráciou v minulosti
        $token = 'expired_token_1234567890abcdef';
        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'passwordHash' => $user->getPasswordHash(),
            'roles' => $user->getRoles(),
            'name' => $user->getName(),
            'resetTokenHash' => hash('sha256', $token),
            'resetTokenExpires' => time() - 3600, // 1 hodina dozadu
            'twoFactorEnabled' => false,
            'twoFactorSecret' => null,
            'twoFactorVerifiedAt' => null,
            'createdAt' => $user->getCreatedAt(),
            'updatedAt' => $user->getUpdatedAt(),
        ];

        $this->createUserFile($user->getId(), $data);

        $found = $this->repository->findByResetToken($token);
        $this->assertNull($found, 'Expirovaný token by nemal byť nájdený');
    }

    public function testUpdateUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('StrongP@ssw0rd123!');
        $user->setName('Test User');

        $this->repository->save($user);

        // Aktualizácia
        $user->setName('Updated Name');
        $user->setRoles(['ADMIN']);
        $this->repository->save($user);

        $found = $this->repository->findByEmail('test@example.com');
        $this->assertNotNull($found, 'Používateľ sa nenašiel po aktualizácii');
        $this->assertEquals('Updated Name', $found->getName());
        $this->assertEquals(['ADMIN'], $found->getRoles());
    }

    public function testFindAllIgnoresBackupFilesAndInvalidRecords(): void
    {
        $this->createUserFile('user_real', [
            'id' => 'user_real',
            'email' => 'real@example.com',
            'passwordHash' => password_hash('StrongP@ssw0rd123!', PASSWORD_ARGON2ID),
            'roles' => ['USER'],
            'name' => 'Real User',
            'twoFactorEnabled' => false,
            'twoFactorSecret' => null,
            'createdAt' => time(),
            'updatedAt' => time(),
        ]);

        file_put_contents(
            $this->root . '/data/users/user_real.json.backup.20260718_120000',
            json_encode(['id' => 'user_backup', 'email' => 'backup@example.com'])
        );

        $this->createUserFile('user_invalid', [
            'name' => 'No id or email',
        ]);

        $all = $this->repository->findAll();

        $this->assertCount(1, $all);
        $this->assertSame('real@example.com', $all[0]->getEmail());
    }
}
