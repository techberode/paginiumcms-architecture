<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Commands;

use PaginiumCMS\Http\Extensions\Services\PluginScanService;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Scan a plugin folder with the same engine as CMS ZIP import (It.89e). */
final class PluginScanCommand extends Command
{
    public function __construct(
        private PluginScanService $scan,
        private string $extensionsRoot,
    ) {
        parent::__construct();
        $this->extensionsRoot = rtrim($extensionsRoot, '/');
    }

    protected function configure(): void
    {
        $this
            ->setName('plugin:scan')
            ->setAliases(['paginium:plugin:scan'])
            ->setDescription('Scan a plugin directory (id or path) with the ZIP-import code policy + capability usage engine')
            ->addArgument('target', InputArgument::REQUIRED, 'Plugin id under Http/Extensions/ or a directory containing plugin.json')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print machine-readable JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $asJson = (bool) $input->getOption('json');

        try {
            $root = $this->resolveRoot(trim((string) $input->getArgument('target')));
            $report = $this->scan->scan($root);
        } catch (RuntimeException $exception) {
            if ($asJson) {
                $output->writeln(JsonHelper::encode([
                    'ok' => false,
                    'error' => $exception->getMessage(),
                ]));
            } else {
                $io->error($exception->getMessage());
            }

            return Command::FAILURE;
        }

        $ok = $report['errors'] === [];
        if ($asJson) {
            $output->writeln(JsonHelper::encode([
                'ok' => $ok,
                'id' => $report['id'],
                'path' => $root,
                'errors' => $report['errors'],
            ], JSON_UNESCAPED_UNICODE));

            return $ok ? Command::SUCCESS : Command::FAILURE;
        }

        if ($ok) {
            $io->success(sprintf('Plugin %s passed policy + capability usage scan.', $report['id']));

            return Command::SUCCESS;
        }

        $io->error(sprintf('Plugin %s failed scan:', $report['id']));
        foreach ($report['errors'] as $file => $messages) {
            foreach ($messages as $message) {
                $io->writeln($file . ': ' . $message);
            }
        }

        return Command::FAILURE;
    }

    private function resolveRoot(string $target): string
    {
        if ($target === '') {
            throw new RuntimeException('Plugin id or path is required.');
        }

        if (is_dir($target) && is_file(rtrim($target, '/\\') . '/plugin.json')) {
            $real = realpath($target);
            if ($real === false) {
                throw new RuntimeException('Unable to resolve plugin path.');
            }

            return $real;
        }

        $fromExt = $this->extensionsRoot . '/' . $target;
        if (is_dir($fromExt) && is_file($fromExt . '/plugin.json')) {
            $real = realpath($fromExt);
            if ($real === false) {
                throw new RuntimeException('Unable to resolve plugin path.');
            }

            return $real;
        }

        throw new RuntimeException('Plugin not found: ' . $target);
    }
}
