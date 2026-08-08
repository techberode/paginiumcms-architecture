<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Commands;

use PaginiumCMS\Core\Content\LocalizedContentMigrationService;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Schema v2 locale migration CLI (Iteration 73 Phase 2d).
 */
final class ContentLocaleMigrateCommand extends Command
{
    protected static string $defaultName = 'content:locale-migrate';

    public function __construct(
        private LocalizedContentMigrationService $migration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('content:locale-migrate')
            ->setDescription('Inventory, dry-run, convert, or rollback legacy content to schema v2 locales')
            ->addArgument(
                'action',
                InputArgument::REQUIRED,
                'Action: inventory, dry-run, run, rollback'
            )
            ->addOption('default-locale', null, InputOption::VALUE_REQUIRED, 'Target default locale for conversion', 'sk')
            ->addOption('migration-id', null, InputOption::VALUE_REQUIRED, 'Migration id (run/rollback)')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Confirm destructive run or rollback')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = strtolower(trim((string) $input->getArgument('action')));
        $asJson = (bool) $input->getOption('json');

        try {
            $report = match ($action) {
                'inventory' => $this->migration->inventory(),
                'dry-run' => $this->migration->dryRun((string) $input->getOption('default-locale')),
                'run' => $this->migration->run(
                    (string) $input->getOption('default-locale'),
                    $input->getOption('migration-id') !== null ? (string) $input->getOption('migration-id') : null,
                    (bool) $input->getOption('yes')
                ),
                'rollback' => $this->migration->rollback(
                    (string) ($input->getOption('migration-id') ?? ''),
                    (bool) $input->getOption('yes')
                ),
                default => throw new FlatFileException('Unknown action: ' . $action),
            };
        } catch (FlatFileException $exception) {
            if ($asJson) {
                $encoded = json_encode([
                    'ok' => false,
                    'error' => $exception->getMessage(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $output->writeln($encoded !== false ? $encoded : '{}');
            } else {
                $io->error($exception->getMessage());
            }

            return Command::FAILURE;
        }

        if ($asJson) {
            $encoded = JsonHelper::encode($report);
            $output->writeln($encoded);

            return $this->resolveExitCode($action, $report);
        }

        return $this->renderHumanOutput($io, $action, $report);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderHumanOutput(SymfonyStyle $io, string $action, array $report): int
    {
        match ($action) {
            'inventory' => $this->renderInventory($io, $report),
            'dry-run' => $this->renderDryRun($io, $report),
            'run' => $this->renderRun($io, $report),
            'rollback' => $this->renderRollback($io, $report),
            default => null,
        };

        return $this->resolveExitCode($action, $report);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderInventory(SymfonyStyle $io, array $report): void
    {
        $io->title('Locale migration inventory');
        /** @var array<string, int> $totals */
        $totals = is_array($report['totals'] ?? null) ? $report['totals'] : [];
        $io->table(
            ['Metric', 'Count'],
            [
                ['Documents', (string) ($totals['documents'] ?? 0)],
                ['Legacy single-locale', (string) ($totals['legacySingleLocale'] ?? 0)],
                ['Schema v2', (string) ($totals['schemaV2'] ?? 0)],
                ['Locale copy candidates', (string) ($totals['localeCopyCandidates'] ?? 0)],
                ['Conflicts', (string) ($totals['conflicts'] ?? 0)],
            ]
        );

        if (($report['conflicts'] ?? []) !== []) {
            $io->section('Conflicts (manual resolution required)');
            foreach ($report['conflicts'] as $conflict) {
                $io->writeln('- ' . ($conflict['message'] ?? 'conflict') . ': ' . implode(', ', $conflict['paths'] ?? []));
            }
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderDryRun(SymfonyStyle $io, array $report): void
    {
        $io->title('Locale migration dry-run');
        $io->writeln('Proposed migration id: ' . ($report['migrationId'] ?? ''));
        $io->writeln('Default locale: ' . ($report['defaultLocale'] ?? ''));
        $io->writeln('Would convert: ' . ($report['wouldConvert'] ?? 0));
        $io->writeln('Skipped: ' . ($report['skipped'] ?? 0));

        if (($report['blockingConflicts'] ?? false) === true) {
            $io->warning('Blocking conflicts detected. Resolve before running migration.');
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderRun(SymfonyStyle $io, array $report): void
    {
        if (($report['converted'] ?? 0) === 0) {
            $io->note($report['message'] ?? 'Nothing converted.');

            return;
        }

        $io->success(sprintf(
            'Converted %d document(s). Migration id: %s',
            (int) $report['converted'],
            (string) ($report['migrationId'] ?? '')
        ));
        $io->writeln('Manifest: ' . ($report['manifestPath'] ?? ''));
    }

    /**
     * @param array<string, mixed> $report
     */
    private function renderRollback(SymfonyStyle $io, array $report): void
    {
        $io->success(sprintf(
            'Restored %d document(s) from migration %s',
            (int) ($report['restored'] ?? 0),
            (string) ($report['migrationId'] ?? '')
        ));
    }

    /**
     * @param array<string, mixed> $report
     */
    private function resolveExitCode(string $action, array $report): int
    {
        if ($action === 'inventory' || $action === 'dry-run') {
            return ($report['blockingConflicts'] ?? false) === true ? Command::FAILURE : Command::SUCCESS;
        }

        if ($action === 'run' && isset($report['verified']) && is_array($report['verified'])) {
            foreach ($report['verified'] as $item) {
                if (($item['ok'] ?? false) !== true) {
                    return Command::FAILURE;
                }
            }
        }

        return Command::SUCCESS;
    }
}
