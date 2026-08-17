<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Locking\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Locking\Contracts\LockManagerInterface;
use PaginiumCMS\Core\Locking\Exception\LockConflictException;
use PaginiumCMS\Core\Locking\Models\ContentLock;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Modules\Security\Models\User;
use RuntimeException;

/**
 * === Služba: LockManager ===
 * Flat-file manažér zámkov. Stav je v jednom JSON súbore (`data/locks.json`).
 *
 * Riešenie súbežných zápisov (race conditions):
 *  - Celý cyklus "načítaj -> uprav -> zapíš" beží pod exkluzívnym zámkom OS (flock LOCK_EX)
 *    nad samotným JSON súborom (fopen 'c+'). Dvaja používatelia sa tak nikdy neprepíšu.
 *  - Pri každej operácii sa najprv odstránia expirované zámky (auto-release po TTL).
 *
 * Závislosti:
 *  - FileReaderInterface : len na zistenie základnej cesty úložiska (getBasePath)
 *  - FileWriterInterface : na vytvorenie adresára `data/` ak neexistuje
 *  - LoggerInterface     : audit každej zmeny zámku
 */
final class LockManager implements LockManagerInterface
{
    private string $absoluteLockPath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private LoggerInterface $logger,
        private string $lockFile = 'data/locks.json',
        private int $ttl = 300
    ) {
        // Absolútna cesta k registru zámkov (základ úložiska + relatívna cesta).
        $this->absoluteLockPath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->lockFile, '/');
    }

    // === Blok: Verejné API ===

    public function acquire(string $resourceId, User $user): ContentLock
    {
        $resourceId = $this->normalizeId($resourceId);
        $userId = $user->getId();
        $userName = $user->getName() !== '' ? $user->getName() : $user->getEmail();

        return $this->withLockedRegistry(function (array &$locks) use ($resourceId, $userId, $userName): ContentLock {
            $existing = $locks[$resourceId] ?? null;

            // Zdroj je držaný niekým iným a zámok stále platí -> konflikt.
            if ($existing instanceof ContentLock && !$existing->isOwnedBy($userId)) {
                throw new LockConflictException($existing);
            }

            // Buď zámok neexistuje, alebo ho drží ten istý používateľ -> (re)vytvoríme.
            $lock = ContentLock::create($resourceId, $userId, $userName, $this->ttl);
            $locks[$resourceId] = $lock;

            $this->logger->info('Zámok získaný', [
                'resourceId' => $resourceId,
                'lockedBy' => $userId,
            ]);

            return $lock;
        });
    }

    public function heartbeat(string $resourceId, string $token): ContentLock
    {
        $resourceId = $this->normalizeId($resourceId);

        return $this->withLockedRegistry(function (array &$locks) use ($resourceId, $token): ContentLock {
            $lock = $locks[$resourceId] ?? null;

            if (!$lock instanceof ContentLock || !$lock->ownsToken($token)) {
                // Zámok medzičasom expiroval / bol prevzatý / token nesedí.
                throw new LockConflictException(
                    $lock instanceof ContentLock
                        ? $lock
                        : ContentLock::create($resourceId, '', 'neznámy', 0),
                    'Zámok už neplatí – bol uvoľnený alebo prevzatý iným používateľom.'
                );
            }

            $lock->touch($this->ttl);
            $locks[$resourceId] = $lock;

            return $lock;
        });
    }

    public function release(string $resourceId, string $token): void
    {
        $resourceId = $this->normalizeId($resourceId);

        $this->withLockedRegistry(function (array &$locks) use ($resourceId, $token): null {
            $lock = $locks[$resourceId] ?? null;

            if ($lock instanceof ContentLock && $lock->ownsToken($token)) {
                unset($locks[$resourceId]);
                $this->logger->info('Zámok uvoľnený', ['resourceId' => $resourceId]);
            }

            return null;
        });
    }

    public function getLock(string $resourceId): ?ContentLock
    {
        $resourceId = $this->normalizeId($resourceId);

        return $this->withLockedRegistry(
            fn (array &$locks): ?ContentLock => $locks[$resourceId] ?? null
        );
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getAllLocks(): array
    {
        return $this->withLockedRegistry(
            static fn (array &$locks): array => array_values($locks)
        );
    }

    public function forceRelease(string $resourceId): void
    {
        $resourceId = $this->normalizeId($resourceId);

        $this->withLockedRegistry(function (array &$locks) use ($resourceId): null {
            if (isset($locks[$resourceId])) {
                unset($locks[$resourceId]);
                $this->logger->warning('Zámok vynútene uvoľnený administrátorom', [
                    'resourceId' => $resourceId,
                ]);
            }

            return null;
        });
    }

    public function purgeExpired(): int
    {
        return $this->withLockedRegistry(function (array &$locks): int {
            // purgeExpired() prebehne automaticky vnútri withLockedRegistry pred callbackom,
            // takže tu už žiadne expirované zámky nie sú – vrátime 0 (čistenie sa udialo).
            return 0;
        });
    }

    // === Blok: Interná atomická práca s registrom ===

    /**
     * Vykoná callback nad dekódovaným registrom zámkov pod exkluzívnym flock zámkom.
     * Pred callbackom odstráni expirované zámky, po callbacku zapíše zmeny späť.
     *
     * @template T
     * @param callable(array<string, ContentLock>): T $callback
     * @return T
     */
    private function withLockedRegistry(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absoluteLockPath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť register zámkov: ' . $this->absoluteLockPath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať exkluzívny zámok registra.');
            }

            $locks = $this->readLocks($handle);

            // Auto-release: odstráň expirované zámky pri každom prístupe.
            $now = time();
            foreach ($locks as $id => $lock) {
                if ($lock->isExpired($now)) {
                    unset($locks[$id]);
                    $this->logger->info('Zámok auto-uvoľnený (expirácia)', ['resourceId' => $id]);
                }
            }

            $before = $this->serialize($locks);
            $result = $callback($locks);
            $after = $this->serialize($locks);

            // Zapisujeme len ak sa register reálne zmenil (šetríme I/O).
            if ($after !== $before) {
                $this->writeLocks($handle, $locks);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return array<string, ContentLock>
 */private function readLocks($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $locks = [];
        foreach ($decoded as $entry) {
            if (is_array($entry) && isset($entry['resourceId'])) {
                $locks[(string) $entry['resourceId']] = ContentLock::fromArray($entry);
            }
        }

        return $locks;
    }

    /**
     * @param resource $handle
     * @param array<string, ContentLock> $locks
 */private function writeLocks($handle, array $locks): void
    {
        $payload = json_encode(
            array_map(static fn (ContentLock $lock): array => $lock->toArray(), array_values($locks)),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($payload === false) {
            $payload = '[]';
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);
    }

    /**
     * Deterministický reťazec na porovnanie stavu registra (zmenil sa / nezmenil).
     *
     * @param array<string, ContentLock> $locks
 */private function serialize(array $locks): string
    {
        $data = array_map(static fn (ContentLock $lock): array => $lock->toArray(), $locks);
        ksort($data);

        return (string) json_encode($data);
    }

    /**
     * Zaistí existenciu adresára registra.
     * Samotný súbor `locks.json` vytvorí fopen('c+') v withLockedRegistry(),
     * takže tu stačí založiť nadradený adresár (bez závislosti na utf8_normalize).
     */
    private function ensureStorage(): void
    {
        $dir = dirname($this->lockFile);
        if ($dir !== '' && $dir !== '.') {
            $this->writer->createDirectory($dir);
        }

        if (!is_file($this->absoluteLockPath)) {
            $this->writer->write($this->lockFile, "[]\n", false);
        }
    }

    /**
     * Normalizuje identifikátor zdroja (orezanie medzier, jednotný tvar).
     */
    private function normalizeId(string $resourceId): string
    {
        return trim($resourceId);
    }
}
