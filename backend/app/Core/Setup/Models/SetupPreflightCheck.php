<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Setup\Models;

enum SetupPreflightStatus: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Warn = 'warn';
    case Info = 'info';
}

enum SetupPreflightSeverity: string
{
    case Hard = 'hard';
    case Soft = 'soft';
    case Info = 'info';
}

/**
 * Read-only setup preflight row (It.25 M1+).
 *
 * @phpstan-type SetupPreflightCheckArray array{
 *     id: string,
 *     status: 'pass'|'fail'|'warn'|'info',
 *     severity: 'hard'|'soft'|'info',
 *     current: string|null,
 *     required: string|null,
 *     installSteps: list<string>
 * }
 */
final class SetupPreflightCheck
{
    /**
     * @param list<string> $installSteps
     */
    public function __construct(
        public readonly string $id,
        public readonly SetupPreflightStatus $status,
        public readonly SetupPreflightSeverity $severity,
        public readonly ?string $current = null,
        public readonly ?string $required = null,
        public readonly array $installSteps = [],
    ) {
    }

    /**
     * @return SetupPreflightCheckArray
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'severity' => $this->severity->value,
            'current' => $this->current,
            'required' => $this->required,
            'installSteps' => $this->installSteps,
        ];
    }
}
