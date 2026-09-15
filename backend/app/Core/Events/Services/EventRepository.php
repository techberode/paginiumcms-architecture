<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Events\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Flat-file site events at data/events/{id}.json (`site-event@1`, It.93n).
 * Company/public events — not the editorial calendar.
 */
final class EventRepository
{
    public const SCHEMA = 'site-event@1';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    /** @var list<string> */
    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED];

    public const MAX_EVENTS = 200;
    public const MAX_TITLE = 160;
    public const MAX_SLUG = 80;
    public const MAX_LOCATION = 160;
    public const MAX_BODY = 20000;
    public const MAX_PLAN_ID = 64;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativeDir = 'data/events',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $items = [];
        foreach ($this->eventFiles() as $file) {
            $id = basename($file, '.json');
            $event = $this->readNormalized($id);
            if ($event !== null) {
                $items[] = $event;
            }
        }

        usort(
            $items,
            static function (array $a, array $b): int {
                $start = ((int) $b['startsAt']) <=> ((int) $a['startsAt']);
                if ($start !== 0) {
                    return $start;
                }

                return strcmp((string) $a['title'], (string) $b['title']);
            }
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
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        if (count($this->list()) >= self::MAX_EVENTS) {
            throw new InvalidArgumentException('Too many events.');
        }

        $now = time();
        $id = 'event_' . bin2hex(random_bytes(5));
        $title = $this->normalizeTitle(is_string($payload['title'] ?? null) ? $payload['title'] : '');
        $slugInput = is_string($payload['slug'] ?? null) ? $payload['slug'] : '';

        return $this->writeRecord([
            'schema' => self::SCHEMA,
            'id' => $id,
            'title' => $title,
            'slug' => $this->uniqueSlug($slugInput !== '' ? $slugInput : $title, null),
            'startsAt' => $this->normalizeStartsAt($payload['startsAt'] ?? null),
            'endsAt' => $this->normalizeEndsAt($payload['endsAt'] ?? null, $this->normalizeStartsAt($payload['startsAt'] ?? null)),
            'location' => $this->normalizeLocation(is_string($payload['location'] ?? null) ? $payload['location'] : ''),
            'body' => $this->normalizeBody(is_string($payload['body'] ?? null) ? $payload['body'] : ''),
            'status' => $this->normalizeStatus(is_string($payload['status'] ?? null) ? $payload['status'] : self::STATUS_DRAFT),
            'projectPlanId' => $this->normalizePlanId(
                is_string($payload['projectPlanId'] ?? null) ? $payload['projectPlanId'] : ''
            ),
            'createdAt' => $now,
            'updatedAt' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        $id = $this->normalizeId($id);
        $existing = $this->readNormalized($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Event not found');
        }

        if (array_key_exists('title', $payload)) {
            $existing['title'] = $this->normalizeTitle(is_string($payload['title']) ? $payload['title'] : '');
        }
        if (array_key_exists('slug', $payload) || array_key_exists('title', $payload)) {
            $slugInput = array_key_exists('slug', $payload) && is_string($payload['slug'])
                ? $payload['slug']
                : (string) ($existing['slug'] ?? '');
            if ($slugInput === '') {
                $slugInput = (string) $existing['title'];
            }
            $existing['slug'] = $this->uniqueSlug($slugInput, $id);
        }
        if (array_key_exists('startsAt', $payload)) {
            $existing['startsAt'] = $this->normalizeStartsAt($payload['startsAt']);
        }
        if (array_key_exists('endsAt', $payload) || array_key_exists('startsAt', $payload)) {
            $endsRaw = array_key_exists('endsAt', $payload) ? $payload['endsAt'] : ($existing['endsAt'] ?? null);
            $existing['endsAt'] = $this->normalizeEndsAt($endsRaw, (int) $existing['startsAt']);
        }
        if (array_key_exists('location', $payload)) {
            $existing['location'] = $this->normalizeLocation(is_string($payload['location']) ? $payload['location'] : '');
        }
        if (array_key_exists('body', $payload)) {
            $existing['body'] = $this->normalizeBody(is_string($payload['body']) ? $payload['body'] : '');
        }
        if (array_key_exists('status', $payload)) {
            $existing['status'] = $this->normalizeStatus(is_string($payload['status']) ? $payload['status'] : '');
        }
        if (array_key_exists('projectPlanId', $payload)) {
            $existing['projectPlanId'] = $this->normalizePlanId(
                is_string($payload['projectPlanId']) ? $payload['projectPlanId'] : ''
            );
        }
        $existing['updatedAt'] = time();

        return $this->writeRecord($existing);
    }

    public function delete(string $id): void
    {
        $id = $this->normalizeId($id);
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new InvalidArgumentException('Event not found');
        }

        $this->writer->delete($relativePath, false);
    }

    /**
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function writeRecord(array $record): array
    {
        $id = $this->normalizeId((string) ($record['id'] ?? ''));
        $canonical = [
            'schema' => self::SCHEMA,
            'id' => $id,
            'title' => $this->normalizeTitle((string) ($record['title'] ?? '')),
            'slug' => $this->normalizeSlug((string) ($record['slug'] ?? '')),
            'startsAt' => $this->normalizeStartsAt($record['startsAt'] ?? null),
            'endsAt' => $this->normalizeEndsAt($record['endsAt'] ?? null, $this->normalizeStartsAt($record['startsAt'] ?? null)),
            'location' => $this->normalizeLocation((string) ($record['location'] ?? '')),
            'body' => $this->normalizeBody((string) ($record['body'] ?? '')),
            'status' => $this->normalizeStatus((string) ($record['status'] ?? self::STATUS_DRAFT)),
            'projectPlanId' => $this->normalizePlanId((string) ($record['projectPlanId'] ?? '')),
            'createdAt' => is_int($record['createdAt'] ?? null) ? $record['createdAt'] : time(),
            'updatedAt' => is_int($record['updatedAt'] ?? null) ? $record['updatedAt'] : time(),
        ];

        $this->writer->createDirectory($this->relativeDir);
        $this->writer->write(
            $this->relativePath($id),
            JsonHelper::encode($canonical, JSON_PRETTY_PRINT),
            false
        );

        $saved = $this->readNormalized($id);
        if ($saved === null) {
            throw new RuntimeException('Event was not stored.');
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
            $startsAt = $this->normalizeStartsAt($data['startsAt'] ?? null);

            return [
                'schema' => self::SCHEMA,
                'id' => $id,
                'title' => $this->normalizeTitle(is_string($data['title'] ?? null) ? $data['title'] : ''),
                'slug' => $this->normalizeSlug(is_string($data['slug'] ?? null) ? $data['slug'] : ''),
                'startsAt' => $startsAt,
                'endsAt' => $this->normalizeEndsAt($data['endsAt'] ?? null, $startsAt),
                'location' => $this->normalizeLocation(is_string($data['location'] ?? null) ? $data['location'] : ''),
                'body' => $this->normalizeBody(is_string($data['body'] ?? null) ? $data['body'] : ''),
                'status' => $this->normalizeStatus(is_string($data['status'] ?? null) ? $data['status'] : self::STATUS_DRAFT),
                'projectPlanId' => $this->normalizePlanId(is_string($data['projectPlanId'] ?? null) ? $data['projectPlanId'] : ''),
                'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
                'updatedAt' => is_int($data['updatedAt'] ?? null) ? $data['updatedAt'] : 0,
            ];
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function eventFiles(): array
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
        if ($id === '' || !preg_match('/^event_[a-f0-9]{10}$/', $id)) {
            throw new InvalidArgumentException('Invalid event id.');
        }

        return $id;
    }

    private function uniqueSlug(string $raw, ?string $ignoreId): string
    {
        $base = $this->slugify($raw);
        $slug = $base;
        $n = 2;
        while ($this->slugTaken($slug, $ignoreId)) {
            $suffix = '-' . $n;
            $slug = $this->normalizeSlug(substr($base, 0, self::MAX_SLUG - strlen($suffix)) . $suffix);
            $n++;
            if ($n > 50) {
                throw new InvalidArgumentException('Could not allocate a unique slug.');
            }
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?string $ignoreId): bool
    {
        foreach ($this->list() as $event) {
            if ($ignoreId !== null && $event['id'] === $ignoreId) {
                continue;
            }
            if ((string) $event['slug'] === $slug) {
                return true;
            }
        }

        return false;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $ascii = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
        if (is_string($ascii) && $ascii !== '') {
            $value = strtolower($ascii);
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        if ($value === '') {
            $value = 'event';
        }

        return $this->normalizeSlug($value);
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        $slug = LogSanitizer::value($slug, self::MAX_SLUG);
        if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException('Invalid event slug.');
        }

        return $slug;
    }

    private function normalizeTitle(string $title): string
    {
        $title = LogSanitizer::value(trim($title), self::MAX_TITLE);
        if ($title === '') {
            throw new InvalidArgumentException('Event title is required.');
        }

        return $title;
    }

    private function normalizeLocation(string $location): string
    {
        return LogSanitizer::value(trim($location), self::MAX_LOCATION);
    }

    private function normalizeBody(string $body): string
    {
        $body = str_replace("\0", '', $body);
        if (function_exists('mb_substr')) {
            return mb_substr($body, 0, self::MAX_BODY);
        }

        return substr($body, 0, self::MAX_BODY);
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid event status.');
        }

        return $status;
    }

    private function normalizePlanId(string $planId): ?string
    {
        $planId = strtolower(trim($planId));
        if ($planId === '') {
            return null;
        }
        if (strlen($planId) > self::MAX_PLAN_ID || !preg_match('/^[a-z0-9-]+$/', $planId)) {
            throw new InvalidArgumentException('Invalid project plan id.');
        }

        return $planId;
    }

    private function normalizeStartsAt(mixed $value): int
    {
        $stamp = $this->toUnix($value);
        if ($stamp === null || $stamp < 1) {
            throw new InvalidArgumentException('Event start is required.');
        }

        return $stamp;
    }

    private function normalizeEndsAt(mixed $value, int $startsAt): ?int
    {
        $stamp = $this->toUnix($value);
        if ($stamp === null) {
            return null;
        }
        if ($stamp < $startsAt) {
            throw new InvalidArgumentException('Event end must be after the start.');
        }

        return $stamp;
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
