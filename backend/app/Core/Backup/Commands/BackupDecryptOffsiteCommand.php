<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Backup\Commands;

use PaginiumCMS\Core\Backup\Services\BackupOffsiteArchiveCipher;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Decrypts an off-site `*.zip.enc` back into a restorable backup ZIP.
 * The passphrase is never accepted as a CLI argument (shell history / process list).
 */
final class BackupDecryptOffsiteCommand extends Command
{
    public const PASSPHRASE_ENV = 'PAGINIUM_BACKUP_PASSPHRASE';

    public function __construct(
        private BackupOffsiteArchiveCipher $cipher,
        private SettingsRepositoryInterface $settings,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('backup:decrypt-offsite')
            ->setDescription('Decrypt an off-site *.zip.enc backup (formats s1/s2) into a ZIP for restore')
            ->addArgument('input', InputArgument::REQUIRED, 'Absolute path to the downloaded *.zip.enc file')
            ->addArgument('output', InputArgument::OPTIONAL, 'Absolute path for the decrypted ZIP (default: input without .enc)')
            ->addOption('from-settings', null, InputOption::VALUE_NONE, 'Use the stored backups.offsiteEncryptionKey (requires the same APP_KEY)')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite the output file if it exists')
            ->setHelp('Passphrase source order: --from-settings, env ' . self::PASSPHRASE_ENV . ', interactive hidden prompt.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = (string) $input->getArgument('input');
        $target = (string) ($input->getArgument('output') ?? '');
        if ($target === '') {
            $target = (string) preg_replace('/\.enc$/i', '', $source);
        }

        if (!is_file($source) || !is_readable($source)) {
            $io->error('Input file not found or not readable: ' . $source);

            return Command::FAILURE;
        }
        if ($target === $source) {
            $io->error('Output path must differ from the input path.');

            return Command::FAILURE;
        }
        if (file_exists($target) && !$input->getOption('force')) {
            $io->error('Output exists (use --force to overwrite): ' . $target);

            return Command::FAILURE;
        }

        $passphrase = $this->resolvePassphrase($input, $io);
        if ($passphrase === '') {
            $io->error('No passphrase provided.');

            return Command::FAILURE;
        }

        try {
            $this->cipher->decryptToFile($source, $target, $passphrase);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Decrypted backup written to ' . $target);

        return Command::SUCCESS;
    }

    private function resolvePassphrase(InputInterface $input, SymfonyStyle $io): string
    {
        if ($input->getOption('from-settings')) {
            return (string) ($this->settings->group('backups')['offsiteEncryptionKey'] ?? '');
        }

        $fromEnv = getenv(self::PASSPHRASE_ENV);
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        $question = new Question('Off-site encryption passphrase');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $answer = $io->askQuestion($question);

        return is_string($answer) ? $answer : '';
    }
}
