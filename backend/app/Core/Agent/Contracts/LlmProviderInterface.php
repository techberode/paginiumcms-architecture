<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Contracts;

/**
 * Typed completion + tool request (It.75). Implementations must not log content or secrets.
 *
 * @phpstan-type AgentToolSpec array{name: string, description: string, parameters: array<string, mixed>}
 * @phpstan-type AgentMessage array{role: string, content: string}
 * @phpstan-type AgentCompletion array{
 *     type: 'tool_call'|'message',
 *     name?: string,
 *     arguments?: array<string, mixed>,
 *     content?: string,
 *     tokens: int
 * }
 */
interface LlmProviderInterface
{
    public function id(): string;

    /**
     * @param list<AgentMessage> $messages
     * @param list<AgentToolSpec> $tools
     * @return AgentCompletion
     */
    public function complete(array $messages, array $tools, int $maxTokens): array;

    /**
     * @return array{ok: bool, error?: string}
     */
    public function health(): array;
}
