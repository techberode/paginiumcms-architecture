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

/**
 * Media storage migration CLI (Iteration 72c).
 */
final class MediaMigrateCommand extends Command
{
    protected static string $defaultName = 'media:migrate';

    public function __construct(
        private MediaMigrationService $migration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('media:migrate')
            ->setDescription('Inventory, dry-run, start, copy batches, or cutover media storage migration')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Action: inventory, dry-run, start, copy, cutover'
            )
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source driver', 'local')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Target driver', 's3')
            ->addOption('migration-id', null, InputOption::VALUE_REQUIRED, 'Migration journal id')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Copy batch size', '20')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirm cutover')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = strtolower(trim((string) $input->getArgument('action')));
        $asJson = (bool) $input->getOption('json');
        $source = (string) $input->getOption('source');
        $target = (string) $input->getOption('target');

        if ($action === 'copy' && ($input->getOption('migration-id') === null || $input->getOption('migration-id') === '')) {
            return $this->fail($io, $output, $asJson, 'Copy requires --migration-id.');
        }

        if ($action === 'cutover' && ($input->getOption('migration-id') === null || $input->getOption('migration-id') === '')) {
            return $this->fail($io, $output, $asJson, 'Cutover requires --migration-id.');
        }

        try {
            $report = match ($action) {
                'inventory' => $this->migration->inventory($source, $target),
                'dry-run' => $this->migration->dryRun($source, $target),
                'start' => $this->migration->start(
                    $source,
                    $target,
                    $input->getOption('migration-id') !== null ? (string) $input->getOption('migration-id') : null,
                ),
                'copy' => $this->migration->copyBatch(
                    (string) $input->getOption('migration-id'),
                    (int) $input->getOption('batch-size'),
                    false,
                ),
                'cutover' => $this->migration->cutover(
                    (string) $input->getOption('migration-id'),
                    (bool) $input->getOption('yes'),
                ),
                default => throw new MediaMigrationException('Unknown action: ' . $action),
            };
        } catch (MediaMigrationException $exception) {
            return $this->fail($io, $output, $asJson, $exception->getMessage());
        }

        if ($asJson) {
            $output->writeln(JsonHelper::encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }

        $this->renderReport($io, $action, $report);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderReport(SymfonyStyle $io, string $action, array $report): void
    {
        match ($action) {
            'inventory' => $io->success(sprintf(
                'Inventory: %d items, %d bytes, %d missing on source.',
                (int) ($report['itemCount'] ?? 0),
                (int) ($report['totalBytes'] ?? 0),
                (int) ($report['missingOnSource'] ?? 0),
            )),
            'dry-run' => $io->success(sprintf(
                'Dry-run ok=%s, proposed migration id=%s',
                ($report['canMigrate'] ?? false) ? 'yes' : 'no',
                (string) ($report['migrationId'] ?? ''),
            )),
            'start' => $io->success(sprintf(
                'Migration started: %s (%d items). Run media:migrate copy --migration-id=%s',
                (string) ($report['migrationId'] ?? ''),
                (int) ($report['itemCount'] ?? 0),
                (string) ($report['migrationId'] ?? ''),
            )),
            'copy' => $io->success(sprintf(
                'Copy batch: copied=%d failed=%d pending=%d complete=%s',
                (int) ($report['copied'] ?? 0),
                (int) ($report['failed'] ?? 0),
                (int) ($report['pending'] ?? 0),
                ($report['complete'] ?? false) ? 'yes' : 'no',
            )),
            'cutover' => $io->success(sprintf(
                'Cutover complete. Active driver: %s',
                (string) ($report['activeDriver'] ?? ''),
            )),
            default => $io->writeln(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: ''),
        };
    }

    private function fail(SymfonyStyle $io, OutputInterface $output, bool $asJson, string $message): int
    {
        if ($asJson) {
            $output->writeln(JsonHelper::encode(['ok' => false, 'error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $io->error($message);
        }

        return Command::FAILURE;
    }
}
