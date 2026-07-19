<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Commands;

use PaginiumCMS\Modules\Demo\Services\DemoResetScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunDemoResetCommand extends Command
{
    public function __construct(
        private DemoResetScheduler $scheduler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('demo:reset-if-due')
            ->setDescription('Obnoví demo snapshot, ak uplynul DEMO_AUTO_RESET_MINUTES (pre crontab)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->scheduler->runIfDue();

        if ($result['ran'] === true) {
            $written = $result['written'] ?? 0;
            $output->writeln('<info>✅ Demo snapshot obnovený (' . $written . ' súborov).</info>');

            return Command::SUCCESS;
        }

        $reason = $result['reason'] ?? 'unknown';
        $output->writeln('<comment>⏭ Demo reset nebol spustený (' . $reason . ').</comment>');

        return Command::SUCCESS;
    }
}
