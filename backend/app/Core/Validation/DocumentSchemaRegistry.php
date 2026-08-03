<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Validation;

/**
 * Registers JSON Schema documents by type and version (Iteration 68).
 */
final class DocumentSchemaRegistry
{
    public const TYPE_SETTINGS_OVERRIDES = 'settings.overrides';

    /** @var array<string, array<int, array<string, mixed>>> */
    private array $schemas = [];

    /**
     * @param array<string, mixed> $schema JSON Schema document
     */
    public function register(string $documentType, int $version, array $schema): void
    {
        $this->schemas[$documentType][$version] = $schema;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $documentType, int $version): ?array
    {
        return $this->schemas[$documentType][$version] ?? null;
    }

    public function has(string $documentType, int $version): bool
    {
        return isset($this->schemas[$documentType][$version]);
    }

    public function latestVersion(string $documentType): ?int
    {
        if (!isset($this->schemas[$documentType])) {
            return null;
        }

        $versions = array_keys($this->schemas[$documentType]);
        if ($versions === []) {
            return null;
        }

        return max($versions);
    }

    /**
     * @return list<string>
     */
    public function registeredTypes(): array
    {
        return array_keys($this->schemas);
    }

    /**
     * Registers built-in schemas shipped with the core.
     */
    public static function createWithDefaults(): self
    {
        $registry = new self();
        $registry->register(self::TYPE_SETTINGS_OVERRIDES, 1, self::settingsOverridesSchemaV1());

        return $registry;
    }

    /**
     * @return array<string, mixed>
     */
    private static function settingsOverridesSchemaV1(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'additionalProperties' => [
                'type' => 'object',
                'additionalProperties' => true,
            ],
        ];
    }
}
