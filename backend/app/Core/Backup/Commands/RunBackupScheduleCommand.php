<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Commands;

use PaginiumCMS\Core\Backup\Services\BackupScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunBackupScheduleCommand extends Command
{
    public function __construct(
        private BackupScheduler $scheduler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('backup:run-schedule')
            ->setDescription('Spustí naplánovanú zálohu, ak je termín splnený (pre crontab)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->scheduler->runIfDue();

        if ($result['ran'] === true) {
            $output->writeln('<info>✅ Záloha vytvorená.</info>');
            if (isset($result['backup']) && is_object($result['backup']) && method_exists($result['backup'], 'getId')) {
                $output->writeln('ID: ' . $result['backup']->getId());
            }

            return Command::SUCCESS;
        }

        $reason = $result['reason'] ?? 'unknown';
        $output->writeln('<comment>⏭ Záloha nebola spustená (' . $reason . ').</comment>');

        return Command::SUCCESS;
    }
}
