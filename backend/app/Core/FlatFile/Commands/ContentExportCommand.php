<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Commands;

use PaginiumCMS\Core\FlatFile\Services\ContentExportService;
use PaginiumCMS\Support\JsonHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Export flat-file pages/articles to JSON (It.80f). */
final class ContentExportCommand extends Command
{
    protected static string $defaultName = 'content:export';

    public function __construct(
        private ContentExportService $export,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('content:export')
            ->setDescription('Export pages and/or articles to JSON (stdout or directory)')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'page, article, or all', 'all')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output path: "-" for stdout (default) or directory', '-');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = strtolower(trim((string) $input->getOption('type')));
        $typeFilter = match ($type) {
            'all' => null,
            'page', 'pages' => 'page',
            'article', 'articles', 'blog' => 'article',
            default => '__invalid__',
        };

        if ($typeFilter === '__invalid__') {
            $io->error('Invalid --type. Use page, article, or all.');

            return Command::FAILURE;
        }

        $payload = $this->export->buildPayload($typeFilter);
        $outputPath = (string) $input->getOption('output');

        if ($outputPath === '-' || $outputPath === '') {
            $output->writeln(JsonHelper::encode($payload));

            return Command::SUCCESS;
        }

        if (!is_dir($outputPath) && !@mkdir($outputPath, 0755, true) && !is_dir($outputPath)) {
            $io->error('Could not create output directory: ' . $outputPath);

            return Command::FAILURE;
        }

        file_put_contents(
            rtrim($outputPath, '/') . '/manifest.json',
            JsonHelper::encode($payload)
        );

        foreach ($payload['items'] as $item) {
            $filename = sprintf('%s-%s.json', $item['type'], $item['slug']);
            file_put_contents(
                rtrim($outputPath, '/') . '/' . $filename,
                JsonHelper::encode($item)
            );
        }

        $io->success(sprintf(
            'Exported %d item(s) to %s',
            count($payload['items']),
            $outputPath
        ));

        return Command::SUCCESS;
    }
}
