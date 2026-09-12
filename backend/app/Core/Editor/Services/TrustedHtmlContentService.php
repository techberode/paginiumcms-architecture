<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\Security\Services\TrustedHtmlPurifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Models\User;

/**
 * Role-gated trusted HTML blocks in Markdown (It.91a).
 */
final class TrustedHtmlContentService
{
    public const PERMISSION_TRUSTED_HTML = 'content:trusted-html';

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private AuthorizationInterface $authorization,
        private TrustedHtmlPurifier $purifier,
        private HtmlSafeShortcode $htmlSafeShortcode = new HtmlSafeShortcode(),
    ) {
    }

    public function isFeatureEnabled(): bool
    {
        $editor = $this->settings->group('editor');

        return $this->isTruthy($editor['trustedHtmlEnabled'] ?? false);
    }

    public function canUseTrustedHtml(?User $user): bool
    {
        if (!$this->isFeatureEnabled()) {
            return false;
        }

        if ($user === null) {
            return false;
        }

        return $this->authorization->hasPermission($user, self::PERMISSION_TRUSTED_HTML);
    }

    public function validateMarkdown(string $content, ?User $user): ?string
    {
        if (!$this->htmlSafeShortcode->containsBlock($content)) {
            return null;
        }

        if (!$this->canUseTrustedHtml($user)) {
            return 'Trusted HTML bloky nie sú povolené pre tento účet alebo sú vypnuté v nastaveniach.';
        }

        return null;
    }

    public function normalizeMarkdown(string $content): string
    {
        if (!$this->htmlSafeShortcode->containsBlock($content)) {
            return $content;
        }

        return $this->htmlSafeShortcode->normalizeAllBodies(
            $content,
            fn (string $body): string => $this->purifier->purify($body)
        );
    }

    private function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value !== 0;
        }

        $normalized = strtolower(trim((string) $value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no'], true);
    }
}
