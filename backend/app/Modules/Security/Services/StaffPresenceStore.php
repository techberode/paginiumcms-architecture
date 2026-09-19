<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Flat-file last-seen stamps for staff chat (It.93o-3).
 */
final class StaffPresenceStore
{
    public const ONLINE_WINDOW_SECONDS = 90;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativeDir = 'data/staff-presence',
    ) {
    }

    /**
     * @return array{online: bool, lastSeen: int}
     */
    public function snapshot(string $userId): array
    {
        $path = $this->path($userId);
        if (!$this->reader->exists($path)) {
            return ['online' => false, 'lastSeen' => 0];
        }

        try {
            $data = JsonHelper::decode($this->reader->read($path));
        } catch (\Throwable) {
            return ['online' => false, 'lastSeen' => 0];
        }

        $wantOnline = (bool) ($data['online'] ?? false);
        $lastSeen = (int) ($data['lastSeen'] ?? 0);
        $fresh = $lastSeen > 0 && (time() - $lastSeen) <= self::ONLINE_WINDOW_SECONDS;

        return [
            'online' => $wantOnline && $fresh,
            'lastSeen' => $lastSeen,
        ];
    }

    public function isOnline(string $userId): bool
    {
        return $this->snapshot($userId)['online'];
    }

    public function heartbeat(string $userId, bool $online): void
    {
        $userId = trim($userId);
        if ($userId === '') {
            return;
        }

        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->path($userId),
            JsonHelper::encode([
                'userId' => $userId,
                'online' => $online,
                'lastSeen' => $online ? time() : (int) ($this->snapshot($userId)['lastSeen']),
            ], JSON_UNESCAPED_UNICODE),
            false
        );
    }

    private function path(string $userId): string
    {
        return $this->relativeDir . '/' . hash('sha256', $userId) . '.json';
    }
}
