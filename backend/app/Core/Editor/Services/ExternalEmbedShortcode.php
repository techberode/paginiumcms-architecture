<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Expands guarded :::embed Markdown blocks to sandboxed provider iframes (It.91b).
 */
final class ExternalEmbedShortcode
{
    public const DIRECTIVE = 'embed';

    /** @var array<string, string> */
    private const EMBED_URLS = [
        'youtube' => 'https://www.youtube-nocookie.com/embed/',
        'vimeo' => 'https://player.vimeo.com/video/',
    ];

    /** @var array<string, string> */
    private const ID_PATTERNS = [
        'youtube' => '/^[a-zA-Z0-9_-]{11}$/',
        'vimeo' => '/^\d{1,20}$/',
    ];

    public function expand(string $markdown): string
    {
        $expanded = preg_replace_callback(
            '/:::embed\s*\n\s*provider:\s*(\S+)\s*\n\s*id:\s*(\S+)\s*\n\s*:::/',
            fn (array $matches): string => $this->renderMatch((string) $matches[1], (string) $matches[2]),
            $markdown
        );

        if (!is_string($expanded)) {
            return $markdown;
        }

        $oneLine = preg_replace_callback(
            '/:::embed\s+provider="([^"]+)"\s+id="([^"]+)"\s*:::/',
            fn (array $matches): string => $this->renderMatch((string) $matches[1], (string) $matches[2]),
            $expanded
        );

        return is_string($oneLine) ? $oneLine : $expanded;
    }

    /**
     * CommonMark allows <video> blocks but escapes <iframe> — defer embed HTML until after conversion.
     *
     * @return array{0: string, 1: array<string, string>} Markdown with placeholders, map key → iframe HTML
     */
    public function deferBlocks(string $markdown): array
    {
        /** @var array<string, string> $renders */
        $renders = [];
        $index = 0;

        $replace = function (array $matches) use (&$renders, &$index): string {
            $html = $this->renderMatch((string) $matches[1], (string) $matches[2]);
            if ($html === '') {
                return '';
            }

            $key = 'paginium-embed:' . $index;
            $index++;
            $renders[$key] = $html;

            return "\n\n<!-- {$key} -->\n\n";
        };

        $deferred = preg_replace_callback(
            '/:::embed\s*\n\s*provider:\s*(\S+)\s*\n\s*id:\s*(\S+)\s*\n\s*:::/',
            $replace,
            $markdown
        );

        if (!is_string($deferred)) {
            return [$markdown, []];
        }

        $deferred = preg_replace_callback(
            '/:::embed\s+provider="([^"]+)"\s+id="([^"]+)"\s*:::/',
            $replace,
            $deferred
        );

        return [is_string($deferred) ? $deferred : $markdown, $renders];
    }

    /**
     * @param array<string, string> $renders
     */
    public function restoreDeferred(string $html, array $renders): string
    {
        foreach ($renders as $key => $fragment) {
            $comment = '<!-- ' . $key . ' -->';
            $html = str_replace($comment, $fragment, $html);
            $html = str_replace('<p>' . $comment . '</p>', $fragment, $html);
        }

        return $html;
    }

    public function containsBlock(string $markdown): bool
    {
        return str_contains($markdown, ':::' . self::DIRECTIVE);
    }

    public function stripBlocks(string $markdown): string
    {
        $stripped = preg_replace('/:::embed\s*\n\s*provider:\s*\S+\s*\n\s*id:\s*\S+\s*\n\s*:::/', '', $markdown);
        if (!is_string($stripped)) {
            return $markdown;
        }

        $inline = preg_replace('/:::embed\s+provider="[^"]+"\s+id="[^"]+"\s*:::/', '', $stripped);

        return is_string($inline) ? $inline : $stripped;
    }

    /**
     * @param list<string> $enabledProviders Lowercase provider names from settings.
     */
    public function validateBlocks(string $markdown, array $enabledProviders): ?string
    {
        $enabled = array_fill_keys(array_map('strtolower', $enabledProviders), true);

        if (preg_match_all(
            '/:::embed\s*\n\s*provider:\s*(\S+)\s*\n\s*id:\s*(\S+)\s*\n\s*:::/',
            $markdown,
            $blockMatches,
            PREG_SET_ORDER
        )) {
            foreach ($blockMatches as $match) {
                $error = $this->validateProviderId((string) $match[1], (string) $match[2], $enabled);
                if ($error !== null) {
                    return $error;
                }
            }
        }

        if (preg_match_all(
            '/:::embed\s+provider="([^"]+)"\s+id="([^"]+)"\s*:::/',
            $markdown,
            $inlineMatches,
            PREG_SET_ORDER
        )) {
            foreach ($inlineMatches as $match) {
                $error = $this->validateProviderId((string) $match[1], (string) $match[2], $enabled);
                if ($error !== null) {
                    return $error;
                }
            }
        }

        return null;
    }

    public static function isAllowedIframeSrc(string $src): bool
    {
        $host = strtolower((string) parse_url(trim($src), PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        return in_array($host, [
            'www.youtube-nocookie.com',
            'youtube-nocookie.com',
            'www.youtube.com',
            'youtube.com',
            'player.vimeo.com',
        ], true);
    }

    private function renderMatch(string $providerRaw, string $idRaw): string
    {
        $provider = strtolower(trim($providerRaw));
        $id = trim($idRaw);
        if ($id === '' || !isset(self::EMBED_URLS[$provider])) {
            return '';
        }

        if (preg_match(self::ID_PATTERNS[$provider], $id) !== 1) {
            return '';
        }

        $src = self::EMBED_URLS[$provider] . rawurlencode($id);
        if (!self::isAllowedIframeSrc($src)) {
            return '';
        }

        $title = htmlspecialchars($provider . ' embed', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $srcAttr = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Standalone block-level iframe (same pattern as :::video) — CommonMark escapes nested HTML inside <div>.
        return '<iframe class="paginium-external-embed" src="' . $srcAttr . '" title="' . $title . '" width="560" height="315" loading="lazy" '
            . 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
            . 'allowfullscreen referrerpolicy="strict-origin-when-cross-origin" '
            . 'sandbox="allow-scripts allow-same-origin allow-presentation"></iframe>';
    }

    /**
     * @param array<string, true> $enabledProviders
     */
    private function validateProviderId(string $providerRaw, string $idRaw, array $enabledProviders): ?string
    {
        $provider = strtolower(trim($providerRaw));
        $id = trim($idRaw);

        if ($provider === '' || $id === '') {
            return 'Embed shortcode vyžaduje provider a id.';
        }

        if (!isset($enabledProviders[$provider])) {
            return 'Embed poskytovateľ nie je povolený: ' . $provider . '.';
        }

        if (!isset(self::ID_PATTERNS[$provider])) {
            return 'Neznámy embed poskytovateľ: ' . $provider . '.';
        }

        if (preg_match(self::ID_PATTERNS[$provider], $id) !== 1) {
            return 'Neplatné embed id pre poskytovateľa ' . $provider . '.';
        }

        return null;
    }
}
