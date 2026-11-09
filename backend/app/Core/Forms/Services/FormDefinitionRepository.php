<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Forms\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Flat-file public form definitions at data/forms/definitions/{id}.json (It.101a).
 */
final class FormDefinitionRepository
{
    public function __construct(
        private FormDefinitionPolicy $policy,
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $definitionsRelativeDir = 'data/forms/definitions',
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
                $items[] = $this->toListEntry($definition);
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
        return $this->readNormalized($this->policy->normalizeId($id));
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function save(string $id, array $payload): array
    {
        $id = $this->policy->normalizeId($id);
        $payload['id'] = $id;
        $normalized = $this->policy->validate($payload);

        $this->writer->createDirectory($this->definitionsRelativeDir);
        $this->writer->write(
            $this->definitionRelativePath($id),
            JsonHelper::encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            false
        );

        $saved = $this->readNormalized($id);
        if ($saved === null) {
            throw new RuntimeException('Form definition was not stored.');
        }

        return $saved;
    }

    public function delete(string $id): void
    {
        $id = $this->policy->normalizeId($id);
        $path = $this->definitionRelativePath($id);
        if (!$this->reader->exists($path)) {
            throw new RuntimeException('Form not found: ' . $id);
        }

        $this->writer->delete($path, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function createFromPreset(string $presetId, string $customId = ''): array
    {
        $preset = FormDefinitionPresets::build($presetId);
        if ($preset === null) {
            throw new RuntimeException('Unknown preset.');
        }

        if ($customId !== '') {
            $preset['id'] = $this->policy->normalizeId($customId);
            unset($preset['preset']);
        }

        return $this->save((string) $preset['id'], $preset);
    }

    /**
     * @return list<string>
     */
    private function definitionFiles(): array
    {
        $dir = rtrim($this->reader->getBasePath(), '/') . '/' . $this->definitionsRelativeDir;
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.json');

        return is_array($files) ? $files : [];
    }

  /**
   * @return array<string, mixed>|null
   */
    private function readNormalized(string $id): ?array
    {
        $path = $this->definitionRelativePath($id);
        if (!$this->reader->exists($path)) {
            return null;
        }

        try {
            $data = JsonHelper::decode($this->reader->read($path));
        } catch (\Throwable) {
            return null;
        }

        $data['id'] = $id;

        return $this->policy->validate($data);
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function toListEntry(array $definition): array
    {
        return [
            'id' => $definition['id'],
            'title' => $definition['title'],
            'enabled' => $definition['enabled'],
            'preset' => $definition['preset'] ?? '',
            'fieldCount' => is_array($definition['fields'] ?? null) ? count($definition['fields']) : 0,
            'markup' => '[form id="' . $definition['id'] . '" /]',
            'widgetMarkup' => '[widget type="form-embed" id="' . $definition['id'] . '" /]',
        ];
    }

    private function definitionRelativePath(string $id): string
    {
        return $this->definitionsRelativeDir . '/' . $id . '.json';
    }
}
