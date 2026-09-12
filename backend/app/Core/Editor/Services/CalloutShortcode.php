<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Expands guarded :::note / :::tip / :::warning blocks (It.90b).
 */
final class CalloutShortcode
{
    /** @var list<string> */
    public const TYPES = ['note', 'tip', 'warning'];

    private ?MarkdownConverter $inlineConverter = null;

    /**
     * @return array{0: string, 1: array<string, string>}
     */
    public function deferBlocks(string $markdown): array
    {
        /** @var array<string, string> $renders */
        $renders = [];
        $index = 0;
        $result = $markdown;

        foreach (self::TYPES as $type) {
            $pattern = '/:::' . preg_quote($type, '/') . '\s*\n([\s\S]*?)\n\s*:::/';
            $result = preg_replace_callback(
                $pattern,
                function (array $matches) use ($type, &$renders, &$index): string {
                    $html = $this->render($type, (string) $matches[1]);
                    if ($html === '') {
                        return '';
                    }

                    $key = 'paginium-callout:' . $type . ':' . $index;
                    $index++;
                    $renders[$key] = $html;

                    return "\n\n<!-- {$key} -->\n\n";
                },
                $result
            ) ?? $result;
        }

        return [$result, $renders];
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
        foreach (self::TYPES as $type) {
            if (str_contains($markdown, ':::' . $type)) {
                return true;
            }
        }

        return false;
    }

    public function stripBlocks(string $markdown): string
    {
        $result = $markdown;
        foreach (self::TYPES as $type) {
            $pattern = '/:::' . preg_quote($type, '/') . '\s*\n[\s\S]*?\n\s*:::/';
            $result = preg_replace($pattern, '', $result) ?? $result;
        }

        return $result;
    }

    public function validateBlocks(string $markdown): ?string
    {
        foreach (self::TYPES as $type) {
            $pattern = '/:::' . preg_quote($type, '/') . '\s*\n([\s\S]*?)\n\s*:::/';
            if (preg_match_all($pattern, $markdown, $matches) !== false) {
                foreach ($matches[1] as $body) {
                    $error = $this->validateBody((string) $body);
                    if ($error !== null) {
                        return $error;
                    }
                }
            }
        }

        return null;
    }

    private function render(string $type, string $body): string
    {
        $body = trim($body);
        if ($body === '' || !in_array($type, self::TYPES, true)) {
            return '';
        }

        if ($this->validateBody($body) !== null) {
            return '';
        }

        $inner = $this->inlineHtml($body);
        $class = htmlspecialchars('paginium-callout paginium-callout--' . $type, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<div class="' . $class . '">' . $inner . '</div>';
    }

    private function validateBody(string $body): ?string
    {
        $lower = strtolower($body);
        if (str_contains($lower, '<script') || str_contains($lower, '<iframe')) {
            return 'Callout bloky nepovoľujú skripty ani iframe.';
        }

        if (preg_match('/<[a-z][^>]*>/i', $body) === 1) {
            return 'Callout bloky nesmú obsahovať raw HTML.';
        }

        return null;
    }

    private function inlineHtml(string $body): string
    {
        if ($this->inlineConverter === null) {
            $environment = new Environment([
                'html_input' => 'escape',
                'allow_unsafe_links' => false,
            ]);
            $environment->addExtension(new CommonMarkCoreExtension());
            $this->inlineConverter = new MarkdownConverter($environment);
        }

        return $this->inlineConverter->convert($body)->getContent();
    }
}
