<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Messages\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Support\JsonHelper;

/**
 * Subject → team/user routing for the contact desk (It.93o-4).
 */
final class MessageRoutingStore
{
    public const SCHEMA = 'message-routing@1';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $relativePath = 'data/message-routing.json'
    ) {
    }

    /**
     * @return array{schema: string, enabled: bool, routes: list<array{subject: string, enabled: bool, teamIds: list<string>, userIds: list<string>}>}
     */
    public function get(): array
    {
        if (!$this->reader->exists($this->relativePath)) {
            return $this->empty();
        }

        try {
            $data = JsonHelper::decode($this->reader->read($this->relativePath));
        } catch (\Throwable) {
            return $this->empty();
        }

        return $this->normalize($data);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{schema: string, enabled: bool, routes: list<array{subject: string, enabled: bool, teamIds: list<string>, userIds: list<string>}>}
     */
    public function save(array $payload): array
    {
        $canonical = $this->normalize($payload);
        $this->writer->write(
            $this->relativePath,
            JsonHelper::encode($canonical, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            false
        );

        return $canonical;
    }

    /**
     * @return array{subject: string, enabled: bool, teamIds: list<string>, userIds: list<string>}|null
     */
    public function match(string $subject): ?array
    {
        $config = $this->get();
        if (!$config['enabled']) {
            return null;
        }

        $needle = self::normalizeSubject($subject);
        if ($needle === '') {
            return null;
        }

        foreach ($config['routes'] as $route) {
            if (!$route['enabled']) {
                continue;
            }
            if (self::normalizeSubject($route['subject']) === $needle) {
                return $route;
            }
        }

        return null;
    }

    public static function normalizeSubject(string $subject): string
    {
        $subject = trim($subject);
        if ($subject === '') {
            return '';
        }

        return function_exists('mb_strtolower') ? mb_strtolower($subject) : strtolower($subject);
    }

    /**
     * @return array{schema: string, enabled: bool, routes: list<array{subject: string, enabled: bool, teamIds: list<string>, userIds: list<string>}>}
     */
    private function empty(): array
    {
        return [
            'schema' => self::SCHEMA,
            'enabled' => false,
            'routes' => [],
        ];
    }

    /**
     * @param array<int|string, mixed> $data
     * @return array{schema: string, enabled: bool, routes: list<array{subject: string, enabled: bool, teamIds: list<string>, userIds: list<string>}>}
     */
    private function normalize(array $data): array
    {
        $routes = [];
        $raw = $data['routes'] ?? [];
        if (is_array($raw)) {
            foreach ($raw as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $subject = trim((string) ($row['subject'] ?? ''));
                if ($subject === '') {
                    continue;
                }
                $teams = $row['teamIds'] ?? [];
                $users = $row['userIds'] ?? [];
                $routes[] = [
                    'subject' => mb_substr($subject, 0, 200),
                    'enabled' => (bool) ($row['enabled'] ?? true),
                    'teamIds' => ContactMessage::normalizeIds(is_array($teams) ? array_values($teams) : []),
                    'userIds' => ContactMessage::normalizeIds(is_array($users) ? array_values($users) : []),
                ];
            }
        }

        return [
            'schema' => self::SCHEMA,
            'enabled' => (bool) ($data['enabled'] ?? false),
            'routes' => $routes,
        ];
    }
}
