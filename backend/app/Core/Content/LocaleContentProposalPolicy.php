<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\Validation\ValidationException;

/**
 * Guards against automated translation/AI flows publishing a locale without human approval (It.73).
 */
final class LocaleContentProposalPolicy
{
    /** @var list<string> */
    private const AUTOMATED_SOURCES = ['translation', 'ai', 'machine_translation'];

    public function isAutomatedProposalSource(?string $source): bool
    {
        if ($source === null) {
            return false;
        }

        $source = strtolower(trim($source));

        return $source !== '' && in_array($source, self::AUTOMATED_SOURCES, true);
    }

    /**
     * @param array<int|string, mixed> $payload
     *
     * @throws ValidationException
     */
    public function assertDoesNotAutoPublish(array $payload, ?string $source): void
    {
        if (!$this->isAutomatedProposalSource($source)) {
            return;
        }

        $status = strtolower(trim((string) ($payload['status'] ?? 'draft')));
        if ($status === 'published') {
            throw new ValidationException(
                ['status' => ['Translation/AI proposals cannot auto-publish a locale.']],
                'Automated locale proposal rejected',
                422
            );
        }
    }
}
