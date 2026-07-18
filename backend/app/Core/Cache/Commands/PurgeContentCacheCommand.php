<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache\Commands;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Vymaže cache obsahu (zoznamy + jednotlivé stránky/články) a voliteľne rebuildne index.
 */
final class PurgeContentCacheCommand extends Command
{
    protected static $defaultName = 'content:cache-purge';

    public function __construct(
        private ContentCacheService $contentCache,
        private ContentIndexService $index,
        private ContentRepositoryInterface $repository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('content:cache-purge')
            ->setDescription('Invaliduje content cache a voliteľne rebuildne flat-file index')
            ->addOption('reindex', null, InputOption::VALUE_NONE, 'Rebuild data/index/content.json z disku');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->contentCache->purgeAll();
        $output->writeln('<info>Content cache invalidovaná (generácie zoznamov + položky).</info>');

        if ($input->getOption('reindex')) {
            $this->index->rebuild($this->repository);
            $output->writeln('<info>Content index rebuildnutý z disku.</info>');
        }

        return Command::SUCCESS;
    }
}
