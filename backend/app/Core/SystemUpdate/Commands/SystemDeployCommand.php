<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Commands;

use PaginiumCMS\Core\SystemUpdate\Services\SystemDeployService;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI entry for production deploy (It.63) — same whitelist script as admin job queue.
 */
final class SystemDeployCommand extends Command
{
    public function __construct(private SystemDeployService $deploy)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('system:deploy')
            ->setDescription('Run whitelisted instance update script (git ref + composer + FE build)')
            ->addOption('ref', null, InputOption::VALUE_REQUIRED, 'Git ref (origin/main or tag)', 'origin/main');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ref = (string) $input->getOption('ref');
        $result = $this->deploy->deploy(['ref' => $ref]);

        $output->writeln(JsonHelper::encode($result->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $result->success ? Command::SUCCESS : Command::FAILURE;
    }
}
