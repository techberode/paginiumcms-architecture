<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Expands guarded :::embed Markdown blocks to sandboxed provider iframes (It.91b).
 */
final class ExternalEmbedShortcode
{
    public const DIRECTIVE = 'embed';

    public const MAX_WIDTH_MIN = 280;

    public const MAX_WIDTH_MAX = 1280;

    private const BLOCK_PATTERN =
        '/:::embed\s*\n\s*provider:\s*(\S+)\s*\n\s*id:\s*(\S+)(?:\n\s*align:\s*(left|center|right))?(?:\n\s*maxWidth:\s*(\d+))?\s*\n\s*:::/';

    private const INLINE_PATTERN =
        '/:::embed\s+provider="([^"]+)"\s+id="([^"]+)"(?:\s+align="(left|center|right)")?(?:\s+maxWidth="(\d+)")?\s*:::/';

    private const STRIP_BLOCK_PATTERN =
        '/:::embed\s*\n\s*provider:\s*\S+\s*\n\s*id:\s*\S+(?:\n\s*align:\s*(?:left|center|right))?(?:\n\s*maxWidth:\s*\d+)?\s*\n\s*:::/';

    private const STRIP_INLINE_PATTERN =
        '/:::embed\s+provider="[^"]+"\s+id="[^"]+"(?:\s+align="(?:left|center|right)")?(?:\s+maxWidth="\d+")?\s*:::/';

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
        $markdown = $this->promoteStandaloneVideoUrls($markdown);

        $expanded = preg_replace_callback(
            self::BLOCK_PATTERN,
            fn (array $matches): string => $this->renderFromRegexMatch($matches),
            $markdown
        );

        if (!is_string($expanded)) {
            return $markdown;
        }

