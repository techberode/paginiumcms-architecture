<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex;

use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Resolves active query index driver (It.92). Fail closed / fail open to JSON.
 */
final class QueryIndexFactory
{
    public function __construct(
        private ContentIndexService $contentIndex,
        private SettingsRepositoryInterface $settings,
        private QueryIndexCapabilityProbe $probe,
        private SqliteQueryIndex $sqliteIndex,
        private QueryIndexFailureHandler $failureHandler
    ) {
    }

    public function create(): QueryIndexInterface
    {
        $json = new JsonQueryIndex($this->contentIndex);
        $driver = (string) ($this->settings->get('engine.queryIndexDriver') ?? QueryIndexInterface::DRIVER_JSON);
        if ($driver !== QueryIndexInterface::DRIVER_SQLITE) {
            return $json;
        }

        if (!$this->probe->runtimeReady()) {
            $this->failureHandler->handleDetectedIssue();

            return $json;
        }

        return new FallbackQueryIndex($this->sqliteIndex, $json, $this->failureHandler);
    }
}
