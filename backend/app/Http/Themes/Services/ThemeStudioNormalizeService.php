<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use DOMDocument;
use DOMElement;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Security\Services\HtmlDomSanitizer;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;

/**
 * In-memory Theme Studio normalize (It.88c). Never writes disk, never executes JS.
 *
 * Ingest → report → strip/rewrite → package. Hostile markup is dropped, not “fixed”.
 * PHP / Blade / Twig / foreign `{{` engines reject the entire import.
 *
 * @phpstan-type StudioMarker array{line: int, message: string, relativePath?: string}
 * @phpstan-type NormalizeResult array{
 *   rejected: bool,
 *   files: array<string, string>,
 *   dropped: list<string>,
 *   markers: list<StudioMarker>
 * }
 */
final class ThemeStudioNormalizeService
{
    public const MAX_FILES = 24;

    public const MAX_TOTAL_BYTES = 1572864;

    /** @var list<string> */
    private const LAYOUT_TAGS = [
        'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'img', 'blockquote',
        'code', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'div', 'article', 'section', 'aside', 'span',
        'header', 'footer', 'main', 'nav', 'figure', 'figcaption',
        'cite', 'small', 'time',
    ];

    /** @var list<array{pattern: string, message: string}> */
    private const REJECT_PATTERNS = [
        ['pattern' => '/<\?php/i', 'message' => 'PHP tag'],
        ['pattern' => '/<\?=/', 'message' => 'PHP short echo'],
        ['pattern' => '/@extends\b/', 'message' => 'Blade @extends'],
        ['pattern' => '/@section\b/', 'message' => 'Blade @section'],
        ['pattern' => '/@yield\b/', 'message' => 'Blade @yield'],
        ['pattern' => '/\{!!/', 'message' => 'Blade unescaped echo'],
        ['pattern' => '/\{\{\s*\$/', 'message' => 'Blade/Twig variable'],
        ['pattern' => '/\{%\s/', 'message' => 'Twig/Liquid tag'],
    ];

    public function __construct(
        private ThemeStudioService $studio,
        private ThemeStudioValidator $validator,
        private ThemeScriptIntegrityService $integrity,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<mixed> $files
     * @return NormalizeResult
     */
    public function normalize(
        string $themeId,
        array $files = [],
        string $html = '',
        string $css = '',
        string $js = '',
    ): array {
        $id = trim($themeId);
        if ($id === '') {
            $id = 'untitled-theme';
        }
        if (!ThemeStudioService::isValidThemeId($id)) {
            throw new ThemeStudioException('Invalid theme id.', 400);
        }

        $buffers = $this->normalizeInputFiles($files);
        if ($html !== '') {
            $buffers['__paste.html'] = $html;
        }
        if ($css !== '') {
            $buffers['__paste.css'] = $css;
        }
        if ($js !== '') {
            $buffers['__paste.js'] = $js;
        }

        if ($buffers === []) {
            throw new ThemeStudioException('html or files is required.', 400);
        }

        $rawBlob = implode("\n", $buffers);
        $reject = $this->rejectionReason($rawBlob, $buffers);
        if ($reject !== null) {
            $this->logger?->warning('Theme studio normalize rejected', LogSanitizer::context([
                'themeId' => $id,
                'reason' => $reject,
            ]));

            return [
                'rejected' => true,
                'files' => [],
                'dropped' => ['Rejected entire import: ' . $reject],
                'markers' => [[
                    'line' => 1,
                    'message' => 'Rejected entire import: ' . $reject,
                    'relativePath' => 'pasted.html',
                ]],
            ];
        }

        $dropped = [];
        $sourceHtml = $this->selectPrimaryHtml($buffers);
        if ($sourceHtml === '') {
            throw new ThemeStudioException('No HTML to normalize.', 400);
        }

        $sourceHtml = $this->extractEmbeddedStyles($sourceHtml, $buffers, $dropped);
        $this->noteDroppedMarkup($sourceHtml, $dropped);

        $slots = $this->extractSlots($sourceHtml);
        $header = $this->sanitizeFragment($slots['header']);
        $footer = $this->sanitizeFragment($slots['footer']);
        $sidebar = $slots['sidebar'] !== '' ? $this->sanitizeFragment($slots['sidebar']) : '';

        if (trim(strip_tags($header)) === '') {
            $header = '<header class="pg-header"><a class="pg-brand" href="/">{{siteName}}</a></header>';
        }
        if (trim(strip_tags($footer)) === '') {
            $footer = '<footer class="pg-footer"><p>{{siteName}}</p></footer>';
        }

        $template = $this->buildTemplate($sidebar !== '');
        $themeCss = $this->rewriteCss($this->collectCss($buffers), $dropped);
        $keptJs = $this->keepPassingJavascript($id, $this->collectJs($buffers), $dropped);

        $out = [
            'templates/default.html' => $template,
            'partials/header.html' => $header . "\n",
            'partials/footer.html' => $footer . "\n",
            'assets/theme.css' => $themeCss,
        ];
        if ($sidebar !== '') {
            $out['partials/sidebar.html'] = $sidebar . "\n";
        }
        if ($keptJs !== null) {
            $out['assets/theme.js'] = $keptJs;
        }

        $name = $this->inferName($sourceHtml, $id);
        $out['theme.json'] = $this->buildManifest($id, $name, $sidebar !== '', $keptJs);

        $markers = $this->outputMarkers($id, $out);
        $dropped = array_values(array_unique($dropped));

        $this->logger?->info('Theme studio normalize completed', LogSanitizer::context([
            'themeId' => $id,
            'dropped' => (string) count($dropped),
            'markers' => (string) count($markers),
            'js' => $keptJs === null ? '0' : '1',
        ]));

        return [
            'rejected' => false,
            'files' => $out,
            'dropped' => $dropped,
            'markers' => $markers,
        ];
    }

    /**
     * @param array<mixed> $files
     * @return array<string, string>
     */
    private function normalizeInputFiles(array $files): array
    {
        if ($files === []) {
            return [];
        }

        if (count($files) > self::MAX_FILES) {
            throw new ThemeStudioException('Too many theme files in the normalize payload.', 413);
        }

        $buffers = [];
        $total = 0;
        foreach ($files as $path => $content) {
            if (!is_string($path) || !is_string($content)) {
                throw new ThemeStudioException('Each theme file must be a string path and body.', 400);
            }

            $relative = $this->studio->assertBufferPath($path);

            if (strlen($content) > ThemeStudioService::MAX_FILE_BYTES) {
                throw new ThemeStudioException('Theme file is too large to open in the studio.', 413);
            }

            $total += strlen($content);
            if ($total > self::MAX_TOTAL_BYTES) {
                throw new ThemeStudioException('Theme normalize payload is too large.', 413);
            }

            $buffers[$relative] = $content;
        }

        return $buffers;
    }

    /**
     * @param array<string, string> $buffers
     */
    private function rejectionReason(string $blob, array $buffers): ?string
    {
        foreach ($buffers as $path => $_content) {
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['php', 'phtml', 'phar'], true) || str_ends_with(strtolower($path), '.blade.php')) {
                return 'PHP file in payload (' . $path . ')';
            }
        }

