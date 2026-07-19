<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Commands;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ListUsersCommand extends Command
{
    public function __construct(
        private UserRepository $users,
        private FileReaderInterface $reader,
        private DemoMode $demoMode
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('user:list')
            ->setDescription('Vypíše používateľov v aktívnom flat-file úložisku (content alebo demo podľa DEMO_MODE)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $basePath = $this->reader->getBasePath();

        $io->title('PaginiumCMS — používatelia');
        $io->text([
            'DEMO_MODE: ' . ($this->demoMode->isEnabled() ? 'true (demo strom)' : 'false (produkčný content)'),
            'Base path: ' . $basePath,
            'Users dir: ' . $basePath . '/data/users',
        ]);

        $all = $this->users->findAll();
        if ($all === []) {
            $io->warning('V aktívnom úložisku nie je žiadny používateľ.');

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($all as $user) {
            $rows[] = [
                $user->getEmail(),
                implode(',', $user->getRoles()),
                $user->isActive() ? 'yes' : 'no',
                $user->isTwoFactorEnabled() ? 'yes' : 'no',
                $user->getId(),
            ];
        }

        $io->table(['Email', 'Roles', 'Active', '2FA', 'ID'], $rows);

        return Command::SUCCESS;
    }
}
