<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

/**
 * User-agent bot classification result (analytics + optional WAF blocking).
 */
final readonly class BotClassification
{
    public function __construct(
        public string $visitorType,
        public ?string $botName = null,
        public ?string $botKind = null,
        public bool $shouldBlock = false,
    ) {
    }

    public function isBot(): bool
    {
        return $this->visitorType === 'bot';
    }
}
