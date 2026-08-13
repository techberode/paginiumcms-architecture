<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Seo\Commands;

use PaginiumCMS\Core\Seo\Services\RedirectStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Lint redirect map for loops and invalid targets (It.80f). */
final class RedirectValidateCommand extends Command
{
    protected static string $defaultName = 'redirect:validate';

    public function __construct(
        private RedirectStore $redirects,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('redirect:validate')
            ->setDescription('Validate flat-file redirect rules (loops, paths, status codes)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $issues = $this->redirects->validateAllRules();
        $count = count($this->redirects->listRules());

        if ($issues === []) {
            $io->success(sprintf('All %d redirect rules are valid.', $count));

            return Command::SUCCESS;
        }

        $io->error(sprintf('Found %d issue(s) in redirect map:', count($issues)));
        $io->listing($issues);

        return Command::FAILURE;
    }
}
