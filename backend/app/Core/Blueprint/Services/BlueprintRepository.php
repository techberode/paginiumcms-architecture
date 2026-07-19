<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Blueprint\Services;

use PaginiumCMS\Core\Blueprint\Models\Blueprint;
use PaginiumCMS\Core\Blueprint\Models\FieldDefinition;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file blueprint store: `data/blueprints/{type}.json` (Iteration 12).
 */
final class BlueprintRepository
{
    private string $directory;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeDir = 'data/blueprints'
    ) {
        $this->directory = rtrim($this->reader->getBasePath(), '/') . '/' . trim($this->storeDir, '/');
    }

    /**
     * @return list<array{type: string, label: string, system: bool, field_count: int}>
     */
    public function list(): array
    {
        $types = $this->discoverTypes();
        $summaries = [];

        foreach ($types as $type) {
            $blueprint = $this->get($type);
            $summaries[] = [
                'type' => $blueprint->type,
                'label' => $blueprint->label,
                'system' => $blueprint->system,
                'field_count' => count($blueprint->fields),
            ];
        }

        usort(
            $summaries,
            static fn (array $a, array $b): int => strcmp($a['type'], $b['type'])
        );

        return $summaries;
    }

    public function exists(string $type): bool
    {
        $type = $this->normalizeType($type);

        return $type !== '' && (is_readable($this->pathFor($type)) || isset(self::defaults()[$type]));
    }

    public function get(string $type): Blueprint
    {
        $type = $this->normalizeType($type);
        if ($type === '') {
            throw new RuntimeException('Blueprint type is required');
        }

        $path = $this->pathFor($type);
        if (is_readable($path)) {
            $raw = file_get_contents($path);
            if ($raw !== false && trim($raw) !== '') {
                return Blueprint::fromArray($this->normalizePayload(JsonHelper::decode($raw), $type));
            }
        }

        $defaults = self::defaults();
        if (!isset($defaults[$type])) {
            throw new RuntimeException('Blueprint not found: ' . $type);
        }

        return Blueprint::fromArray($defaults[$type]);
    }

    public function save(Blueprint $blueprint): Blueprint
    {
        $type = $this->normalizeType($blueprint->type);
        if ($type === '') {
            throw new RuntimeException('Blueprint type is required');
        }

        $payload = $blueprint->toArray();
        $payload['type'] = $type;
        $payload['updated_at'] = date('c');
        if ($blueprint->system) {
            $payload['system'] = true;
        }

        $dir = $this->directory;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create blueprint directory: ' . $dir);
        }

        file_put_contents(
            $this->pathFor($type),
            JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return Blueprint::fromArray($payload);
    }

    public function delete(string $type): void
    {
        $type = $this->normalizeType($type);
        $defaults = self::defaults();
        if (isset($defaults[$type])) {
            throw new RuntimeException('System blueprint cannot be deleted: ' . $type);
        }

        $path = $this->pathFor($type);
        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * @return list<string>
     */
    private function discoverTypes(): array
    {
        $types = array_keys(self::defaults());

        if (is_dir($this->directory)) {
            foreach (glob($this->directory . '/*.json') ?: [] as $file) {
                $type = basename($file, '.json');
                if ($type !== '') {
                    $types[] = $type;
                }
            }
        }

        return array_values(array_unique($types));
    }

    private function pathFor(string $type): string
    {
        return $this->directory . '/' . $type . '.json';
    }

    private function normalizeType(string $type): string
    {
        return strtolower(trim($type));
    }

    /**
     * @param array<int|string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizePayload(array $decoded, string $type): array
    {
        $fields = $decoded['fields'] ?? [];

        return [
            'type' => (string) ($decoded['type'] ?? $type),
            'label' => (string) ($decoded['label'] ?? ucfirst($type)),
            'description' => (string) ($decoded['description'] ?? ''),
            'system' => ($decoded['system'] ?? isset(self::defaults()[$type])) === true,
            'fields' => is_array($fields) ? $fields : [],
            'updated_at' => isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : null,
        ];
    }

    /**
     * Built-in blueprints for core content types.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            'page' => [
                'type' => 'page',
                'label' => 'Stránka',
                'description' => 'Statická stránka webu',
                'system' => true,
                'fields' => [
                    FieldDefinition::fromArray([
                        'key' => 'title',
                        'type' => 'text',
                        'label' => 'Nadpis',
                        'rules' => ['required', 'string', 'max:255'],
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'slug',
                        'type' => 'slug',
                        'label' => 'Slug',
                        'rules' => ['required', 'slug'],
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'status',
                        'type' => 'select',
                        'label' => 'Stav',
                        'options' => ['draft', 'published', 'archived'],
                        'rules' => ['required', 'in:draft,published,archived'],
                        'default' => 'draft',
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'template',
                        'type' => 'select',
                        'label' => 'Šablóna',
                        'options' => ['default', 'home', 'about', 'contact', 'landing', 'services', 'blog'],
                        'rules' => ['string', 'in:default,home,about,contact,landing,services,blog'],
                        'default' => 'default',
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'content',
                        'type' => 'markdown',
                        'label' => 'Obsah',
                        'rules' => ['string'],
                    ])->toArray(),
                ],
            ],
            'article' => [
                'type' => 'article',
                'label' => 'Článok',
                'description' => 'Blogový článok',
                'system' => true,
                'fields' => [
                    FieldDefinition::fromArray([
                        'key' => 'title',
                        'type' => 'text',
                        'label' => 'Nadpis',
                        'rules' => ['required', 'string', 'max:255'],
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'slug',
                        'type' => 'slug',
                        'label' => 'Slug',
                        'rules' => ['required', 'slug'],
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'status',
                        'type' => 'select',
                        'label' => 'Stav',
                        'options' => ['draft', 'published', 'archived'],
                        'rules' => ['required', 'in:draft,published,archived'],
                        'default' => 'draft',
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'excerpt',
                        'type' => 'textarea',
                        'label' => 'Perex',
                        'rules' => ['string', 'max:500'],
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'published_at',
                        'type' => 'datetime',
                        'label' => 'Dátum publikácie',
                        'rules' => ['string'],
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'featured_image',
                        'type' => 'media',
                        'label' => 'Titulný obrázok',
                        'rules' => ['string', 'max:512'],
                    ])->toArray(),
                    FieldDefinition::fromArray([
                        'key' => 'content',
                        'type' => 'markdown',
                        'label' => 'Obsah',
                        'rules' => ['string'],
                    ])->toArray(),
                ],
            ],
        ];
    }
}
