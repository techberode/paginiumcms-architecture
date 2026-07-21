<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Locking;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Locking\Exception\LockConflictException;
use PaginiumCMS\Core\Locking\Services\LockManager;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Testy systému zamykania obsahu (Iterácia 1).
 *
 * Používa REÁLNY dočasný adresár, pretože LockManager sa spolieha na fopen('c+') + flock,
 * ktoré vfsStream spoľahlivo nepodporuje.
 */
class LockManagerTest extends TestCase
{
    private string $baseDir;
    private FileReader $reader;
    private FileWriter $writer;
    private LockManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDir = sys_get_temp_dir() . '/pag_lock_test_' . uniqid();
        mkdir($this->baseDir, 0777, true);

        $validator = new FileValidator($this->baseDir);
        $this->reader = new FileReader($validator);
        $this->writer = new FileWriter($validator);

        // Predvolený manažér s pohodlným TTL; krátky TTL testujeme cez makeManager(1).
        $this->manager = $this->makeManager(300);
    }

    private function makeManager(int $ttl): LockManager
    {
        return new LockManager($this->reader, $this->writer, $this->createLogger(), 'data/locks.json', $ttl);
    }

    protected function tearDown(): void
    {
        // Rekurzívne upratanie dočasného adresára.
        $this->removeDir($this->baseDir);
        parent::tearDown();
    }

    public function testAcquireCreatesLock(): void
    {
        $lock = $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));

        $this->assertSame('page:o-nas', $lock->getResourceId());
        $this->assertSame('user_1', $lock->getLockedBy());
        $this->assertNotEmpty($lock->getToken());
        $this->assertCount(1, $this->manager->getAllLocks());
    }

    public function testAcquireByAnotherUserConflicts(): void
    {
        $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));

        $this->expectException(LockConflictException::class);
        $this->manager->acquire('page:o-nas', $this->user('user_2', 'Jana'));
    }

    public function testSameUserCanReacquire(): void
    {
        $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));
        $lock = $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));

        $this->assertSame('user_1', $lock->getLockedBy());
        $this->assertCount(1, $this->manager->getAllLocks());
    }

    public function testHeartbeatExtendsLock(): void
    {
        $lock = $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));
        $original = $lock->getExpiresAt();

        sleep(1);
        $refreshed = $this->manager->heartbeat('page:o-nas', $lock->getToken());

        // Po heartbeate musí byť expirácia posunutá do budúcnosti (>= pôvodná).
        $this->assertGreaterThanOrEqual($original, $refreshed->getExpiresAt());
        $this->assertGreaterThan($lock->getLastHeartbeat(), $refreshed->getLastHeartbeat());
    }

    public function testHeartbeatWithWrongTokenFails(): void
    {
        $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));

        $this->expectException(LockConflictException::class);
        $this->manager->heartbeat('page:o-nas', 'zly-token');
    }

    public function testReleaseRemovesLock(): void
    {
        $lock = $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));
        $this->manager->release('page:o-nas', $lock->getToken());

        $this->assertCount(0, $this->manager->getAllLocks());
    }

    public function testReleaseWithWrongTokenKeepsLock(): void
    {
        $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));
        $this->manager->release('page:o-nas', 'zly-token');

        $this->assertCount(1, $this->manager->getAllLocks(), 'Cudzí token nesmie uvoľniť zámok');
    }

    public function testAutoReleaseAfterTtl(): void
    {
        // Krátky TTL 1 s pre rýchly test auto-release.
        $shortManager = $this->makeManager(1);
        $shortManager->acquire('page:o-nas', $this->user('user_1', 'Ján'));

        // Po 2 s musí byť zámok automaticky uvoľnený.
        sleep(2);

        $this->assertCount(0, $shortManager->getAllLocks(), 'Expirovaný zámok sa má auto-uvoľniť');

        // A iný používateľ ho teraz môže získať bez konfliktu.
        $lock = $shortManager->acquire('page:o-nas', $this->user('user_2', 'Jana'));
        $this->assertSame('user_2', $lock->getLockedBy());
    }

    public function testForceReleaseRemovesLock(): void
    {
        $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));
        $this->manager->forceRelease('page:o-nas');

        $this->assertCount(0, $this->manager->getAllLocks());
    }

    public function testTokenIsHiddenInApiSerialization(): void
    {
        $lock = $this->manager->acquire('page:o-nas', $this->user('user_1', 'Ján'));

        $serialized = json_encode($lock->jsonSerialize());
        $this->assertIsString($serialized);
        $this->assertStringNotContainsString('token', $serialized, 'Token sa nesmie posielať v API odpovedi');
        $this->assertStringContainsString('token', json_encode($lock->toArray()) ?: '', 'Token sa ukladá na disk');
    }

    // === Pomocné ===

    private function user(string $id, string $name): User
    {
        $user = new User();
        // setAccessible netreba (PHP 8.1+): reflexia súkromnej vlastnosti funguje priamo.
        $reflection = new \ReflectionProperty($user, 'id');
        $reflection->setValue($user, $id);
        $user->setName($name)->setEmail($id . '@test.sk');

        return $user;
    }

    private function createLogger(): LoggerInterface
    {
        return new class implements LoggerInterface {
            public function info(string $message, array $context = []): void
            {
            }

            public function warning(string $message, array $context = []): void
            {
            }

            public function error(string $message, array $context = []): void
            {
            }

            public function critical(string $message, array $context = []): void
            {
            }

            public function debug(string $message, array $context = []): void
            {
            }

            public function log(string $severity, string $message, array $context = []): void
            {
            }

            public function writeEntry(\PaginiumCMS\Core\Logging\Models\LogEntry $entry): void
            {
            }

            public function getLastEntries(int $limit = 100): array
            {
                return [];
            }

            public function getEntriesBySeverity(string $severity, int $limit = 100): array
            {
                return [];
            }

            public function getEntriesByCategory(string $category, int $limit = 100): array
            {
                return [];
            }

            public function clearOldEntries(int $days = 30): int
            {
                return 0;
            }
        };
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
