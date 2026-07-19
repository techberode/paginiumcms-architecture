<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Commands;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clears login brute-force lockouts and rate-limit cache (LAN/dev recovery).
 */
final class ClearLoginLockoutsCommand extends Command
{
    public function __construct(
        private LoginAttemptTracker $loginAttempts,
        private CacheManager $cache
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('security:clear-lockouts')
            ->setDescription('Zruší login lockout (login_attempts.json) a rate-limit cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->loginAttempts->clearAll();
        $this->cache->clear();

        $output->writeln('<info>✅ Login lockout a rate-limit cache vymazané.</info>');

        return Command::SUCCESS;
    }
}