        foreach (self::REJECT_PATTERNS as $rule) {
            if (preg_match($rule['pattern'], $blob) === 1) {
                return $rule['message'];
            }
        }

        if (preg_match_all('/\{\{([^}]*)\}\}/', $blob, $matches) !== false) {
            foreach ($matches[1] as $inner) {
                $token = trim((string) $inner);
                if ($token === '') {
                    return 'Empty template token';
                }
                if (preg_match('/^(>|content|title|siteName|year|navigation)\b/', $token) === 1) {
                    continue;
                }

                return 'Foreign template token {{' . $token . '}}';
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $buffers
     */
    private function selectPrimaryHtml(array $buffers): string
    {
        $preferred = [
            '__paste.html',
            'index.html',
            'index.htm',
            'templates/default.html',
        ];
        foreach ($preferred as $path) {
            if (isset($buffers[$path]) && trim($buffers[$path]) !== '') {
                return $buffers[$path];
            }
        }

        $paths = array_keys($buffers);
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($ext, ['html', 'htm'], true)) {
                continue;
            }
            if (str_starts_with($path, 'partials/')) {
                continue;
            }

            return $buffers[$path];
        }

        return '';
    }

    /**
     * @param array<string, string> $buffers
     * @param list<string> $dropped
     */
    private function extractEmbeddedStyles(string $html, array &$buffers, array &$dropped): string
    {
        $extracted = preg_replace_callback(
            '/<style\b[^>]*>(.*?)<\/style>/is',
            static function (array $matches) use (&$buffers): string {
                $buffers['__embedded.css'] = ($buffers['__embedded.css'] ?? '') . "\n" . $matches[1];

                return '';
            },
            $html
        );
        if (is_string($extracted)) {
            if ($extracted !== $html) {
                $dropped[] = 'Moved <style> blocks into assets/theme.css';
            }
            $html = $extracted;
        }

        $withoutLinks = preg_replace_callback(
            '/<link\b[^>]*>/i',
            static function (array $matches) use (&$dropped): string {
                $tag = $matches[0];
                if (preg_match('/href\s*=\s*[\'"]([^\'"]+)[\'"]/i', $tag, $href) !== 1) {
                    $dropped[] = 'Dropped <link> without href';

                    return '';
                }
                $url = trim($href[1]);
                if (preg_match('#^(https?:)?//#i', $url) === 1) {
                    $dropped[] = 'Dropped CDN stylesheet: ' . $url;

                    return '';
                }
                $dropped[] = 'Dropped local <link rel=stylesheet>; concatenate CSS files instead';

                return '';
            },
            $html
        );

        return is_string($withoutLinks) ? $withoutLinks : $html;
    }

