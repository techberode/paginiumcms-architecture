<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Security\Services\ContentSecuritySanitizer;
use PaginiumCMS\Core\Security\Services\HtmlDomSanitizer;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Support\LogSanitizer;

/**
 * In-memory Theme Studio preview (It.88d). Never writes disk, never executes JS.
 * HTML/CSS/JS run the same untrusted validators as 88b. Manifest/markdown are not
 * a preview gate (persist still validates theme.json in 88g).
 *
 * @phpstan-type StudioMarker array{line: int, message: string}
 * @phpstan-type PreviewIssue array{relativePath: string, valid: bool, markers: list<StudioMarker>}
 * @phpstan-type PreviewResult array{blocked: bool, document: string, template: string, issues: list<PreviewIssue>}
 */
final class ThemeStudioPreviewService
{
    public const MAX_FILES = 24;

    public const MAX_TOTAL_BYTES = 1572864;

    public const MAX_PARTIAL_DEPTH = 3;

    public const SAMPLE_TITLE = 'Theme Studio preview';

    public const SAMPLE_SITE_NAME = 'PaginiumCMS';

    /**
     * Fail-closed CSP for the preview srcdoc (opaque iframe, no scripts).
     */
    public const DOCUMENT_CSP = "default-src 'none'; img-src data: https: http:; style-src 'unsafe-inline'; font-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'; script-src 'none'; object-src 'none'; connect-src 'none'";

    /** @var list<string> */
    private const LAYOUT_TAGS = [
        'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'img', 'blockquote',
        'code', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'div', 'article', 'section', 'aside', 'span',
        'header', 'footer', 'main', 'nav', 'figure', 'figcaption',
        'cite', 'small', 'time',
    ];

    public function __construct(
        private ThemeStudioService $studio,
        private ThemeStudioValidator $validator,
        private ContentSecuritySanitizer $sanitizer,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param array<mixed> $files
     * @return PreviewResult
     */
    public function preview(string $themeId, array $files, string $template = ''): array
    {
        $id = trim($themeId);
        if ($id === '') {
            $id = 'untitled-theme';
        }
        if (!ThemeStudioService::isValidThemeId($id)) {
            throw new ThemeStudioException('Invalid theme id.', 400);
        }

        $buffers = $this->normalizeFiles($files);
        $issues = $this->validateAll($id, $buffers);
        $templatePath = $this->resolveTemplatePath($buffers, $template);

        if ($issues !== []) {
            $this->logger?->warning('Theme studio preview blocked', LogSanitizer::context([
                'themeId' => $id,
                'template' => $templatePath,
                'issues' => (string) count($issues),
            ]));

            return [
                'blocked' => true,
                'document' => '',
                'template' => $templatePath,
                'issues' => $issues,
            ];
        }

        $layout = $buffers[$templatePath];
        $expanded = $this->expandPartials($layout, $buffers, 0);
        $interpolated = $this->interpolate($expanded, $this->sampleTokens());
        $body = $this->sanitizeLayout($this->extractBody($interpolated));
        $css = $this->concatenateCss($buffers);
        $document = $this->buildDocument($body, $css);

        return [
            'blocked' => false,
            'document' => $document,
            'template' => $templatePath,
            'issues' => [],
        ];
    }

    /**
     * @param array<mixed> $files
     * @return array<string, string>
     */
    private function normalizeFiles(array $files): array
    {
        if ($files === []) {
            throw new ThemeStudioException('files is required.', 400);
        }

        if (count($files) > self::MAX_FILES) {
            throw new ThemeStudioException('Too many theme files in the preview payload.', 413);
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
                throw new ThemeStudioException('Theme preview payload is too large.', 413);
            }

            $buffers[$relative] = $content;
        }

        return $buffers;
    }

    /**
     * @param array<string, string> $buffers
     * @return list<PreviewIssue>
     */
    private function validateAll(string $themeId, array $buffers): array
    {
        $issues = [];
        foreach ($buffers as $path => $content) {
            $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if ($extension === 'json' || $extension === 'md') {
                continue;
            }

            $result = $this->validator->validate($themeId, $path, $content);
            if ($result['valid']) {
                continue;
            }

            $issues[] = [
                'relativePath' => $result['relativePath'],
                'valid' => false,
                'markers' => $result['markers'],
            ];
        }

        return $issues;
    }

    /**
     * @param array<string, string> $buffers
     */
    private function resolveTemplatePath(array $buffers, string $template): string
    {
        $requested = trim($template);
        if ($requested !== '') {
            $path = $this->studio->assertBufferPath($requested);
            if (!isset($buffers[$path])) {
                throw new ThemeStudioException('Preview template is not in the submitted files.', 400);
            }
            if (!str_ends_with(strtolower($path), '.html')) {
                throw new ThemeStudioException('Preview template must be an HTML file.', 400);
            }

            return $path;
        }

        if (isset($buffers['templates/default.html'])) {
            return 'templates/default.html';
        }

        foreach (array_keys($buffers) as $path) {
            if (str_starts_with($path, 'templates/') && str_ends_with(strtolower($path), '.html')) {
                return $path;
            }
        }

        foreach (array_keys($buffers) as $path) {
            if (str_ends_with(strtolower($path), '.html')) {
                return $path;
            }
        }

        throw new ThemeStudioException('No layout HTML to preview.', 400);
    }

