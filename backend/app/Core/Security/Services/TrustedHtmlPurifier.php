<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Strict HTMLPurifier profile for :::html-safe blocks (It.91).
 */
final class TrustedHtmlPurifier
{
    private const DEFAULT_ALLOWED_TAGS =
        'div,span,p,a,img,table,thead,tbody,tr,th,td,ul,ol,li,strong,em,blockquote,code,pre,h1,h2,h3,h4,br';

    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function purify(string $html): string
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return '';
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', $this->allowedTags());
        $config->set('HTML.ForbiddenElements', ['script', 'iframe', 'object', 'embed', 'form', 'input', 'button']);
        $config->set('Attr.AllowedFrameTargets', []);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('CSS.AllowedProperties', []);
        $config->set('HTML.Nofollow', false);
        $config->set('AutoFormat.RemoveEmpty', true);

        $purifier = new HTMLPurifier($config);

        return trim($purifier->purify($trimmed));
    }

    private function allowedTags(): string
    {
        $cfg = $this->settings->group('contentSecurity');
        $raw = trim((string) ($cfg['trustedHtmlAllowedTags'] ?? ''));

        return $this->normalizeAllowedDefinition($raw !== '' ? $raw : self::DEFAULT_ALLOWED_TAGS);
    }

    /**
     * HTMLPurifier requires attributes on tags like img/a — plain tag lists trigger E_USER_WARNING.
     */
    private function normalizeAllowedDefinition(string $raw): string
    {
        if (str_contains($raw, '[') || str_contains($raw, '@')) {
            return $raw;
        }

        $parts = [];
        foreach (explode(',', $raw) as $tag) {
            $tag = strtolower(trim($tag));
            if ($tag === '') {
                continue;
            }

            $parts[] = match ($tag) {
                'a' => 'a[href|title|target|rel]',
                'img' => 'img[src|alt|width|height]',
                'th' => 'th[colspan|rowspan|scope]',
                'td' => 'td[colspan|rowspan]',
                'div', 'span', 'code', 'pre' => $tag . '[class]',
                default => $tag,
            };
        }

        return implode(',', $parts);
    }
}
