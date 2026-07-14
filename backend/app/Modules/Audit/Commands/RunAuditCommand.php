<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Audit\Commands;

use PaginiumCMS\Modules\Audit\Services\AuditEngine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Konzolový príkaz pre spustenie auditu.
 */
class RunAuditCommand extends Command
{
    private AuditEngine $auditEngine;

    public function __construct(AuditEngine $auditEngine)
    {
        parent::__construct();
        $this->auditEngine = $auditEngine;
    }

    protected function configure(): void
    {
        $this
            ->setName('audit:run')
            ->setDescription('Spustí audit systému')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Formát výstupu (json, table)',
                'table'
            )
            ->addOption(
                'auditors',
                'a',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Spustiť iba vybraných auditorov (security, integrity, configuration, compatibility, performance)',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');
        $auditors = $input->getOption('auditors');

        if (!empty($auditors)) {
            $report = $this->auditEngine->runSelected($auditors);
        } else {
            $report = $this->auditEngine->run();
        }

        if ($format === 'json') {
            $output->writeln($report->toJson());
        } else {
            $this->renderTable($output, $report);
        }

        return $report->isPassed() ? Command::SUCCESS : Command::FAILURE;
    }

    private function renderTable(OutputInterface $output, $report): void
    {
        $output->writeln('');
        $output->writeln('<info>=== Audit Report ===</info>');
        $output->writeln('');

        $output->writeln('ID: ' . $report->getId());
        $output->writeln('Čas: ' . $report->getTimestamp());
        $output->writeln('Stav: ' . ($report->isPassed() ? '<info>✅ PASOVAL</info>' : '<error>❌ ZLYHAL</error>'));
        $output->writeln('');

        $severityCounts = $report->getSeverityCounts();
        $output->writeln('Zhrnutie:');
        $output->writeln('  - Celkom: ' . $report->getTotalIssues());
        foreach ($severityCounts as $severity => $count) {
            if ($count > 0) {
                $color = match ($severity) {
                    'critical' => 'error',
                    'error' => 'error',
                    'warning' => 'comment',
                    default => 'info',
                };
                $output->writeln(sprintf('  - <fg=%s>%s</>: %d', $color, strtoupper($severity), $count));
            }
        }
        $output->writeln('');

        if ($report->getTotalIssues() === 0) {
            $output->writeln('<info>✅ Všetky kontroly prešli úspešne.</info>');
            return;
        }

        $output->writeln('<comment>Podrobnosti:</comment>');
        $output->writeln('');

        foreach ($report->getIssues() as $issue) {
            $color = match ($issue->getSeverity()) {
                'critical' => 'error',
                'error' => 'error',
                'warning' => 'comment',
                default => 'info',
            };

            $output->writeln(sprintf(
                '<fg=%s>[%s]</> <options=bold>%s</>',
                $color,
                strtoupper($issue->getSeverity()),
                $issue->getTitle()
            ));
            $output->writeln('  ' . $issue->getDescription());

            if ($issue->getRecommendation()) {
                $output->writeln('  <comment>💡 Odporúčanie:</comment> ' . $issue->getRecommendation());
            }

            if ($issue->getFile()) {
                $output->writeln('  📁 ' . $issue->getFile() . ($issue->getLine() ? ':' . $issue->getLine() : ''));
            }

            $output->writeln('');
        }
    }
}