    /**
     * @param array<string, string> $buffers
     */
    private function expandPartials(string $html, array $buffers, int $depth): string
    {
        if ($depth > self::MAX_PARTIAL_DEPTH) {
            return preg_replace('/\{\{>\s*[a-zA-Z0-9_-]+\s*\}\}/', '', $html) ?? $html;
        }

        $expanded = preg_replace_callback(
            '/\{\{>\s*([a-zA-Z0-9_-]+)\s*\}\}/',
            function (array $matches) use ($buffers, $depth): string {
                $name = $matches[1];
                $path = 'partials/' . $name . '.html';
                $partial = $buffers[$path] ?? '';
                if ($partial === '') {
                    return '';
                }

                return $this->expandPartials($partial, $buffers, $depth + 1);
            },
            $html
        );

        return is_string($expanded) ? $expanded : $html;
    }

    /**
     * @return array<string, string>
     */
    private function sampleTokens(): array
    {
        $year = (string) (int) date('Y');
        $title = htmlspecialchars(self::SAMPLE_TITLE, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $site = htmlspecialchars(self::SAMPLE_SITE_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $sample = $this->sanitizer->sanitizeHtml(
            '<article class="pg-preview-sample"><h1>' . $title . '</h1>'
            . '<p>Sample content for the sandboxed Theme Studio preview.</p></article>'
        );
        $navigation = $this->sanitizer->sanitizeHtml('<a href="/">Home</a>');

        return [
            'content' => $sample,
            'title' => $title,
            'siteName' => $site,
            'year' => $year,
            'navigation' => $navigation,
        ];
    }

    /**
     * @param array<string, string> $tokens
     */
    private function interpolate(string $html, array $tokens): string
    {
        foreach ($tokens as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
            $html = str_replace('{{ ' . $key . ' }}', $value, $html);
        }

        return preg_replace('/\{\{[^}]*\}\}/', '', $html) ?? $html;
    }

    private function extractBody(string $html): string
    {
        if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $html, $matches) === 1) {
            return $matches[1];
        }

        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<\/?html\b[^>]*>/i', '', $html) ?? $html;

        return preg_replace('/<head\b[^>]*>.*?<\/head>/is', '', $html) ?? $html;
    }

    private function sanitizeLayout(string $html): string
    {
        $sanitized = (new HtmlDomSanitizer())->sanitize($html, self::LAYOUT_TAGS);
        $sanitized = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/<(iframe|object|embed|link|meta|base)\b[^>]*>.*?<\/\1>/is', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/<(iframe|object|embed|link|meta|base)\b[^>]*\/?>/i', '', $sanitized) ?? $sanitized;

        return $sanitized;
    }

    /**
     * @param array<string, string> $buffers
     */
    private function concatenateCss(array $buffers): string
    {
        $chunks = [];
        $paths = array_keys($buffers);
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            if (!str_ends_with(strtolower($path), '.css')) {
                continue;
            }
            $chunks[] = '/* ' . str_replace('*/', '', $path) . ' */' . "\n" . $this->sanitizeCssBuffer($buffers[$path]);
        }

        return implode("\n\n", $chunks);
    }

    private function sanitizeCssBuffer(string $css): string
    {
        $css = str_ireplace(['</style', '</script'], '', $css);
        $css = preg_replace('/expression\s*\(/i', 'invalid(', $css) ?? $css;
        $css = preg_replace('/url\s*\(\s*[\'"]?\s*javascript\s*:/i', 'url(', $css) ?? $css;
        $css = preg_replace('/@import\s+(?:url\s*\(\s*)?[\'"]?\s*https?:/i', '/* import removed */', $css) ?? $css;

        return $css;
    }

    private function buildDocument(string $body, string $css): string
    {
        $style = $css !== ''
            ? "<style>\n" . $css . "\n</style>\n"
            : '';

        return '<!DOCTYPE html>' . "\n"
            . '<html lang="en">' . "\n"
            . '<head>' . "\n"
            . '<meta charset="utf-8">' . "\n"
            . '<meta http-equiv="Content-Security-Policy" content="' . self::DOCUMENT_CSP . '">' . "\n"
            . '<title>' . htmlspecialchars(self::SAMPLE_TITLE, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</title>' . "\n"
            . $style
            . '</head>' . "\n"
            . '<body>' . "\n"
            . $body . "\n"
            . '</body>' . "\n"
            . '</html>' . "\n";
    }
}
