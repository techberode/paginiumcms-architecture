<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance\Commands;

use PaginiumCMS\Core\Performance\HostMetricsCollector;
use PaginiumCMS\Core\Performance\HostMetricsStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class HostMetricsCollectCommand extends Command
{
    public function __construct(
        private HostMetricsCollector $collector,
        private HostMetricsStore $store
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('metrics:host-collect')
            ->setDescription('Collect host CPU/RAM/disk snapshot into data/metrics/host-latest.json (It.82d)')
            ->addOption('disk-path', null, InputOption::VALUE_OPTIONAL, 'Path for disk_free_space probe', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $diskPath = trim((string) $input->getOption('disk-path'));
        $snapshot = $this->collector->collect($diskPath !== '' ? $diskPath : null);
        if ($snapshot === null) {
            $output->writeln('<error>Unable to collect host metrics on this environment.</error>');

            return Command::FAILURE;
        }

        $this->store->save($snapshot);
        $output->writeln('<info>Host metrics snapshot saved.</info>');

        return Command::SUCCESS;
    }
}
