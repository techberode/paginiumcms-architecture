<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Agent job state (queued/running/succeeded/failed/cancelled). Immutable actor context.
 */
final class AgentRunStore
{
    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $directory = 'data/agent/runs',
    ) {
    }

    /**
     * @param array<string, mixed> $run
     * @return array<string, mixed>
     */
    public function create(array $run): array
    {
        $id = bin2hex(random_bytes(16));
        $run['id'] = $id;
        $run['createdAt'] = time();
        $run['status'] = (string) ($run['status'] ?? 'queued');
        $this->write($id, $run);

        return $run;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $id, string $actorUserId): array
    {
        $run = $this->read($id);
        if ($run === null) {
            throw new AgentException('Agent run not found', 404, 'NOT_FOUND');
        }
        $owner = (string) ($run['actorUserId'] ?? '');
        if ($owner === '' || !hash_equals($owner, $actorUserId)) {
            throw new AgentException('Agent run not found', 404, 'NOT_FOUND');
        }

        return $run;
    }

    /**
     * Worker lookup without actor check (handler already has the run id from the queue).
     *
     * @return array<string, mixed>
     */
    public function getUnchecked(string $id): array
    {
        $run = $this->read($id);
        if ($run === null) {
            throw new AgentException('Agent run not found', 404, 'NOT_FOUND');
        }

        return $run;
    }

    /**
     * @param array<string, mixed> $run
     */
    public function save(array $run): void
    {
        $id = (string) ($run['id'] ?? '');
        if ($id === '' || !$this->safeId($id)) {
            throw new AgentException('Invalid run id', 422, 'INVALID');
        }
        $this->write($id, $run);
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
     * @param array<string, mixed> $run
     */
    private function write(string $id, array $run): void
    {
        $this->writer->write($this->path($id), JsonHelper::encode($run));
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
