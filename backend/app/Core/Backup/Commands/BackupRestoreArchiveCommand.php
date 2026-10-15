<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Commands;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentMediaIntegrityService;
use PaginiumCMS\Modules\Media\Services\MediaRegistryReconcileService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Restore flat-file content directly from a ZIP path (downloaded backup). */
final class BackupRestoreArchiveCommand extends Command
{
    public function __construct(
        private BackupInterface $backup,
        private MediaRegistryReconcileService $mediaRegistry,
        private ContentMediaIntegrityService $contentMedia,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('backup:restore-archive')
            ->setDescription('Restore content from a backup ZIP file path (same as admin Restore, no library id)')
            ->addArgument('zipPath', InputArgument::REQUIRED, 'Absolute or relative path to .zip archive');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $zipPath = (string) $input->getArgument('zipPath');

        if (!is_file($zipPath)) {
            $io->error('ZIP not found: ' . $zipPath);

            return Command::FAILURE;
        }

        try {
            $archiveMedia = $this->backup->inspectMediaCoverage($zipPath);
            if (!$archiveMedia['healthy']) {
                $io->warning(sprintf(
                    'Archive lists %d media paths in manifest but %d binaries are missing inside the ZIP.',
                    $archiveMedia['manifestMediaFiles'],
                    count($archiveMedia['manifestMediaWithoutZipBinary'])
                ));
            }

            $this->backup->importBackup($zipPath);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $registry = $this->mediaRegistry->report();
        $integrity = $this->contentMedia->scan();

        $io->success('Restore from archive completed.');
        $io->table(
            ['Check', 'Value'],
            [
                ['Registry missing file', (string) count($registry['registryMissingFile'])],
                ['Content broken media refs', (string) $integrity['missingTotal']],
                ['ZIP media binaries', (string) $archiveMedia['zipMediaBinaries']],
            ]
        );

        if ($registry['registryMissingFile'] !== []) {
            $io->warning('Registry rows without disk file — check storage permissions or retry after binary-safe restore build.');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
