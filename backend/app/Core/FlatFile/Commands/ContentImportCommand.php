<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Commands;

use PaginiumCMS\Core\FlatFile\Services\ContentImportService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import pages/articles from JSON export or WordPress WXR (It.80f / 80g).
 *
 * Default is dry-run. Pass --run to write to flat-file SSOT.
 */
final class ContentImportCommand extends Command
{
    protected static string $defaultName = 'content:import';

    public function __construct(
        private ContentImportService $import,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('content:import')
            ->setDescription('Import pages/articles from JSON export or WordPress WXR XML')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to import file (.json or .xml)')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'json or wordpress (auto-detected from extension when omitted)')
            ->addOption('run', null, InputOption::VALUE_NONE, 'Persist imports (default is dry-run preview only)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = trim((string) $input->getOption('file'));
        if ($file === '') {
            $io->error('Missing required --file=/path/to/export.json|.xml');

            return Command::FAILURE;
        }

        if (!is_file($file)) {
            $io->error('Import file not found: ' . $file);

            return Command::FAILURE;
        }

        $format = strtolower(trim((string) ($input->getOption('format') ?? '')));
        if ($format === '') {
            $format = str_ends_with(strtolower($file), '.xml') ? 'wordpress' : 'json';
        }

        $dryRun = !$input->getOption('run');
        if ($dryRun) {
            $io->note('Dry-run mode — no files will be written. Pass --run to persist.');
        }

        $result = match ($format) {
            'json', 'export' => $this->import->importFromJsonFile($file, $dryRun),
            'wordpress', 'wxr', 'xml' => $this->import->importFromWordPressFile($file, $dryRun),
            default => null,
        };

        if ($result === null) {
            $io->error('Invalid --format. Use json or wordpress.');

            return Command::FAILURE;
        }

        if ($result->messages !== []) {
            $io->listing($result->messages);
        }

        if ($result->errors !== []) {
            $io->error('Import finished with errors:');
            $io->listing($result->errors);

            return Command::FAILURE;
        }

        $io->success(sprintf(
            '%s complete: %d item(s) %s, %d skipped.',
            $dryRun ? 'Dry-run' : 'Import',
            $result->created,
            $dryRun ? 'validated' : 'written',
            $result->skipped
        ));

        return Command::SUCCESS;
    }
}
