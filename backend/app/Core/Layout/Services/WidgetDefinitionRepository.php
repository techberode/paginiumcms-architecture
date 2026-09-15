<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file custom widget definitions at data/widgets/definitions/{id}.json (It.93t-e).
 */
final class WidgetDefinitionRepository
{
    public function __construct(
        private WidgetDefinitionPolicy $policy,
        private CodePolicyEngineInterface $codePolicy,
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $definitionsRelativeDir = 'data/widgets/definitions',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $items = [];
        foreach ($this->definitionFiles() as $file) {
            $id = basename($file, '.json');
            $definition = $this->readNormalized($id);
            if ($definition !== null) {
                $items[] = $this->toCatalogEntry($definition);
            }
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $id): ?array
    {
        return $this->readNormalized($this->normalizeId($id));
    }

    /**
     * @param list<string> $reservedIds
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function save(string $id, array $payload, array $reservedIds): array
    {
        $id = $this->normalizeId($id);
        if (in_array($id, $reservedIds, true)) {
            throw new RuntimeException('Cannot overwrite a built-in widget type.');
        }

        $payload['id'] = $id;
        $this->policy->validate($payload);

        $canonical = JsonHelper::encode($this->normalizePayload($payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $relativePath = $this->definitionRelativePath($id);
        $this->codePolicy->validateUntrusted($relativePath, $canonical);

        $this->writer->createDirectory($this->definitionsRelativeDir);
        $this->writer->write($relativePath, $canonical, false);

        $saved = $this->readNormalized($id);
        if ($saved === null) {
            throw new RuntimeException('Widget definition was not stored.');
        }

        return $saved;
    }

    /**
     * @param list<string> $reservedIds
     */
    public function delete(string $id, array $reservedIds): void
    {
        $id = $this->normalizeId($id);
        if (in_array($id, $reservedIds, true)) {
            throw new RuntimeException('Cannot delete a built-in widget type.');
        }

        $relativePath = $this->definitionRelativePath($id);
        if (!$this->reader->exists($relativePath)) {
            throw new RuntimeException('Widget not found: ' . $id);
        }

        $this->writer->delete($relativePath, false);
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    public function toCatalogEntry(array $definition): array
    {
        return [
            'id' => (string) ($definition['id'] ?? ''),
            'label' => (string) ($definition['label'] ?? $definition['id'] ?? ''),
            'selfClosing' => (bool) ($definition['selfClosing'] ?? true),
            'fields' => $definition['fields'] ?? [],
            'defaults' => $definition['defaults'] ?? [],
            'source' => 'custom',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readNormalized(string $id): ?array
    {
        $relativePath = $this->definitionRelativePath($id);
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($this->reader->read($relativePath));
        } catch (\Throwable) {
            return null;
        }

        try {
            $this->policy->validate($decoded);
        } catch (CodePolicyViolationException) {
            return null;
        }

        return $this->normalizePayload($decoded);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array
    {
        $id = $this->normalizeId((string) ($payload['id'] ?? $payload['name'] ?? ''));
        $fields = [];
        $rawFields = $payload['fields'] ?? [];
        if (is_array($rawFields)) {
            foreach ($rawFields as $field) {
                if (!is_array($field)) {
                    continue;
                }
                $key = (string) ($field['key'] ?? '');
                $kind = (string) ($field['kind'] ?? 'string');
                $entry = ['key' => $key, 'kind' => $kind];
                if ($kind === 'tone') {
                    $entry['options'] = ['primary', 'success', 'warn', 'muted'];
                }
                $fields[] = $entry;
            }
        }

        $defaults = [];
        $rawDefaults = $payload['defaults'] ?? [];
        if (is_array($rawDefaults)) {
            foreach ($rawDefaults as $key => $value) {
                if (is_string($key) && is_scalar($value)) {
                    $defaults[$key] = (string) $value;
                }
            }
        }

        return [
            'id' => $id,
            'label' => trim((string) ($payload['label'] ?? $id)),
            'version' => 1,
            'selfClosing' => (bool) ($payload['selfClosing'] ?? true),
            'fields' => $fields,
            'defaults' => $defaults,
            'expand' => (string) ($payload['expand'] ?? ''),
            'source' => 'custom',
        ];
    }

    /**
     * @return list<string>
     */
    private function definitionFiles(): array
    {
        try {
            $files = $this->reader->listFiles($this->definitionsRelativeDir, '*.json');
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

    private function definitionRelativePath(string $id): string
    {
        return $this->definitionsRelativeDir . '/' . $id . '.json';
    }

    private function normalizeId(string $id): string
    {
        $id = strtolower(trim($id));
        if ($id === '' || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $id)) {
            throw new RuntimeException('Invalid widget id.');
        }

        return $id;
    }
}
