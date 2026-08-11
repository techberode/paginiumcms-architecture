<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Comments\Services;

/**
 * Result of comment spam heuristics (It.80c).
 */
final class CommentSpamVerdict
{
    public const ACTION_ALLOW = 'allow';
    public const ACTION_QUARANTINE = 'quarantine';
    public const ACTION_REJECT = 'reject';
    public const ACTION_REJECT_SILENT = 'reject_silent';

    public function __construct(
        public readonly string $action,
        public readonly int $score = 0,
    ) {
    }

    public function isAllow(): bool
    {
        return $this->action === self::ACTION_ALLOW;
    }

    public function isQuarantine(): bool
    {
        return $this->action === self::ACTION_QUARANTINE;
    }

    public function isRejectSilent(): bool
    {
        return $this->action === self::ACTION_REJECT_SILENT;
    }

    public function isReject(): bool
    {
        return $this->action === self::ACTION_REJECT;
    }
}