    /**
     * @param list<string> $dropped
     */
    private function noteDroppedMarkup(string $html, array &$dropped): void
    {
        $scripts = preg_match_all('/<script\b/i', $html);
        if (is_int($scripts) && $scripts > 0) {
            $dropped[] = 'Dropped <script> (' . $scripts . ')';
        }
        $iframes = preg_match_all('/<iframe\b/i', $html);
        if (is_int($iframes) && $iframes > 0) {
            $dropped[] = 'Dropped <iframe> (' . $iframes . ')';
        }
        $handlers = preg_match_all('/\bon[a-z]+\s*=/i', $html);
        if (is_int($handlers) && $handlers > 0) {
            $dropped[] = 'Dropped event handler attributes (' . $handlers . ')';
        }
        $jsUrls = preg_match_all('/javascript\s*:/i', $html);
        if (is_int($jsUrls) && $jsUrls > 0) {
            $dropped[] = 'Dropped javascript: URLs (' . $jsUrls . ')';
        }
        $objects = preg_match_all('/<(object|embed)\b/i', $html);
        if (is_int($objects) && $objects > 0) {
            $dropped[] = 'Dropped <object>/<embed> (' . $objects . ')';
        }
    }

    /**
     * @return array{header: string, footer: string, sidebar: string}
     */
    private function extractSlots(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="UTF-8"><div id="paginium-root">' . $html . '</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return [
            'header' => $this->firstOuterHtml($document, 'header'),
            'footer' => $this->firstOuterHtml($document, 'footer'),
            'sidebar' => $this->firstOuterHtml($document, 'aside'),
        ];
    }

    private function firstOuterHtml(DOMDocument $document, string $tag): string
    {
        $nodes = $document->getElementsByTagName($tag);
        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            return '';
        }

        $saved = $document->saveHTML($node);

