<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * TTL-bound proposals: actor + resource + revision (It.75). Applied rows stay in audit only.
 */
final class AgentProposalStore
{
    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private AgentSettings $settings,
        private string $directory = 'data/agent/proposals',
    ) {
    }

    /**
     * @param array<string, mixed> $proposal
     * @return array<string, mixed>
     */
    public function create(array $proposal): array
    {
        $id = bin2hex(random_bytes(16));
        $now = time();
        $proposal['id'] = $id;
        $proposal['createdAt'] = $now;
        $proposal['expiresAt'] = $now + $this->settings->proposalTtlSeconds();
        $proposal['applied'] = false;
        $this->write($id, $proposal);

        return $proposal;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id, string $actorUserId): array
    {
        $proposal = $this->read($id);
        if ($proposal === null) {
            throw new AgentException('Agent proposal not found', 404, 'NOT_FOUND');
        }
        if ((int) ($proposal['expiresAt'] ?? 0) < time() && ($proposal['applied'] ?? false) !== true) {
            $this->delete($id);
            throw new AgentException('Agent proposal expired', 410, 'EXPIRED');
        }
        $owner = (string) ($proposal['actorUserId'] ?? '');
        if ($owner === '' || !hash_equals($owner, $actorUserId)) {
            throw new AgentException('Agent proposal not found', 404, 'NOT_FOUND');
        }

        return $proposal;
    }

    /**
     * @param array<string, mixed> $proposal
     */
    public function save(array $proposal): void
    {
        $id = (string) ($proposal['id'] ?? '');
        if ($id === '' || !$this->safeId($id)) {
            throw new AgentException('Invalid proposal id', 422, 'INVALID');
        }
        $this->write($id, $proposal);
    }

    public function delete(string $id): void
    {
        if (!$this->safeId($id) || !$this->reader->exists($this->path($id))) {
            return;
        }
        $this->writer->delete($this->path($id), false);
    }

    public function purgeExpired(): void
    {
        try {
            $files = $this->reader->listFiles($this->directory, '*.json');
        } catch (\Throwable) {
            return;
        }

        $now = time();
        foreach ($files as $relative) {
            $id = basename((string) $relative, '.json');
            $row = $this->read($id);
            if ($row === null) {
                continue;
            }
            if (($row['applied'] ?? false) === true) {
                continue;
            }
            if ((int) ($row['expiresAt'] ?? 0) < $now) {
                $this->delete($id);
            }
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function read(string $id): ?array
    {
        if (!$this->safeId($id) || !$this->reader->exists($this->path($id))) {
            return null;
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read($this->path($id)));
        } catch (\Throwable) {
            return null;
        }

        $out = [];
        foreach ($decoded as $key => $value) {
            if (is_string($key)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $proposal
     */
    private function write(string $id, array $proposal): void
    {
        $this->writer->write($this->path($id), JsonHelper::encode($proposal));
    }

    private function path(string $id): string
    {
        return $this->directory . '/' . $id . '.json';
    }

    private function safeId(string $id): bool
    {
        return preg_match('/^[a-f0-9]{32}$/', $id) === 1;
    }
}
