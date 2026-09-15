<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\ComingSoon\Services;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Coming-soon countdown linked to a page or article (`coming-soon@1`, It.93v).
 */
final class ComingSoonRepository
{
    public const SCHEMA = 'coming-soon@1';
    public const KIND_PAGE = 'page';
    public const KIND_ARTICLE = 'article';

    /** @var list<string> */
    public const KINDS = [self::KIND_PAGE, self::KIND_ARTICLE];

    public const MAX_ITEMS = 200;
    public const MAX_TITLE = 160;
    public const MAX_SUBTITLE = 400;
    public const MAX_SLUG = 80;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativeDir = 'data/coming-soon',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $items = [];
        foreach ($this->files() as $file) {
            $id = basename($file, '.json');
            $item = $this->readNormalized($id);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        usort(
            $items,
            static fn (array $a, array $b): int => ((int) $a['publishAt']) <=> ((int) $b['publishAt'])
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
    public function findByKindSlug(string $kind, string $slug): ?array
    {
        try {
            $kind = $this->normalizeKind($kind);
            $slug = $this->normalizeSlug($slug);
        } catch (InvalidArgumentException) {
            return null;
        }

        foreach ($this->list() as $item) {
            if ((string) $item['contentKind'] === $kind && (string) $item['slug'] === $slug) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        if (count($this->list()) >= self::MAX_ITEMS) {
            throw new InvalidArgumentException('Too many coming-soon timers.');
        }

        $kind = $this->normalizeKind(is_string($payload['contentKind'] ?? null) ? $payload['contentKind'] : '');
        $slug = $this->normalizeSlug(is_string($payload['slug'] ?? null) ? $payload['slug'] : '');
        if ($this->findByKindSlug($kind, $slug) !== null) {
            throw new InvalidArgumentException('A coming-soon timer already exists for this page.');
        }

        $now = time();

        return $this->writeRecord([
            'schema' => self::SCHEMA,
            'id' => 'soon_' . bin2hex(random_bytes(5)),
            'contentKind' => $kind,
            'slug' => $slug,
            'title' => $this->normalizeTitle(is_string($payload['title'] ?? null) ? $payload['title'] : ''),
            'subtitle' => $this->normalizeSubtitle(is_string($payload['subtitle'] ?? null) ? $payload['subtitle'] : ''),
            'publishAt' => $this->normalizePublishAt($payload['publishAt'] ?? null),
            'enabled' => $this->toBool($payload['enabled'] ?? true),
            'embedOnPage' => $this->toBool($payload['embedOnPage'] ?? true),
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
        $existing = $this->get($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Coming-soon timer not found');
        }

        $kind = array_key_exists('contentKind', $payload)
            ? $this->normalizeKind(is_string($payload['contentKind']) ? $payload['contentKind'] : '')
            : (string) $existing['contentKind'];
        $slug = array_key_exists('slug', $payload)
            ? $this->normalizeSlug(is_string($payload['slug']) ? $payload['slug'] : '')
            : (string) $existing['slug'];

        $other = $this->findByKindSlug($kind, $slug);
        if ($other !== null && (string) $other['id'] !== (string) $existing['id']) {
            throw new InvalidArgumentException('A coming-soon timer already exists for this page.');
        }

        return $this->writeRecord([
            ...$existing,
            'contentKind' => $kind,
            'slug' => $slug,
            'title' => array_key_exists('title', $payload)
                ? $this->normalizeTitle(is_string($payload['title']) ? $payload['title'] : '')
                : (string) $existing['title'],
            'subtitle' => array_key_exists('subtitle', $payload)
                ? $this->normalizeSubtitle(is_string($payload['subtitle']) ? $payload['subtitle'] : '')
                : (string) $existing['subtitle'],
            'publishAt' => array_key_exists('publishAt', $payload)
                ? $this->normalizePublishAt($payload['publishAt'])
                : (int) $existing['publishAt'],
            'enabled' => array_key_exists('enabled', $payload)
                ? $this->toBool($payload['enabled'])
                : (bool) $existing['enabled'],
            'embedOnPage' => array_key_exists('embedOnPage', $payload)
                ? $this->toBool($payload['embedOnPage'])
                : (bool) $existing['embedOnPage'],
            'updatedAt' => time(),
        ]);
    }

    public function delete(string $id): void
    {
        $id = $this->normalizeId($id);
        $relativePath = $this->relativePath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new InvalidArgumentException('Coming-soon timer not found');
        }

        $this->writer->delete($relativePath, false);
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function presentPublic(array $item, int $now): array
    {
        $publishAt = (int) ($item['publishAt'] ?? 0);

        return [
            'id' => (string) $item['id'],
            'contentKind' => (string) $item['contentKind'],
            'slug' => (string) $item['slug'],
            'title' => (string) $item['title'],
            'subtitle' => (string) $item['subtitle'],
            'publishAt' => $publishAt,
            'embedOnPage' => (bool) ($item['embedOnPage'] ?? true),
            'remainingSeconds' => max(0, $publishAt - $now),
            'isLive' => $now >= $publishAt,
        ];
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
            'contentKind' => $this->normalizeKind((string) ($record['contentKind'] ?? '')),
            'slug' => $this->normalizeSlug((string) ($record['slug'] ?? '')),
            'title' => $this->normalizeTitle((string) ($record['title'] ?? '')),
            'subtitle' => $this->normalizeSubtitle((string) ($record['subtitle'] ?? '')),
            'publishAt' => $this->normalizePublishAt($record['publishAt'] ?? null),
            'enabled' => $this->toBool($record['enabled'] ?? true),
            'embedOnPage' => $this->toBool($record['embedOnPage'] ?? true),
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
            throw new RuntimeException('Coming-soon timer was not stored.');
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
            $storedId = is_string($data['id'] ?? null) ? $data['id'] : $id;
            if ($storedId !== $id) {
                return null;
            }

            return [
                'schema' => self::SCHEMA,
                'id' => $id,
                'contentKind' => $this->normalizeKind(is_string($data['contentKind'] ?? null) ? $data['contentKind'] : ''),
                'slug' => $this->normalizeSlug(is_string($data['slug'] ?? null) ? $data['slug'] : ''),
                'title' => $this->normalizeTitle(is_string($data['title'] ?? null) ? $data['title'] : ''),
                'subtitle' => $this->normalizeSubtitle(is_string($data['subtitle'] ?? null) ? $data['subtitle'] : ''),
                'publishAt' => $this->normalizePublishAt($data['publishAt'] ?? null),
                'enabled' => $this->toBool($data['enabled'] ?? true),
                'embedOnPage' => $this->toBool($data['embedOnPage'] ?? true),
                'createdAt' => is_int($data['createdAt'] ?? null) ? $data['createdAt'] : 0,
                'updatedAt' => is_int($data['updatedAt'] ?? null) ? $data['updatedAt'] : 0,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function files(): array
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
        if ($id === '' || !preg_match('/^soon_[a-f0-9]{10}$/', $id)) {
            throw new InvalidArgumentException('Invalid coming-soon id.');
        }

        return $id;
    }

    private function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));
        if (!in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException('Invalid content kind.');
        }

        return $kind;
    }

    private function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || strlen($slug) > self::MAX_SLUG || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException('Invalid content slug.');
        }

        return $slug;
    }

    private function normalizeTitle(string $title): string
    {
        $title = LogSanitizer::value(trim($title), self::MAX_TITLE);

        return $title === '' ? 'Coming soon' : $title;
    }

    private function normalizeSubtitle(string $subtitle): string
    {
        return LogSanitizer::value(trim($subtitle), self::MAX_SUBTITLE);
    }

    private function normalizePublishAt(mixed $value): int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            $unix = (int) $value;
            if ($unix > 0) {
                return $unix;
            }
        }

        throw new InvalidArgumentException('Publish time is required.');
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
