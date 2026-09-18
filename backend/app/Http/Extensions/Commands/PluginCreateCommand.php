<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Commands;

use PaginiumCMS\Http\Extensions\Services\PluginScanService;
use PaginiumCMS\Http\Extensions\Services\PluginScaffoldService;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Scaffold a capability-compliant plugin under Http/Extensions/{id}/ (It.89e). */
final class PluginCreateCommand extends Command
{
    public function __construct(
        private PluginScaffoldService $scaffold,
        private PluginScanService $scan,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('plugin:create')
            ->setAliases(['paginium:plugin:create'])
            ->setDescription('Create a plugin scaffold with plugin.json capabilities (same contract as ZIP import)')
            ->addArgument('id', InputArgument::REQUIRED, 'Kebab-case plugin id (e.g. seo-analyzer)')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Display name (defaults from id)')
            ->addOption(
                'capabilities',
                'c',
                InputOption::VALUE_REQUIRED,
                'Comma-separated capability strings',
                'content:read'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = trim((string) $input->getArgument('id'));
        $name = trim((string) ($input->getOption('name') ?? ''));
        $rawCaps = (string) $input->getOption('capabilities');
        $capabilities = preg_split('/\s*,\s*/', $rawCaps) ?: [];

        try {
            $root = $this->scaffold->create($id, $name, $capabilities);
            $report = $this->scan->scan($root);
        } catch (RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        if ($report['errors'] !== []) {
            $io->error('Scaffold failed the same scan as ZIP import.');
            $this->printErrors($io, $report['errors']);

            return Command::FAILURE;
        }

        $io->success(sprintf('Created plugin %s at %s (enabled=false until you enable it in Extensions).', $report['id'], $root));
        $io->listing([
            'plugin.json with manifestVersion 1 and capabilities[]',
            'src/Hooks.php (extension.boot)',
            'Scan: php backend/bin/console plugin:scan ' . $report['id'],
        ]);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function printErrors(SymfonyStyle $io, array $errors): void
    {
        foreach ($errors as $file => $messages) {
            foreach ($messages as $message) {
                $io->writeln($file . ': ' . $message);
            }
        }
    }
}
