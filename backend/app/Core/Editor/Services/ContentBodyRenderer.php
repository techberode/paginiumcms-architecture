<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface;
use PaginiumCMS\Core\Layout\Services\ShortcodeExpanderService;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;

/**
 * Resolves public HTML from stored body + contentFormat (Iteration 55).
 */
final class ContentBodyRenderer
{
    public function __construct(
        private MarkdownContentParserInterface $markdownParser,
        private TiptapHtmlRenderer $tiptapRenderer,
        private ContentSecuritySanitizer $contentSecurity,
        private ?ShortcodeExpanderService $shortcodeExpander = null,
    ) {
    }

    public function resolveHtml(string $body, string $contentFormat, ?string $cachedHtml = null): string
    {
        $body = $this->expandShortcodes($body);

        if ($contentFormat === 'html') {
            return $this->contentSecurity->sanitizeHtml($body);
        }

        if ($contentFormat === 'tiptap_json') {
            if ($cachedHtml !== null && trim($cachedHtml) !== '') {
                return $this->contentSecurity->sanitizeHtml($this->expandShortcodes($cachedHtml));
            }

            return $this->contentSecurity->sanitizeHtml($this->tiptapRenderer->render($body));
        }

        return $this->contentSecurity->sanitizeHtml($this->markdownParser->parse($body));
    }

    private function expandShortcodes(string $body): string
    {
        if ($this->shortcodeExpander === null) {
            return $body;
        }

        return $this->shortcodeExpander->expand($body);
    }

    public function normalizeContentFormat(mixed $format, string $body): string
    {
        if ($format === 'html' || $format === 'markdown' || $format === 'tiptap_json') {
            return (string) $format;
        }

        $trimmed = trim($body);
        if ($trimmed === '') {
            return 'markdown';
        }

        if ($trimmed[0] === '{') {
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded) && ($decoded['type'] ?? '') === 'doc') {
                return 'tiptap_json';
            }
        }

        if (str_starts_with($trimmed, '<')) {
            return 'html';
        }

        return 'markdown';
    }
}
