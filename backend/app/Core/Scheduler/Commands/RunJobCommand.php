<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Commands;

use PaginiumCMS\Core\Scheduler\Services\ScheduledJobRunner;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs a single scheduled job by id (production diagnostics).
 */
final class RunJobCommand extends Command
{
    public function __construct(private ScheduledJobRunner $runner)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('jobs:run')
            ->setDescription('Spustí jeden job podľa id (napr. backup-scheduled)')
            ->addArgument('id', InputArgument::REQUIRED, 'Job id from data/jobs/registry.json');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (string) $input->getArgument('id');
        $result = $this->runner->runJobById($id);

        $output->writeln(JsonHelper::encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (($result['error'] ?? null) === 'Job not found') {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
