<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Commands;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ResetUserPasswordCommand extends Command
{
    public function __construct(
        private UserRepository $users,
        private PasswordPolicyInterface $passwordPolicy,
        private FileReaderInterface $reader,
        private DemoMode $demoMode
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('user:reset-password')
            ->setDescription('Nastaví nové heslo používateľa v aktívnom úložisku (content/demo podľa DEMO_MODE)')
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail používateľa')
            ->addArgument('password', InputArgument::REQUIRED, 'Nové heslo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');

        $io->note([
            'DEMO_MODE: ' . ($this->demoMode->isEnabled() ? 'true' : 'false'),
            'Base path: ' . $this->reader->getBasePath(),
        ]);

        try {
            $this->passwordPolicy->requireValid($password);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            $io->error('Používateľ s e-mailom "' . $email . '" v aktívnom úložisku neexistuje.');
            $io->text('Skús: php backend/bin/console user:list');
            $io->text('Ak si účet vytvoril pri DEMO_MODE=true, prepni DEMO_MODE späť alebo vytvor účet v content strome.');

            return Command::FAILURE;
        }

        $user->setPassword($password);
        $this->users->save($user);

        $io->success('Heslo bolo zmenené pre ' . $email);

        return Command::SUCCESS;
    }
}
