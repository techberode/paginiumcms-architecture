<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Admin/CLI operations for derived query index (It.92e).
 */
final class QueryIndexAdminService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private QueryIndexCapabilityProbe $probe,
        private QueryIndexRebuilder $rebuilder,
        private ContentIndexService $contentIndex
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $engine = $this->settings->group('engine');
        $probe = $this->probe->probe($engine);

        return [
            'configured_driver' => (string) ($engine['queryIndexDriver'] ?? QueryIndexInterface::DRIVER_JSON),
            'active_driver' => (string) ($probe['queryIndexDriver']['active'] ?? QueryIndexInterface::DRIVER_JSON),
            'probe' => $probe,
            'activation_ready' => $this->probe->runtimeReady() && $this->probe->parityWithJson(),
            'json_entries' => $this->contentIndex->countAllEntries(),
        ];
    }

    /**
     * @return array{entries: int, json_entries: int}
     */
    public function rebuild(): array
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new InvalidArgumentException('pdo_sqlite is not available on this server.');
        }

        $count = $this->rebuilder->rebuild();
        $jsonCount = $this->contentIndex->countAllEntries();
        if ($count !== $jsonCount) {
            throw new InvalidArgumentException('Rebuild finished with entry count mismatch vs JSON index.');
        }

        return ['entries' => $count, 'json_entries' => $jsonCount];
    }

    /**
     * @return array{driver: string, status: array<string, mixed>}
     */
    public function activateDriver(string $driver): array
    {
        if ($driver !== QueryIndexInterface::DRIVER_JSON && $driver !== QueryIndexInterface::DRIVER_SQLITE) {
            throw new InvalidArgumentException('Invalid query index driver.');
        }

        if ($driver === QueryIndexInterface::DRIVER_SQLITE && !$this->probe->verifyActivation()) {
            throw new InvalidArgumentException(
                'SQLite query index is not ready. Run rebuild and fix probe failures before enabling.'
            );
        }

        $values = $this->settings->setGroup('engine', ['queryIndexDriver' => $driver]);

        return [
            'driver' => (string) ($values['queryIndexDriver'] ?? $driver),
            'status' => $this->status(),
        ];
    }
}
