<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex\Commands;

use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexAdminService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QueryIndexRebuildCommand extends Command
{
    protected static string $defaultName = 'query-index:rebuild';

    public function __construct(
        private QueryIndexAdminService $admin
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('query-index:rebuild')
            ->setDescription('Rebuild content.sqlite from content.json (derived catalog, It.92)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $result = $this->admin->rebuild();
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<info>Rebuilt SQLite index: %d entries (JSON catalog: %d).</info>',
            $result['entries'],
            $result['json_entries']
        ));

        return Command::SUCCESS;
    }
}
