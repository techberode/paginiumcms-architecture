<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Commands;

use PaginiumCMS\Core\Scheduler\Services\ScheduledJobRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunSchedulerCommand extends Command
{
    public function __construct(
        private ScheduledJobRunner $runner
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('scheduler:run')
            ->setDescription('Spustí všetky due joby z registry (pre crontab, Iterácia 29)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->runner->runDue();

        $output->writeln(sprintf('<info>Executed %d job(s).</info>', $result['executed']));

        foreach ($result['results'] as $entry) {
            $jobId = (string) ($entry['job_id'] ?? '?');
            $success = (bool) ($entry['success'] ?? false);
            $message = (string) ($entry['message'] ?? '');
            $tag = $success ? 'info' : 'comment';
            $output->writeln(sprintf('<%s>  · %s: %s</%s>', $tag, $jobId, $message, $tag));
        }

        return Command::SUCCESS;
    }
}
