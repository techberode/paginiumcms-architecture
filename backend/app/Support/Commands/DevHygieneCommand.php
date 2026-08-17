<?php

declare(strict_types=1);

namespace PaginiumCMS\Support\Commands;

use PaginiumCMS\Support\DevStorageHygiene;
use PaginiumCMS\Support\TestArtifactNaming;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DevHygieneCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('dev:hygiene')
            ->setDescription('Scan or purge prefixed test artefacts only (never real content)')
            ->addOption('scan', null, InputOption::VALUE_NONE, 'List removable counts only')
            ->addOption('confirm', null, InputOption::VALUE_NONE, 'Purge prefixed test artefacts')
            ->addOption('include-logs', null, InputOption::VALUE_NONE, 'Also delete storage/logs/*')
            ->addOption('no-reindex', null, InputOption::VALUE_NONE, 'Skip content:diagnose --fix after purge')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Allow running when APP_ENV=production')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output for --scan');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            DevStorageHygiene::assertAllowedEnvironment((bool) $input->getOption('force'));
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($input->getOption('scan') || !$input->getOption('confirm')) {
            $counts = DevStorageHygiene::scan();

            if ($input->getOption('json')) {
                $output->writeln(json_encode([
                    'contentRoot' => DevStorageHygiene::contentRoot(),
                    'qaPrefix' => TestArtifactNaming::QA_PREFIX,
                    'counts' => $counts,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');

                if (!$input->getOption('confirm')) {
                    $io->note('Re-run with --confirm to purge only prefixed test artefacts.');
                }

                return Command::SUCCESS;
            }

            $io->title('PaginiumCMS dev storage scan (prefix-only purge)');
            $io->writeln('Content root: ' . DevStorageHygiene::contentRoot());
            $io->writeln('Test slug prefix: `' . TestArtifactNaming::QA_PREFIX . '` + known PHPUnit patterns');
            $io->table(
                ['Category', 'Count'],
                array_map(
                    static fn (string $key, int $value): array => [$key, (string) $value],
                    array_keys($counts),
                    array_values($counts)
                )
            );

            if (!$input->getOption('confirm')) {
                $io->note('Nothing deleted. Add --confirm to purge prefixed test artefacts only.');
            }

            return Command::SUCCESS;
        }

        $report = DevStorageHygiene::purge(
            (bool) $input->getOption('include-logs'),
            !$input->getOption('no-reindex')
        );

        $io->success('Dev storage hygiene finished.');
        $io->writeln(DevStorageHygiene::formatReport($report));

        return Command::SUCCESS;
    }
}
