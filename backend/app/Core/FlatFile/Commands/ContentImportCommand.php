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
 * Import pages/articles from JSON export or external CMS sources (It.80f / 80g).
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
            ->setDescription('Import pages/articles from Paginium JSON or WordPress, Grav, Jekyll, Hugo, Ghost')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to import file (.json, .xml, .zip)')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Directory path for grav/jekyll/hugo imports')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'json, wordpress, grav, jekyll, hugo, ghost (auto-detected from extension when omitted)'
            )
            ->addOption('run', null, InputOption::VALUE_NONE, 'Persist imports (default is dry-run preview only)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = trim((string) $input->getOption('file'));
        $directory = trim((string) $input->getOption('path'));
        $format = strtolower(trim((string) ($input->getOption('format') ?? '')));

        if ($file === '' && $directory === '') {
            $io->error('Provide --file=export.xml|.json|.zip or --path=/site/user/pages for directory imports');

            return Command::FAILURE;
        }

        $dryRun = !$input->getOption('run');
        if ($dryRun) {
            $io->note('Dry-run mode — no files will be written. Pass --run to persist.');
        }

        if ($directory !== '') {
            if ($format === '') {
                $io->error('Directory import requires --format=grav|jekyll|hugo');

                return Command::FAILURE;
            }

            if (!is_dir($directory)) {
                $io->error('Import directory not found: ' . $directory);

                return Command::FAILURE;
            }

            $result = $this->import->importFromFormat($format, $directory, $dryRun);
        } else {
            if (!is_file($file)) {
                $io->error('Import file not found: ' . $file);

                return Command::FAILURE;
            }

            if ($format === '') {
                $format = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
                    'xml' => 'wordpress',
                    'json' => 'json',
                    'zip' => 'auto',
                    default => 'json',
                };
            }

            if ($format === 'json' || $format === 'export') {
                $result = $this->import->importFromJsonFile($file, $dryRun);
            } elseif ($format === 'auto') {
                $result = $this->import->importFromUploadedFile('auto', $file, basename($file), $dryRun);
            } else {
                $result = $this->import->importFromFormat($format, $file, $dryRun);
            }
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
