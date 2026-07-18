<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Commands;

use PaginiumCMS\Core\Scheduler\Services\JobWorker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ProcessWorkerCommand extends Command
{
    public function __construct(
        private JobWorker $worker
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('worker:process')
            ->setDescription('Spracuje frontu manuálnych jobov (Iterácia 29)')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Max položiek', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = max(1, (int) $input->getOption('limit'));
        $result = $this->worker->process($limit);

        $output->writeln(sprintf('<info>Processed %d queued job(s).</info>', $result['processed']));

        foreach ($result['results'] as $entry) {
            $jobId = (string) ($entry['job_id'] ?? '?');
            $message = (string) ($entry['message'] ?? '');
            $output->writeln(sprintf('  · %s: %s', $jobId, $message));
        }

        return Command::SUCCESS;
    }
}
