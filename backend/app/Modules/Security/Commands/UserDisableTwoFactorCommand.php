<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Commands;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Emergency recovery after backup restore (old user slice vs current authenticator / APP_KEY).
 */
final class UserDisableTwoFactorCommand extends Command
{
    public function __construct(
        private UserRepository $users,
        private TwoFactorInterface $twoFactor,
        private FileReaderInterface $reader,
        private DemoMode $demoMode,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('user:disable-2fa')
            ->setDescription('Disable TOTP for a user in active storage (post-restore lockout recovery)')
            ->addArgument('email', InputArgument::REQUIRED, 'User e-mail');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        $io->note([
            'DEMO_MODE: ' . ($this->demoMode->isEnabled() ? 'true' : 'false'),
            'Base path: ' . $this->reader->getBasePath(),
        ]);

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            $io->error('User not found: ' . $email);
            $io->text('Try: php backend/bin/console user:list');

            return Command::FAILURE;
        }

        if (!$user->isTwoFactorEnabled()) {
            $io->warning('2FA was already disabled for ' . $email);

            return Command::SUCCESS;
        }

        $this->twoFactor->disableTwoFactor($user);
        $io->success('2FA disabled for ' . $email . '. Log in with password, then re-enable 2FA in Account → Security.');

        return Command::SUCCESS;
    }
}
