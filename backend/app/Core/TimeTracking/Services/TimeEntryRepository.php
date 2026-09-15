<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\TimeTracking\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Flat-file time entries at data/time-entries/{id}.json (`time-entry@1`, It.93p).
 * Computed duration only — never writes progress into a project plan.
 */
final class TimeEntryRepository
{
    public const SCHEMA = 'time-entry@1';
    public const TARGET_PLAN_ITEM = 'planItem';
    public const TARGET_EVENT = 'event';
    public const TARGET_CONTENT = 'content';
    public const KIND_PAGE = 'page';
    public const KIND_ARTICLE = 'article';

    /** @var list<string> */
    public const TARGETS = [self::TARGET_PLAN_ITEM, self::TARGET_EVENT, self::TARGET_CONTENT];
    public const MAX_SLUG = 80;

    public const MAX_ENTRIES = 2000;
    public const MAX_NOTE = 500;
    public const MAX_USER_ID = 80;
    public const MAX_PLAN_ID = 64;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativeDir = 'data/time-entries',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(?string $userId = null): array
    {
        $items = [];
        foreach ($this->entryFiles() as $file) {
            $id = basename($file, '.json');
            $entry = $this->readNormalized($id);
            if ($entry === null) {
                continue;
            }
            if ($userId !== null && (string) $entry['userId'] !== $userId) {
                continue;
            }
            $items[] = $entry;
        }

        usort(
            $items,
            static fn (array $a, array $b): int => ((int) $b['startedAt']) <=> ((int) $a['startedAt'])
        );

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        try {
            return $this->readNormalized($this->normalizeId($id));
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function runningForUser(string $userId): ?array
    {
        foreach ($this->list($userId) as $entry) {
            if ($entry['endedAt'] === null) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function start(string $userId, array $payload): array
    {
        if (count($this->list()) >= self::MAX_ENTRIES) {
            throw new InvalidArgumentException('Too many time entries.');
        }

        $userId = $this->normalizeUserId($userId);
        if ($this->runningForUser($userId) !== null) {
            throw new InvalidArgumentException('A timer is already running.');
        }

        $now = time();
        $startedAt = $this->toUnix($payload['startedAt'] ?? $now) ?? $now;
        $target = $this->normalizeTarget(is_string($payload['target'] ?? null) ? $payload['target'] : '');

        return $this->writeRecord([
            'schema' => self::SCHEMA,
            'id' => 'time_' . bin2hex(random_bytes(5)),
            'userId' => $userId,
            'target' => $target,
            'planId' => $target === self::TARGET_PLAN_ITEM
                ? $this->normalizePlanId(is_string($payload['planId'] ?? null) ? $payload['planId'] : '')
                : null,
            'planItemId' => $target === self::TARGET_PLAN_ITEM
                ? $this->normalizePlanId(is_string($payload['planItemId'] ?? null) ? $payload['planItemId'] : '')
                : null,
            'eventId' => $target === self::TARGET_EVENT
                ? $this->normalizeEventId(is_string($payload['eventId'] ?? null) ? $payload['eventId'] : '')
                : null,
            'contentKind' => $target === self::TARGET_CONTENT
                ? $this->normalizeContentKind(is_string($payload['contentKind'] ?? null) ? $payload['contentKind'] : '')
                : null,
            'contentSlug' => $target === self::TARGET_CONTENT
                ? $this->normalizeContentSlug(is_string($payload['contentSlug'] ?? null) ? $payload['contentSlug'] : '')
                : null,
            'startedAt' => $startedAt,
            'endedAt' => null,
            'seconds' => 0,
            'note' => $this->normalizeNote(is_string($payload['note'] ?? null) ? $payload['note'] : ''),
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function stop(string $id, ?int $endedAt = null): array
    {
        $existing = $this->get($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Time entry not found');
        }
        if ($existing['endedAt'] !== null) {
            throw new InvalidArgumentException('Timer is already stopped.');
        }

        $end = $endedAt ?? time();
        $start = (int) $existing['startedAt'];
        if ($end < $start) {
            throw new InvalidArgumentException('End must be after the start.');
        }

        $existing['endedAt'] = $end;
        $existing['seconds'] = $end - $start;
        $existing['updatedAt'] = time();

        return $this->writeRecord($existing);
    }

    /**
     * @return array<string, mixed>
     */
    public function updateNote(string $id, string $note): array
    {
        $existing = $this->get($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Time entry not found');
        }

        $existing['note'] = $this->normalizeNote($note);
        $existing['updatedAt'] = time();

        return $this->writeRecord($existing);
    }

    public function delete(string $id): void
    {
        $id = $this->normalizeId($id);
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new InvalidArgumentException('Time entry not found');
        }

        $this->writer->delete($relativePath, false);
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return array{todaySeconds: int, weekSeconds: int, byUserToday: array<string, int>, byPlan: array<string, int>}
     */
    public function summarize(array $entries, int $now): array
    {
        $day = (int) strtotime('today', $now);
        $week = (int) strtotime('monday this week 00:00:00', $now);
        $todaySeconds = 0;
        $weekSeconds = 0;
        $byUserToday = [];
        $byPlan = [];

        foreach ($entries as $entry) {
            $seconds = $this->elapsed($entry, $now);
            $startedAt = (int) ($entry['startedAt'] ?? 0);
            if ($startedAt >= $day) {
                $todaySeconds += $seconds;
                $userId = (string) ($entry['userId'] ?? '');
                if ($userId !== '') {
                    $byUserToday[$userId] = ($byUserToday[$userId] ?? 0) + $seconds;
                }
            }
            if ($startedAt >= $week) {
                $weekSeconds += $seconds;
            }
            $planId = $entry['planId'] ?? null;
            if (is_string($planId) && $planId !== '') {
                $byPlan[$planId] = ($byPlan[$planId] ?? 0) + $seconds;
            }
        }

        return [
            'todaySeconds' => $todaySeconds,
            'weekSeconds' => $weekSeconds,
            'byUserToday' => $byUserToday,
            'byPlan' => $byPlan,
        ];
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function writeRecord(array $record): array
    {
        $id = $this->normalizeId((string) ($record['id'] ?? ''));
        $startedAt = $this->toUnix($record['startedAt'] ?? null);
        if ($startedAt === null || $startedAt < 1) {
            throw new InvalidArgumentException('Start time is required.');
        }

        $endedAt = $this->toUnix($record['endedAt'] ?? null);
        if ($endedAt !== null && $endedAt < $startedAt) {
            throw new InvalidArgumentException('End must be after the start.');
        }

        $canonical = [
            'schema' => self::SCHEMA,
            'id' => $id,
            'userId' => $this->normalizeUserId((string) ($record['userId'] ?? '')),
            'target' => $this->normalizeTarget((string) ($record['target'] ?? '')),
            'planId' => $record['planId'] ?? null,
            'planItemId' => $record['planItemId'] ?? null,
            'eventId' => $record['eventId'] ?? null,
            'contentKind' => $record['contentKind'] ?? null,
            'contentSlug' => $record['contentSlug'] ?? null,
            'startedAt' => $startedAt,
            'endedAt' => $endedAt,
            'seconds' => $endedAt === null ? 0 : ($endedAt - $startedAt),
            'note' => $this->normalizeNote((string) ($record['note'] ?? '')),
            'createdAt' => is_int($record['createdAt'] ?? null) ? $record['createdAt'] : time(),
            'updatedAt' => is_int($record['updatedAt'] ?? null) ? $record['updatedAt'] : time(),
        ];

        if ($canonical['target'] === self::TARGET_PLAN_ITEM) {
            $canonical['planId'] = $this->normalizePlanId(is_string($canonical['planId']) ? $canonical['planId'] : '');
            $canonical['planItemId'] = $this->normalizePlanId(is_string($canonical['planItemId']) ? $canonical['planItemId'] : '');
            $canonical['eventId'] = null;
            $canonical['contentKind'] = null;
            $canonical['contentSlug'] = null;
        } elseif ($canonical['target'] === self::TARGET_EVENT) {
            $canonical['eventId'] = $this->normalizeEventId(is_string($canonical['eventId']) ? $canonical['eventId'] : '');
            $canonical['planId'] = null;
            $canonical['planItemId'] = null;
            $canonical['contentKind'] = null;
            $canonical['contentSlug'] = null;
        } else {
            $canonical['contentKind'] = $this->normalizeContentKind(is_string($canonical['contentKind']) ? $canonical['contentKind'] : '');
            $canonical['contentSlug'] = $this->normalizeContentSlug(is_string($canonical['contentSlug']) ? $canonical['contentSlug'] : '');
            $canonical['planId'] = null;
            $canonical['planItemId'] = null;
            $canonical['eventId'] = null;
        }

        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->relativePath($id),
            JsonHelper::encode($canonical, JSON_PRETTY_PRINT),
            false
        );

        $saved = $this->readNormalized($id);
        if ($saved === null) {
            throw new RuntimeException('Time entry was not stored.');
        }

        return $saved;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readNormalized(string $id): ?array
    {
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        try {
            $data = JsonHelper::decode($this->reader->read($relativePath));
        } catch (\Throwable) {
            return null;
        }

        $storedId = is_string($data['id'] ?? null) ? $data['id'] : $id;
        if ($storedId !== $id) {
            return null;
        }

        try {
            $startedAt = $this->toUnix($data['startedAt'] ?? null);
            if ($startedAt === null || $startedAt < 1) {
                return null;
            }
            $endedAt = $this->toUnix($data['endedAt'] ?? null);
            $target = $this->normalizeTarget(is_string($data['target'] ?? null) ? $data['target'] : '');

            $record = [
                'schema' => self::SCHEMA,
                'id' => $id,
                'userId' => $this->normalizeUserId(is_string($data['userId'] ?? null) ? $data['userId'] : ''),
                'target' => $target,
                'planId' => null,
                'planItemId' => null,
                'eventId' => null,
                'contentKind' => null,
                'contentSlug' => null,
                'startedAt' => $startedAt,
                'endedAt' => $endedAt,
                'seconds' => 0,
                'note' => $this->normalizeNote(is_string($data['note'] ?? null) ? $data['note'] : ''),
                'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
                'updatedAt' => is_int($data['updatedAt'] ?? null) ? $data['updatedAt'] : 0,
            ];

            if ($target === self::TARGET_PLAN_ITEM) {
                $record['planId'] = $this->normalizePlanId(is_string($data['planId'] ?? null) ? $data['planId'] : '');
                $record['planItemId'] = $this->normalizePlanId(is_string($data['planItemId'] ?? null) ? $data['planItemId'] : '');
            } elseif ($target === self::TARGET_EVENT) {
                $record['eventId'] = $this->normalizeEventId(is_string($data['eventId'] ?? null) ? $data['eventId'] : '');
            } else {
                $record['contentKind'] = $this->normalizeContentKind(is_string($data['contentKind'] ?? null) ? $data['contentKind'] : '');
                $record['contentSlug'] = $this->normalizeContentSlug(is_string($data['contentSlug'] ?? null) ? $data['contentSlug'] : '');
            }

            $record['seconds'] = $this->elapsed($record, time());

            return $record;
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function elapsed(array $entry, int $now): int
    {
        $start = (int) ($entry['startedAt'] ?? 0);
        $end = is_int($entry['endedAt'] ?? null) ? $entry['endedAt'] : $now;

        return max(0, $end - $start);
    }

    /**
     * @return list<string>
     */
    private function entryFiles(): array
    {
        try {
            $files = $this->reader->listFiles($this->relativeDir, '*.json');
        } catch (\Throwable) {
            return [];
        }

        $names = [];
        foreach ($files as $file) {
            if (str_ends_with($file, '.json')) {
                $names[] = $file;
            }
        }

        return $names;
    }

    private function relativePath(string $id): string
    {
        return $this->relativeDir . '/' . $id . '.json';
    }

    private function normalizeId(string $id): string
    {
        $id = strtolower(trim($id));
        if ($id === '' || !preg_match('/^time_[a-f0-9]{10}$/', $id)) {
            throw new InvalidArgumentException('Invalid time entry id.');
        }

        return $id;
    }

    private function normalizeUserId(string $userId): string
    {
        $userId = trim($userId);
        $userId = LogSanitizer::value($userId, self::MAX_USER_ID);
        if ($userId === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $userId)) {
            throw new InvalidArgumentException('Invalid user id.');
        }

        return $userId;
    }

    private function normalizeTarget(string $target): string
    {
        $target = trim($target);
        if (!in_array($target, self::TARGETS, true)) {
            throw new InvalidArgumentException('Invalid time target.');
        }

        return $target;
    }

    private function normalizePlanId(string $planId): string
    {
        $planId = strtolower(trim($planId));
        if ($planId === '' || strlen($planId) > self::MAX_PLAN_ID || !preg_match('/^[a-z0-9-]+$/', $planId)) {
            throw new InvalidArgumentException('Invalid project plan id.');
        }

        return $planId;
    }

    private function normalizeEventId(string $eventId): string
    {
        $eventId = strtolower(trim($eventId));
        if ($eventId === '' || !preg_match('/^event_[a-f0-9]{10}$/', $eventId)) {
            throw new InvalidArgumentException('Invalid event id.');
        }

        return $eventId;
    }

    private function normalizeContentKind(string $kind): string
    {
        $kind = strtolower(trim($kind));
        if (!in_array($kind, [self::KIND_PAGE, self::KIND_ARTICLE], true)) {
            throw new InvalidArgumentException('Invalid content kind.');
        }

        return $kind;
    }

    private function normalizeContentSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || strlen($slug) > self::MAX_SLUG || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException('Invalid content slug.');
        }

        return $slug;
    }

    private function normalizeNote(string $note): string
    {
        return LogSanitizer::value(trim($note), self::MAX_NOTE);
    }

    private function toUnix(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return (int) $value;
        }

        return null;
    }
}
