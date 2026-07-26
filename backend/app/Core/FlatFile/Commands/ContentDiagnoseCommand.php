<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Commands;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Services\ContentIndexService;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Diagnostika flat-file obsahu (ISS-002) — index, súbory, oprávnenia.
 */
final class ContentDiagnoseCommand extends Command
{
    protected static string $defaultName = 'content:diagnose';

    public function __construct(
        private FileReaderInterface $reader,
        private ContentRepositoryInterface $repository,
        private ContentIndexService $index,
        private ContentCacheService $contentCache
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('content:diagnose')
            ->setDescription('Skontroluje content index, súbory pages/blog a oprávnenia (ISS-002)')
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Vymaže content cache a rebuildne index')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Výstup ako JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $report = $this->buildReport();

        if ($input->getOption('fix')) {
            $this->contentCache->purgeAll();
            $this->index->rebuild($this->repository);
            $report = $this->buildReport();
            $report['actions'] = ['content cache purged', 'content index rebuilt'];
        }

        if ($input->getOption('json')) {
            $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $output->writeln($encoded !== false ? $encoded : '{}');

            return ($report['healthy'] ?? false) ? Command::SUCCESS : Command::FAILURE;
        }

        $io->title('PaginiumCMS content diagnose (ISS-002)');

        $io->table(
            ['Check', 'Value'],
            [
                ['Base path', $report['basePath']],
                ['Index file', $report['indexPath']],
                ['Index readable', $this->boolLabel($report['indexReadable'])],
                ['Index writable', $this->boolLabel($report['indexWritable'])],
                ['Index JSON valid', $this->boolLabel($report['indexJsonValid'])],
                ['Index entries', (string) $report['indexEntryCount']],
                ['Pages on disk', (string) $report['pageFileCount']],
                ['Articles on disk', (string) $report['articleFileCount']],
                ['Unreadable content files', (string) $report['unreadableFiles']],
                ['Index orphans (missing file)', (string) $report['indexOrphans']],
                ['Backup files in content dirs', (string) $report['backupFileCount']],
                ['Jobs dir writable', $this->boolLabel($report['jobsDirWritable'])],
                ['Scheduler state writable', $this->boolLabel($report['schedulerStateWritable'])],
            ]
        );

        if ($report['issues'] !== []) {
            $io->section('Issues');
            $io->listing($report['issues']);
        }

        if ($report['actions'] !== []) {
            $io->success('Applied fixes: ' . implode(', ', $report['actions']));
        }

        if ($report['healthy']) {
            $io->success('Content storage looks healthy.');

            return Command::SUCCESS;
        }

        $io->warning('Problems detected. Run with --fix to purge cache and rebuild index.');

        return Command::FAILURE;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReport(): array
    {
        $basePath = $this->reader->getBasePath();
        $indexPath = rtrim($basePath, '/') . '/data/index/content.json';
        $issues = [];
        $actions = [];

        $indexReadable = is_readable($indexPath);
        $indexWritable = is_file($indexPath) ? is_writable($indexPath) : is_writable(dirname($indexPath));

        if (!$indexReadable) {
            $issues[] = 'Content index is not readable: ' . $indexPath;
        }
        if (!$indexWritable) {
            $issues[] = 'Content index path is not writable: ' . $indexPath;
        }

        $indexJsonValid = false;
        $indexEntryCount = 0;
        $indexOrphans = 0;

        if ($indexReadable) {
            $raw = file_get_contents($indexPath);
            if ($raw !== false && trim($raw) !== '') {
                try {
                    $decoded = JsonHelper::decode($raw);
                    $items = $decoded['items'] ?? [];
                    if (is_array($items)) {
                        $indexJsonValid = true;
                        $indexEntryCount = count($items);
                        foreach ($items as $item) {
                            if (!is_array($item)) {
                                continue;
                            }
                            $path = (string) ($item['path'] ?? '');
                            if ($path === '' || !$this->reader->exists($path)) {
                                $indexOrphans++;
                            }
                        }
                    }
                } catch (\Throwable) {
                    $issues[] = 'Content index JSON is invalid or corrupt.';
                }
            } else {
                $issues[] = 'Content index file is empty.';
            }
        }

        if ($indexOrphans > 0) {
            $issues[] = sprintf('%d index entries point to missing files (run --fix).', $indexOrphans);
        }

        $pageFiles = $this->listContentFilesSafe('pages');
        $articleFiles = $this->listContentFilesSafe('blog');
        $backupFileCount = $this->countBackupFiles(['pages', 'blog']);
        $unreadableFiles = $this->countUnreadableFiles(array_merge($pageFiles, $articleFiles));

        if ($unreadableFiles > 0) {
            $issues[] = sprintf('%d content files failed to parse (check front matter / JSON).', $unreadableFiles);
        }

        foreach ($this->checkSchedulerStorage($basePath) as $issue) {
            $issues[] = $issue;
        }

        $healthy = $issues === [];

        return [
            'healthy' => $healthy,
            'basePath' => $basePath,
            'indexPath' => $indexPath,
            'indexReadable' => $indexReadable,
            'indexWritable' => $indexWritable,
            'indexJsonValid' => $indexJsonValid,
            'indexEntryCount' => $indexEntryCount,
            'pageFileCount' => count($pageFiles),
            'articleFileCount' => count($articleFiles),
            'unreadableFiles' => $unreadableFiles,
            'indexOrphans' => $indexOrphans,
            'backupFileCount' => $backupFileCount,
            'jobsDirWritable' => $this->isPathWritable(rtrim($basePath, '/') . '/data/jobs'),
            'schedulerStateWritable' => $this->isPathWritable(rtrim($basePath, '/') . '/data/scheduler-state.json'),
            'issues' => $issues,
            'actions' => $actions,
        ];
    }

    /**
     * @return list<string>
     */
    private function listContentFilesSafe(string $directory): array
    {
        try {
            return array_merge(
                $this->reader->listFiles($directory, '*.md'),
                $this->reader->listFiles($directory, '*.json')
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<string> $paths
     */
    private function countUnreadableFiles(array $paths): int
    {
        $failed = 0;

        foreach ($paths as $path) {
            try {
                $this->repository->findByPath($path);
            } catch (FlatFileException) {
                $failed++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return $failed;
    }

    /**
     * @param list<string> $directories
     */
    private function countBackupFiles(array $directories): int
    {
        $count = 0;

        foreach ($directories as $directory) {
            try {
                $all = $this->reader->listFiles($directory, '*');
            } catch (\Throwable) {
                continue;
            }

            foreach ($all as $file) {
                if (str_contains(basename((string) $file), '.backup.')) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function boolLabel(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    private function isPathWritable(string $path): bool
    {
        if (is_file($path)) {
            return is_writable($path);
        }

        if (is_dir($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);

        return is_dir($parent) ? is_writable($parent) : is_writable(dirname($parent));
    }

    /**
     * @return list<string>
     */
    private function checkSchedulerStorage(string $basePath): array
    {
        $issues = [];
        $jobsDir = rtrim($basePath, '/') . '/data/jobs';
        $schedulerState = rtrim($basePath, '/') . '/data/scheduler-state.json';

        if (!$this->isPathWritable($jobsDir)) {
            $issues[] = 'Job scheduler storage is not writable: ' . $jobsDir
                . ' (Docker www-data needs group write — chown user:www-data, chmod 775).';
        }

        if (!$this->isPathWritable($schedulerState)) {
            $issues[] = 'Scheduler state file is not writable: ' . $schedulerState;
        }

        return $issues;
    }
}
