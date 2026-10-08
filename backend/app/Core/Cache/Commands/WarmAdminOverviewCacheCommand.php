<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Commands;

use PaginiumCMS\Core\Cache\AdminOverviewCacheWarmer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class WarmAdminOverviewCacheCommand extends Command
{
    public function __construct(
        private AdminOverviewCacheWarmer $warmer
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('cache:warm-admin')
            ->setDescription('Pre-generates P2 admin overview caches (audit, jobs, APM, planner) — Iteration 100');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $segments = $this->warmer->warmAll();

        $output->writeln('<info>Warmed admin overview cache:</info> ' . implode(', ', $segments));

        return Command::SUCCESS;
    }
}
