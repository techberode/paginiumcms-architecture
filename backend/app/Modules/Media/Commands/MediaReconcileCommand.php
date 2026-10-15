<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Commands;

use PaginiumCMS\Modules\Media\Services\MediaRegistryReconcileService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MediaReconcileCommand extends Command
{
    protected static string $defaultName = 'media:reconcile';

    public function __construct(
        private MediaRegistryReconcileService $reconcile,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('media:reconcile')
            ->setDescription('Compare media/registry.json with files on disk (post-restore drift)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output')
            ->addOption('prune-registry', null, InputOption::VALUE_NONE, 'Remove registry entries whose file is missing on disk');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $actions = [];

        if ($input->getOption('prune-registry')) {
            $removed = $this->reconcile->pruneRegistryMissingFiles();
            $actions[] = 'pruned ' . $removed . ' registry entries without files';
        }

        $report = $this->reconcile->report();
        if ($actions !== []) {
            $report['actions'] = $actions;
        }

        if ($input->getOption('json')) {
            $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output->writeln($encoded !== false ? $encoded : '{}');

            return $report['healthy'] ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('Media registry vs disk');

        $io->table(
            ['Check', 'Value'],
            [
                ['Registry entries', (string) $report['registryEntries']],
                ['Registry row, file missing', (string) count($report['registryMissingFile'])],
                ['File on disk, not in registry', (string) count($report['diskWithoutRegistry'])],
            ]
        );

        if ($report['registryMissingFile'] !== []) {
            $io->section('Registry without file (admin thumbs blank / 404)');
            $io->listing(array_slice($report['registryMissingFile'], 0, 30));
            if (count($report['registryMissingFile']) > 30) {
                $io->note('Use --json for full list.');
            }
        }

        if ($report['diskWithoutRegistry'] !== []) {
            $io->section('Files on disk not in registry');
            $io->listing(array_slice($report['diskWithoutRegistry'], 0, 30));
        }

        if ($actions !== []) {
            $io->success(implode('; ', $actions));
        }

        if ($report['healthy']) {
            $io->success('Registry matches disk.');

            return Command::SUCCESS;
        }

        $io->warning(
            'Drift detected. Prefer a full backup restore (blog+media), or --prune-registry then re-upload missing assets.'
        );

        return Command::FAILURE;
    }
}
