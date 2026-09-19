<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Drivers;

use PaginiumCMS\Core\Agent\Contracts\LlmProviderInterface;
use PaginiumCMS\Core\Agent\Exception\AgentException;

final class NoneLlmDriver implements LlmProviderInterface
{
    public function id(): string
    {
        return 'none';
    }

    public function complete(array $messages, array $tools, int $maxTokens): array
    {
        throw new AgentException('Agent provider is not configured', 503, 'DISABLED');
    }

    public function health(): array
    {
        return ['ok' => false, 'error' => 'Provider is none'];
    }
}
