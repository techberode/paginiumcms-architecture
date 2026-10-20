<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Commands;

use PaginiumCMS\Core\Backup\Services\BackupOffsiteWebDavService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Diagnose Nextcloud / WebDAV off-site backup settings and optionally run a tiny upload.
 */
final class BackupOffsiteProbeCommand extends Command
{
    public function __construct(
        private BackupOffsiteWebDavService $offsite,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('backup:offsite-probe')
            ->setDescription('Check off-site WebDAV backup settings and optionally test upload')
            ->addOption('upload', null, InputOption::VALUE_NONE, 'Upload a small probe file (removed from local disk after test)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $diag = $this->offsite->diagnose();

        $output->writeln('<info>Off-site WebDAV diagnostics</info>');
        foreach ($diag as $key => $value) {
            if (is_bool($value)) {
                $output->writeln(sprintf('  %s: %s', $key, $value ? 'yes' : 'no'));
            } elseif ($value !== null && $value !== '') {
                $output->writeln(sprintf('  %s: %s', $key, (string) $value));
            }
        }

        if (($diag['ready'] ?? false) !== true) {
            $output->writeln('<error>Not ready for upload — fix settings above (System → Backups off-site).</error>');

            return Command::FAILURE;
        }

        if ($input->getOption('upload') !== true) {
            $output->writeln('<comment>Config looks OK. Re-run with --upload to test WebDAV PUT.</comment>');

            return Command::SUCCESS;
        }

        $result = $this->offsite->probeUpload();
        if ($result['uploaded'] === true) {
            $output->writeln('<info>Probe upload OK.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<error>Probe upload failed: ' . (string) ($result['error'] ?? 'unknown') . '</error>');

        return Command::FAILURE;
    }
}
