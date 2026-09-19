<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Exception;

use RuntimeException;

/**
 * Domain error for the CMS AI assistant (It.75). Never includes prompts, secrets, or provider payloads.
 */
final class AgentException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 422,
        public readonly string $errorCode = 'INVALID',
    ) {
        parent::__construct($message);
    }
}
