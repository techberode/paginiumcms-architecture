<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Commands;

use PaginiumCMS\Core\FlatFile\Services\ContentMediaIntegrityService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ContentVerifyMediaCommand extends Command
{
    protected static string $defaultName = 'content:verify-media';

    public function __construct(
        private ContentMediaIntegrityService $integrity,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('content:verify-media')
            ->setDescription('Lists broken /storage media references in pages and blog articles')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Max missing rows in human output', '50');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->integrity->scan();

        if ($input->getOption('json')) {
            $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output->writeln($encoded !== false ? $encoded : '{}');

            return $report['healthy'] ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('Content media integrity');

        $io->table(
            ['Metric', 'Value'],
            [
                ['Items scanned', (string) $report['contentItemsScanned']],
                ['Media references', (string) $report['referencesTotal']],
                ['Missing files', (string) $report['missingTotal']],
            ]
        );

        $limit = max(1, (int) $input->getOption('limit'));
        $rows = array_slice($report['missing'], 0, $limit);
        if ($rows !== []) {
            $io->section('Missing media (sample)');
            $io->table(
                ['Content', 'Field', 'Media path'],
                array_map(
                    static fn (array $row): array => [
                        $row['contentPath'],
                        $row['field'],
                        $row['mediaPath'],
                    ],
                    $rows
                )
            );
            if (count($report['missing']) > $limit) {
                $io->note(sprintf('… and %d more. Use --json for full list.', count($report['missing']) - $limit));
            }
        }

        if ($report['healthy']) {
            $io->success('All referenced media files exist on disk.');

            return Command::SUCCESS;
        }

        $io->warning('Broken media references detected. Restore blog and media from the same full backup, or run a full content restore.');

        return Command::FAILURE;
    }
}
