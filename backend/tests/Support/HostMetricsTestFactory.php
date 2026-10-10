<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Performance\HostMetricsService;
use PaginiumCMS\Core\Performance\HostMetricsSettings;
use PaginiumCMS\Core\Performance\HostMetricsStore;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

final class HostMetricsTestFactory
{
    public static function service(
        SettingsRepositoryInterface $settings,
        FileReaderInterface $reader,
        FileWriterInterface $writer
    ): HostMetricsService {
        return new HostMetricsService(
            new HostMetricsSettings($settings),
            new HostMetricsStore($reader, $writer)
        );
    }
}