        $oneLine = preg_replace_callback(
            self::INLINE_PATTERN,
            fn (array $matches): string => $this->renderFromRegexMatch($matches),
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
        $markdown = $this->promoteStandaloneVideoUrls($markdown);

        /** @var array<string, string> $renders */
        $renders = [];
        $index = 0;

        $replace = function (array $matches) use (&$renders, &$index): string {
            $html = $this->renderFromRegexMatch($matches);
            if ($html === '') {
                return '';
            }

            $key = 'paginium-embed:' . $index;
            $index++;
            $renders[$key] = $html;

            return "\n\n<!-- {$key} -->\n\n";
        };

        $deferred = preg_replace_callback(
            self::BLOCK_PATTERN,
            $replace,
            $markdown
        );

        if (!is_string($deferred)) {
            return [$markdown, []];
        }

        $deferred = preg_replace_callback(
            self::INLINE_PATTERN,
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
        $stripped = preg_replace(self::STRIP_BLOCK_PATTERN, '', $markdown);
        if (!is_string($stripped)) {
            return $markdown;
        }

        $inline = preg_replace(self::STRIP_INLINE_PATTERN, '', $stripped);

        return is_string($inline) ? $inline : $stripped;
    }

    /**
     * @param list<string> $enabledProviders Lowercase provider names from settings.
     */
    public function validateBlocks(string $markdown, array $enabledProviders): ?string
    {
        $enabled = array_fill_keys(array_map('strtolower', $enabledProviders), true);

        if (preg_match_all(
            self::BLOCK_PATTERN,
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
            self::INLINE_PATTERN,
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

    /**
     * Converts a line that contains only an allow-listed video URL into a :::embed block.
     */
    /**
     * Replaces copy-pasted YouTube/Vimeo iframe HTML with guarded :::embed blocks.
     */
    public function convertRawEmbedIframesToBlocks(string $markdown): string
    {
        $converted = preg_replace_callback(
            '/<iframe\b[^>]*\bsrc=(["\'])([^"\']+)\1[^>]*>\s*<\/iframe>/i',
            function (array $matches): string {
                $src = html_entity_decode(trim((string) $matches[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (!self::isAllowedIframeSrc($src)) {
                    return (string) $matches[0];
                }

                if (preg_match('#(?:youtube-nocookie\.com|youtube\.com)/embed/([a-zA-Z0-9_-]{11})#', $src, $youtube) === 1) {
                    return "\n\n:::embed\nprovider: youtube\nid: {$youtube[1]}\n:::\n";
                }

                if (preg_match('#player\.vimeo\.com/video/(\d{1,20})#', $src, $vimeo) === 1) {
                    return "\n\n:::embed\nprovider: vimeo\nid: {$vimeo[1]}\n:::\n";
                }

                return (string) $matches[0];
            },
            $markdown
        );

        return is_string($converted) ? $converted : $markdown;
    }

    public function normalizeMarkdownEmbeds(string $markdown): string
    {
        return $this->convertRawEmbedIframesToBlocks($this->promoteStandaloneVideoUrls($markdown));
    }

    public function promoteStandaloneVideoUrls(string $markdown): string
    {
        $patterns = [
            '/^(?:[ \t]*)https?:\/\/(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})\/?(?:\?[^\s]*)?(?:[ \t]*)$/mu'
                => "\n\n:::embed\nprovider: youtube\nid: $1\n:::\n",
            '/^(?:[ \t]*)https?:\/\/(?:www\.|m\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})[^\s]*(?:[ \t]*)$/mu'
                => "\n\n:::embed\nprovider: youtube\nid: $1\n:::\n",
            '/^(?:[ \t]*)https?:\/\/(?:www\.|m\.)?youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})[^\s]*(?:[ \t]*)$/mu'
                => "\n\n:::embed\nprovider: youtube\nid: $1\n:::\n",
            '/^(?:[ \t]*)https?:\/\/[^\s]*[?&]v=([a-zA-Z0-9_-]{11})(?:&[^\s]*)?(?:[ \t]*)$/mu'
                => "\n\n:::embed\nprovider: youtube\nid: $1\n:::\n",
            '/^(?:[ \t]*)https?:\/\/(?:www\.)?(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d{1,20})[^\s]*(?:[ \t]*)$/mu'
                => "\n\n:::embed\nprovider: vimeo\nid: $1\n:::\n",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $next = preg_replace($pattern, $replacement, $markdown);
            if (is_string($next)) {
                $markdown = $next;
            }
        }

        return $markdown;
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

    /**
     * @param array<int, string> $matches
     */
    private function renderFromRegexMatch(array $matches): string
    {
        return $this->renderMatch(
            (string) $matches[1],
            (string) $matches[2],
            isset($matches[3]) ? (string) $matches[3] : '',
            isset($matches[4]) ? (string) $matches[4] : ''
        );
    }

    private function renderMatch(
        string $providerRaw,
        string $idRaw,
        string $alignRaw = '',
        string $maxWidthRaw = ''
    ): string {
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

        $align = $this->normalizeAlign($alignRaw);
        $maxWidth = $this->normalizeMaxWidth($maxWidthRaw);

        $title = htmlspecialchars($provider . ' embed', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $srcAttr = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $class = 'paginium-external-embed';
        if ($align !== null) {
            $class .= ' paginium-external-embed--align-' . $align;
        }

        $styleAttr = '';
        if ($maxWidth !== null) {
            $styleAttr = ' style="max-width:' . $maxWidth . 'px"';
        }

        // Standalone block-level iframe (same pattern as :::video) — CommonMark escapes nested HTML inside <div>.
        // No sandbox: allow-listed nocookie/Vimeo hosts only; sandbox breaks most embed players despite CSP frame-src.
        return '<iframe class="' . $class . '" src="' . $srcAttr . '" title="' . $title . '" width="560" height="315" loading="lazy" '
            . $styleAttr
            . ' frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
            . 'allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>';
    }

    private function normalizeAlign(string $raw): ?string
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return null;
        }

        return in_array($value, ['left', 'center', 'right'], true) ? $value : null;
    }

    private function normalizeMaxWidth(string $raw): ?int
    {
        $trimmed = trim($raw);
        if ($trimmed === '' || !ctype_digit($trimmed)) {
            return null;
        }

        $width = (int) $trimmed;
        if ($width < self::MAX_WIDTH_MIN || $width > self::MAX_WIDTH_MAX) {
            return null;
        }

        return $width;
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
