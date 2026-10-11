<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Performance;

/**
 * It.100e — defer heavy optional admin GET/export work when the instance load hint is busy.
 */
final class AdminP3AdmissionGuard
{
    public function __construct(
        private AdminLoadHintResolver $loadHint,
        private AdminLoadHintSettings $settings,
    ) {
    }

    public function shouldDeferHeavyWork(): bool
    {
        if (!$this->settings->p3AdmissionWhenBusy()) {
            return false;
        }

        return $this->loadHint->resolve()['level'] === 'busy';
    }

    public function retryAfterSeconds(): int
    {
        return $this->settings->p3RetryAfterSeconds();
    }
}
