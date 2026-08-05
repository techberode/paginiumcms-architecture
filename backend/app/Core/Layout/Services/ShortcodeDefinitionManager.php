<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Core\CodePolicy\Contracts\CodePolicyEngineInterface;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\CodePolicy\Services\ShortcodeDefinitionPolicy;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Layout\Models\ShortcodeRecord;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Shortcode definition save/preview with fail-closed policy gate (It.67a).
 *
 * Chain: ShortcodeDefinitionPolicy → validateUntrusted → write → registry update.
 * Preview uses the same validators without persisting.
 */
final class ShortcodeDefinitionManager
{
    public function __construct(
        private ShortcodeDefinitionPolicy $definitionPolicy,
        private CodePolicyEngineInterface $codePolicy,
        private ShortcodeRegistry $registry,
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $definitionsRelativeDir = 'data/shortcodes/definitions',
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $items = [];
        foreach ($this->registry->all() as $name => $record) {
            $items[] = $this->buildListItem($name, $record);
        }

        usort($items, static fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $name): array
    {
        $name = $this->normalizeName($name);
        $record = $this->registry->get($name);
        if ($record === null) {
            throw new RuntimeException('Shortcode not found: ' . $name);
        }

        $json = $this->readDefinitionFile($name);

        return [
            'record' => $record->toArray(),
            'definition' => JsonHelper::decode($json),
        ];
    }

    /**
     * Validates without writing — same gate as save (It.67a preview contract).
     *
     * @return array<string, mixed> normalized definition
     */
    public function preview(string $json): array
    {
        return $this->validatePayload($json);
    }

    /**
     * @return array<string, mixed>
     */
    public function save(string $name, string $json): array
    {
        $name = $this->normalizeName($name);
        $definition = $this->validatePayload($json);

        if (($definition['name'] ?? '') !== $name) {
            throw new RuntimeException('Definition name must match URL segment.');
        }

        $this->writer->createDirectory($this->definitionsRelativeDir);
        $relativePath = $this->definitionRelativePath($name);
        $canonical = JsonHelper::encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writer->write($relativePath, $canonical, false);

        $version = (int) ($definition['version'] ?? 1);
        $record = new ShortcodeRecord($name, true, $version, gmdate('c'));
        $this->registry->upsert($record);

        return $this->buildListItem($name, $record);
    }

    public function delete(string $name): void
    {
        $name = $this->normalizeName($name);
        if ($this->registry->get($name) === null) {
            throw new RuntimeException('Shortcode not found: ' . $name);
        }

        $relativePath = $this->definitionRelativePath($name);
        if ($this->reader->exists($relativePath)) {
            $this->writer->delete($relativePath, false);
        }

        $this->registry->remove($name);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(string $json): array
    {
        $this->definitionPolicy->validateJson($json);

        $decoded = JsonHelper::decode($json);
        $name = trim((string) ($decoded['name'] ?? ''));
        if ($name === '') {
            throw new CodePolicyViolationException([
                'schema' => ['Shortcode name is required'],
            ]);
        }

        $logicalPath = $this->definitionRelativePath($name);
        $this->codePolicy->validateUntrusted($logicalPath, $json);

        /** @var array<string, mixed> $decoded */
        $decoded = JsonHelper::decode($json);

        return $decoded;
    }

    private function readDefinitionFile(string $name): string
    {
        $relativePath = $this->definitionRelativePath($name);
        if (!$this->reader->exists($relativePath)) {
            throw new RuntimeException('Shortcode definition file missing: ' . $name);
        }

        return $this->reader->read($relativePath);
    }

    private function definitionRelativePath(string $name): string
    {
        return $this->definitionsRelativeDir . '/' . $name . '.json';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildListItem(string $name, ShortcodeRecord $record): array
    {
        return [
            'name' => $name,
            'enabled' => $record->enabled,
            'version' => $record->version,
            'updatedAt' => $record->updatedAt,
        ];
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || !preg_match('/^[a-z][a-z0-9_-]{0,39}$/', $name)) {
            throw new RuntimeException('Invalid shortcode name.');
        }

        return $name;
    }
}
