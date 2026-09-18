<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\HybridEngine\QueryIndex\Commands;

use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexAdminService;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class QueryIndexStatusCommand extends Command
{
    protected static string $defaultName = 'query-index:status';

    public function __construct(
        private QueryIndexAdminService $admin
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('query-index:status')
            ->setDescription('Show JSON vs SQLite query index probe status (It.92)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = $this->admin->status();
        $output->writeln(JsonHelper::encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }
}
