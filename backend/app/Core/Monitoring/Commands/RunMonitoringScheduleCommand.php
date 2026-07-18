<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Monitoring\Commands;

use PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunMonitoringScheduleCommand extends Command
{
    public function __construct(
        private MonitoringScheduler $scheduler
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('monitoring:run-schedule')
            ->setDescription('Spustí naplánované monitoring reporty a scan log incidentov (pre crontab)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->scheduler->runIfDue();

        $report = $result['report'];
        if (($report['sent'] ?? false) === true) {
            $output->writeln('<info>✅ Monitoring report odoslaný (' . ($report['connector'] ?? '?') . ').</info>');
        } else {
            $reason = $report['reason'] ?? 'not_due';
            $output->writeln('<comment>⏭ Report neodoslaný (' . $reason . ').</comment>');
        }

        $logs = $result['logs'];
        $output->writeln(sprintf(
            'Log scan: %d záznamov, %d notifikácií.',
            (int) ($logs['scanned'] ?? 0),
            (int) ($logs['notified'] ?? 0)
        ));

        return Command::SUCCESS;
    }
}