        return is_string($saved) ? $saved : '';
    }

    private function sanitizeFragment(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $sanitized = (new HtmlDomSanitizer())->sanitize($html, self::LAYOUT_TAGS);
        $sanitized = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/<(iframe|object|embed|link|meta|base)\b[^>]*>.*?<\/\1>/is', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/<(iframe|object|embed|link|meta|base)\b[^>]*\/?>/i', '', $sanitized) ?? $sanitized;

        return $sanitized;
    }

    private function buildTemplate(bool $withSidebar): string
    {
        $sidebar = $withSidebar ? "  {{> sidebar}}\n" : '';

        return "<!DOCTYPE html>\n"
            . "<html lang=\"en\">\n"
            . "<head>\n"
            . "  <meta charset=\"utf-8\">\n"
            . "  <title>{{title}}</title>\n"
            . "</head>\n"
            . "<body class=\"pg-theme-normalized pg-template-default\">\n"
            . "  {{> header}}\n"
            . $sidebar
            . "  <main class=\"pg-main\" id=\"main-content\">{{content}}</main>\n"
            . "  {{> footer}}\n"
            . "</body>\n"
            . "</html>\n";
    }

    /**
     * @param array<string, string> $buffers
     */
    private function collectCss(array $buffers): string
    {
        $chunks = [];
        $paths = array_keys($buffers);
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            if (!str_ends_with(strtolower($path), '.css')) {
                continue;
            }
            $chunks[] = '/* ' . str_replace('*/', '', $path) . " */\n" . $buffers[$path];
        }

        return implode("\n\n", $chunks);
    }

    /**
     * @param array<string, string> $buffers
     * @return list<array{path: string, content: string}>
     */
    private function collectJs(array $buffers): array
    {
        $items = [];
        $paths = array_keys($buffers);
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            if (!str_ends_with(strtolower($path), '.js')) {
                continue;
            }
            $items[] = ['path' => $path, 'content' => $buffers[$path]];
        }

        return $items;
    }

    /**
     * @param list<string> $dropped
     */
    private function rewriteCss(string $css, array &$dropped): string
    {
        $css = str_ireplace(['</style', '</script'], '', $css);

        $imports = preg_match_all('/@import\s+(?:url\s*\(\s*)?[\'"]?\s*(?:https?:|\/\/)/i', $css);
        if (is_int($imports) && $imports > 0) {
            $dropped[] = 'Dropped remote @import (' . $imports . ')';
        }
        $css = preg_replace('/@import\s+(?:url\s*\(\s*)?[\'"]?\s*https?:[^;]+;?/i', '/* import removed */', $css) ?? $css;
        $css = preg_replace('/@import\s+(?:url\s*\(\s*)?[\'"]?\s*\/\/[^;]+;?/i', '/* import removed */', $css) ?? $css;

        $jsUrls = preg_match_all('/url\s*\(\s*[\'"]?\s*javascript\s*:/i', $css);
        if (is_int($jsUrls) && $jsUrls > 0) {
            $dropped[] = 'Dropped url(javascript:) (' . $jsUrls . ')';
        }
        $css = preg_replace('/url\s*\(\s*[\'"]?\s*javascript\s*:[^)]*\)/i', 'url()', $css) ?? $css;

        $remote = preg_match_all('/url\s*\(\s*[\'"]?\s*https?:/i', $css);
        if (is_int($remote) && $remote > 0) {
            $dropped[] = 'Dropped remote CSS url() (' . $remote . ')';
        }
        $css = preg_replace('/url\s*\(\s*([\'"]?)\s*https?:\/\/[^)]+\)/i', 'url()', $css) ?? $css;

        if (preg_match('/expression\s*\(/i', $css) === 1) {
            $dropped[] = 'Dropped CSS expression()';
        }
        $css = preg_replace('/expression\s*\(/i', 'invalid(', $css) ?? $css;
        $css = preg_replace('/behavior\s*:/i', '/* behavior removed */', $css) ?? $css;
        $css = preg_replace('/-moz-binding\s*:/i', '/* binding removed */', $css) ?? $css;

        $css = trim($css);
        if ($css === '') {
            return "/* Normalized theme CSS — persist ships in 88g. */\nbody { margin: 0; }\n";
        }

        return $css . "\n";
    }

    /**
     * @param list<array{path: string, content: string}> $scripts
     * @param list<string> $dropped
     */
    private function keepPassingJavascript(string $themeId, array $scripts, array &$dropped): ?string
    {
        if ($scripts === []) {
            return null;
        }

        $kept = [];
        foreach ($scripts as $script) {
            $logical = str_starts_with($script['path'], '__') ? 'assets/theme.js' : $script['path'];
            if (!str_ends_with(strtolower($logical), '.js')) {
                $logical = 'assets/theme.js';
            }
            $result = $this->validator->validate($themeId, $logical, $script['content']);
            if (!$result['valid']) {
                $dropped[] = 'Dropped JavaScript ' . $script['path'] . ' (policy)';
                continue;
            }
            $kept[] = $script['content'];
        }

        if ($kept === []) {
            return null;
        }

        return implode("\n\n", $kept) . "\n";
    }

    private function inferName(string $html, string $themeId): string
    {
        if (preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $matches) === 1) {
            $title = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $title = preg_replace('/\s+/', ' ', $title) ?? $title;
            if ($title !== '') {
                return mb_substr($title, 0, 80);
            }
        }

        return $themeId;
    }

    private function buildManifest(string $themeId, string $name, bool $sidebar, ?string $js): string
    {
        $slots = ['header', 'main', 'footer'];
        if ($sidebar) {
            $slots[] = 'sidebar';
        }

        $manifest = [
            'manifestVersion' => 1,
            'id' => $themeId,
            'name' => $name,
            'version' => '0.1.0',
            'description' => 'Normalized in Theme Studio (It.88c). Review markers before save.',
            'slots' => $slots,
            'templates' => ['default'],
            'supports' => ['appearance-tokens', 'branding', 'navigation'],
        ];

        if ($js !== null) {
            $manifest['assets'] = [
                'scripts' => [[
                    'path' => 'assets/theme.js',
                    'load' => 'defer',
                    'integrity' => $this->integrity->integrityFor($js),
                ]],
            ];
        }

        return JsonHelper::encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    }

    /**
     * @param array<string, string> $files
     * @return list<StudioMarker>
     */
    private function outputMarkers(string $themeId, array $files): array
    {
        $markers = [];
        foreach ($files as $path => $content) {
            $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($ext, ['html', 'css', 'js', 'json'], true)) {
                continue;
            }
            $result = $this->validator->validate($themeId, $path, $content);
            foreach ($result['markers'] as $marker) {
                $markers[] = [
                    'line' => $marker['line'],
                    'message' => $marker['message'],
                    'relativePath' => $path,
                ];
            }
        }

        return $markers;
    }
}
