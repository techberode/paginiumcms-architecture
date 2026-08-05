<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

use RuntimeException;

/**
 * Safe git binary wrapper using argument arrays (Iteration 70).
 */
final class LocalGitProcess
{
    public function __construct(
        private string $binary = 'git',
    ) {
    }

    /**
     * @param list<string> $args git subcommand args (without the git binary)
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    public function run(array $args, string $workingDirectory): array
    {
        $cwd = realpath($workingDirectory);
        if ($cwd === false || !is_dir($cwd)) {
            throw new RuntimeException('Git working directory is invalid.');
        }

        $command = array_merge([$this->binary], $args);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $cwd);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start git process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    public function isAvailable(): bool
    {
        $result = $this->run(['--version'], sys_get_temp_dir());

        return $result['exitCode'] === 0;
    }
}
