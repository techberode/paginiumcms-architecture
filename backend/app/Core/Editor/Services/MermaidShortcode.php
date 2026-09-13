<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Expands guarded :::mermaid blocks to server-rendered SVG (It.90c).
 */
final class MermaidShortcode
{
    public const DIRECTIVE = 'mermaid';

    public function __construct(
        private MermaidDiagramRenderer $renderer = new MermaidDiagramRenderer()
    ) {
    }

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    public function deferBlocks(string $markdown): array
    {
        /** @var array<string, string> $renders */
        $renders = [];
        $index = 0;

        $result = preg_replace_callback(
            '/:::' . self::DIRECTIVE . '\s*\n([\s\S]*?)\n\s*:::/',
            function (array $matches) use (&$renders, &$index): string {
                $html = $this->renderBody((string) $matches[1]);
                if ($html === '') {
                    return '';
                }

                $key = 'paginium-mermaid:' . $index;
                $index++;
                $renders[$key] = $html;

                return "\n\n<!-- {$key} -->\n\n";
            },
            $markdown
        );

        return [is_string($result) ? $result : $markdown, $renders];
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
        $result = preg_replace('/:::' . self::DIRECTIVE . '\s*\n[\s\S]*?\n\s*:::/', '', $markdown);

        return is_string($result) ? $result : $markdown;
    }

    public function validateBlocks(string $markdown): ?string
    {
        if (preg_match_all('/:::' . self::DIRECTIVE . '\s*\n([\s\S]*?)\n\s*:::/', $markdown, $matches) === false) {
            return null;
        }

        foreach ($matches[1] as $body) {
            $error = $this->renderer->validateSource((string) $body);
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    private function renderBody(string $body): string
    {
        $svg = $this->renderer->renderSvg($body);
        if ($svg === null) {
            return '';
        }

        return '<figure class="paginium-mermaid" role="img" aria-label="Diagram">' . $svg . '</figure>';
    }
}
