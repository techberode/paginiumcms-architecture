<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Commands;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Bootstrap operator account without HTTP session (It.80f). */
final class UserCreateCommand extends Command
{
    /** @var list<string> */
    private const ALLOWED_ROLES = [
        AuthorizationInterface::ROLE_USER,
        AuthorizationInterface::ROLE_EDITOR,
        AuthorizationInterface::ROLE_ADMIN,
        AuthorizationInterface::ROLE_SUPER_ADMIN,
    ];

    public function __construct(
        private UserRepository $users,
        private PasswordPolicyInterface $passwordPolicy,
        private FileReaderInterface $reader,
        private DemoMode $demoMode,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('user:create')
            ->setDescription('Create an operator account in the active flat-file storage tree')
            ->addArgument('email', InputArgument::REQUIRED, 'User e-mail')
            ->addArgument('name', InputArgument::REQUIRED, 'Display name')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'Username (defaults from e-mail local part)')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'USER, EDITOR, ADMIN, or SUPER_ADMIN', AuthorizationInterface::ROLE_ADMIN)
            ->addOption('inactive', null, InputOption::VALUE_NONE, 'Create as inactive');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = strtolower(trim((string) $input->getArgument('email')));
        $name = trim((string) $input->getArgument('name'));
        $password = (string) $input->getArgument('password');
        $role = strtoupper(trim((string) $input->getOption('role')));
        $username = strtolower(trim((string) ($input->getOption('username') ?? '')));

        if ($username === '') {
            $parts = explode('@', $email, 2);
            $localPart = $parts[0] !== '' ? $parts[0] : 'user';
            $username = preg_replace('/[^a-z0-9_-]+/', '', $localPart) ?? '';
            if ($username === '') {
                $username = 'user';
            }
        }

        $io->note([
            'DEMO_MODE: ' . ($this->demoMode->isEnabled() ? 'true (demo tree)' : 'false (content tree)'),
            'Base path: ' . $this->reader->getBasePath(),
        ]);

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            $io->error('Invalid --role. Allowed: ' . implode(', ', self::ALLOWED_ROLES));

            return Command::FAILURE;
        }

        try {
            $this->passwordPolicy->requireValid($password);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        if ($this->users->existsByEmail($email)) {
            $io->error('E-mail already exists: ' . $email);

            return Command::FAILURE;
        }

        if ($this->users->existsByUsername($username)) {
            $io->error('Username already exists: ' . $username);

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setName($name);
        $user->setRoles([$role]);
        $user->setPassword($password);
        $user->setActive(!$input->getOption('inactive'));
        $user->setUpdatedAt(time());

        $this->users->save($user);

        $io->success(sprintf('Created %s (%s) as %s [id=%s]', $email, $username, $role, $user->getId()));

        return Command::SUCCESS;
    }
}
