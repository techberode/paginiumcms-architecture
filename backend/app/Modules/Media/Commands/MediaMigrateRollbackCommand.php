<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Commands;

use PaginiumCMS\Modules\Media\Exception\MediaMigrationException;
use PaginiumCMS\Modules\Media\Services\MediaMigrationService;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MediaMigrateRollbackCommand extends Command
{
    protected static string $defaultName = 'media:migrate:rollback';

    public function __construct(
        private MediaMigrationService $migration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('media:migrate:rollback')
            ->setDescription('Rollback a media storage migration using the flat-file journal')
            ->addArgument('migration-id', InputArgument::REQUIRED, 'Migration journal id')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirm rollback')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $asJson = (bool) $input->getOption('json');
        $migrationId = (string) $input->getArgument('migration-id');

        try {
            $report = $this->migration->rollback($migrationId, (bool) $input->getOption('yes'));
        } catch (MediaMigrationException $exception) {
            if ($asJson) {
                $output->writeln(JsonHelper::encode(['ok' => false, 'error' => $exception->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $io->error($exception->getMessage());
            }

            return Command::FAILURE;
        }

        if ($asJson) {
            $output->writeln(JsonHelper::encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Rollback complete. restoredUrls=%d deletedTargets=%d activeDriver=%s',
            (int) ($report['restoredUrls'] ?? 0),
            (int) ($report['deletedTargets'] ?? 0),
            (string) ($report['activeDriver'] ?? ''),
        ));

        return Command::SUCCESS;
    }
}
