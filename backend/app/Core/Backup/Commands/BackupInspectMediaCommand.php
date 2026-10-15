<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Commands;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class BackupInspectMediaCommand extends Command
{
    public function __construct(
        private BackupInterface $backup,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('backup:inspect-media')
            ->setDescription('Check whether a backup ZIP (restore chain) actually contains media binaries vs manifest')
            ->addArgument('reference', InputArgument::REQUIRED, 'Backup id (backup_…), path to .json metadata, or path to .zip')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $reference = (string) $input->getArgument('reference');

        try {
            $report = $this->backup->inspectMediaCoverage($reference);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($input->getOption('json')) {
            $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output->writeln($encoded !== false ? $encoded : '{}');

            return $report['healthy'] ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Backup media coverage');
        $io->table(
            ['Check', 'Value'],
            [
                ['Mode', (string) $report['mode']],
                ['ZIPs in restore chain', (string) $report['restoreChainBackups']],
                ['Media files in manifest', (string) $report['manifestMediaFiles']],
                ['Media binaries in chain ZIPs', (string) $report['zipMediaBinaries']],
                ['Manifest rows without binary in ZIP', (string) count($report['manifestMediaWithoutZipBinary'])],
            ]
        );

        if ($report['manifestMediaWithoutZipBinary'] !== []) {
            $io->section('Catalog-only rows (restore cannot recreate these files)');
            $io->listing(array_slice($report['manifestMediaWithoutZipBinary'], 0, 25));
            if (count($report['manifestMediaWithoutZipBinary']) > 25) {
                $io->note('Use --json for the full list.');
            }
            $io->warning('This backup is incomplete for media. Re-upload or use another archive.');

            return Command::FAILURE;
        }

        $io->success('Manifest media paths match binaries in the restore chain.');

        return Command::SUCCESS;
    }
}
