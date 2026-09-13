<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Commands;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Services\MediaStorageCapabilityProbe;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI probe for media storage drivers (Iteration 72).
 */
final class MediaStorageProbeCommand extends Command
{
    protected static string $defaultName = 'media:storage:probe';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private MediaStorageFactory $storageFactory,
        private MediaStorageCapabilityProbe $probe,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('media:storage:probe')
            ->setDescription('Probe configured media storage driver health without logging secrets');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mediaSettings = $this->settings->group('media');
        $active = $this->storageFactory->resolveActiveDriver($mediaSettings);
        $driver = $this->storageFactory->create(null, true, $mediaSettings);
        $result = $this->probe->probe($driver, $mediaSettings);

        $output->writeln('<info>Media storage probe</info>');
        $output->writeln('Configured: ' . ($result['storageDriver']['configured'] ?? 'unknown'));
        $output->writeln('Active: ' . $active);
        $output->writeln('Status: ' . ($result['storageDriver']['status'] ?? 'unknown'));
        $output->writeln('Health: ' . (($result['health']['message'] ?? 'n/a')));

        $s3Health = $result['s3Health'] ?? null;
        if (is_array($s3Health)) {
            $output->writeln('S3 health: ' . ($s3Health['message'] ?? 'n/a'));
        }

        $ok = ($result['health']['ok'] ?? false) === true;

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
