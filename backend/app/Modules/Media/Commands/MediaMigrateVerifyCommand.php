<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Commands;

use PaginiumCMS\Modules\Media\Exception\MediaMigrationException;
use PaginiumCMS\Modules\Media\Services\MediaMigrationService;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class MediaMigrateVerifyCommand extends Command
{
    protected static string $defaultName = 'media:migrate:verify';

    public function __construct(
        private MediaMigrationService $migration,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('media:migrate:verify')
            ->setDescription('Verify checksum parity for a media storage migration journal')
            ->addArgument('migration-id', InputArgument::REQUIRED, 'Migration journal id')
            ->addOption('json', null, InputOption::VALUE_NONE, 'JSON output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $asJson = (bool) $input->getOption('json');
        $migrationId = (string) $input->getArgument('migration-id');

        try {
            $report = $this->migration->verify($migrationId);
        } catch (MediaMigrationException $exception) {
            if ($asJson) {
                $output->writeln(JsonHelper::encode(['ok' => false, 'error' => $exception->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $io->error($exception->getMessage());
            }

            return Command::FAILURE;
        }

        if ($asJson) {
            $output->writeln(JsonHelper::encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return ($report['allVerified'] ?? false) ? Command::SUCCESS : Command::FAILURE;
        }

        $io->success(sprintf(
            'Verify: verified=%d failed=%d allVerified=%s status=%s',
            (int) ($report['verified'] ?? 0),
            (int) ($report['failed'] ?? 0),
            ($report['allVerified'] ?? false) ? 'yes' : 'no',
            (string) ($report['status'] ?? ''),
        ));

        return ($report['allVerified'] ?? false) ? Command::SUCCESS : Command::FAILURE;
    }
}
