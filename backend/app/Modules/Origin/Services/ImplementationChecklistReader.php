<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Origin\Services;

use PaginiumCMS\Support\AppRoot;
use RuntimeException;

/**
 * Reads docs/manifest/implementation-checklist.json (operator slices for Origin Panel).
 */
final class ImplementationChecklistReader
{
    private const DEFAULT_RELATIVE_PATH = 'docs/manifest/implementation-checklist.json';

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        $path = $this->resolvePath();
        if (!is_readable($path)) {
            return [
                'schemaVersion' => 1,
                'updatedAt' => '',
                'slices' => [],
            ];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException('Implementation checklist manifest could not be loaded.');
        }

        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Implementation checklist manifest must be a JSON object.');
        }

        return $decoded;
    }

    private function resolvePath(): string
    {
        $root = AppRoot::resolve();
        if ($root !== null) {
            return $root . '/' . self::DEFAULT_RELATIVE_PATH;
        }

        return dirname(__DIR__, 5) . '/' . self::DEFAULT_RELATIVE_PATH;
    }
}
