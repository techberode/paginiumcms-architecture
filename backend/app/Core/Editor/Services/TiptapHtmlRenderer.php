<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Renders sanitized HTML from Tiptap / ProseMirror JSON (Iteration 55).
 */
final class TiptapHtmlRenderer
{
    public function render(string $json): string
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return '';
        }

        return $this->renderNode($decoded);
    }

    /**
     * @param array<string, mixed> $node
     */
    private function renderNode(array $node): string
    {
        $type = (string) ($node['type'] ?? '');
        if ($type === 'doc') {
            return $this->renderChildren($node);
        }

        if ($type === 'text') {
            return $this->renderText($node);
        }

        if ($type === 'hardBreak') {
            return '<br />';
        }

        $inner = $this->renderChildren($node);

        return match ($type) {
            'paragraph' => $inner === '' ? '<p></p>' : '<p>' . $inner . '</p>',
            'heading' => $this->renderHeading($node, $inner),
            'bulletList' => '<ul>' . $inner . '</ul>',
            'orderedList' => '<ol>' . $inner . '</ol>',
            'listItem' => '<li>' . $inner . '</li>',
            'blockquote' => '<blockquote>' . $inner . '</blockquote>',
            'codeBlock' => '<pre><code>' . htmlspecialchars($inner, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>',
            'horizontalRule' => '<hr />',
            'table' => '<table>' . $inner . '</table>',
            'tableRow' => '<tr>' . $inner . '</tr>',
            'tableHeader' => '<th>' . $inner . '</th>',
            'tableCell' => '<td>' . $inner . '</td>',
            'image' => $this->renderImage($node),
            default => $inner,
        };
    }

    /**
     * @param array<string, mixed> $node
     */
    private function renderHeading(array $node, string $inner): string
    {
        $level = (int) (($node['attrs']['level'] ?? 2));
        $level = max(1, min(6, $level));

        return '<h' . $level . '>' . $inner . '</h' . $level . '>';
    }

    /**
     * @param array<string, mixed> $node
     */
    private function renderImage(array $node): string
    {
        $src = $this->sanitizeUrl((string) ($node['attrs']['src'] ?? ''));
        if ($src === '') {
            return '';
        }
        $alt = htmlspecialchars((string) ($node['attrs']['alt'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<img src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" alt="' . $alt . '" />';
    }

    /**
     * @param array<string, mixed> $node
     */
    private function renderText(array $node): string
    {
        $text = htmlspecialchars((string) ($node['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $marks = $node['marks'] ?? [];
        if (!is_array($marks)) {
            return $text;
        }

        foreach ($marks as $mark) {
            if (!is_array($mark)) {
                continue;
            }
            $type = (string) ($mark['type'] ?? '');
            $text = match ($type) {
                'bold' => '<strong>' . $text . '</strong>',
                'italic' => '<em>' . $text . '</em>',
                'underline' => '<u>' . $text . '</u>',
                'strike' => '<s>' . $text . '</s>',
                'code' => '<code>' . $text . '</code>',
                'link' => $this->renderLink($text, $mark),
                default => $text,
            };
        }

        return $text;
    }

    /**
     * @param array<string, mixed> $mark
     */
    private function renderLink(string $text, array $mark): string
    {
        $href = $this->sanitizeUrl((string) ($mark['attrs']['href'] ?? ''));
        if ($href === '') {
            return $text;
        }

        return '<a href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" rel="noopener noreferrer">' . $text . '</a>';
    }

    /**
     * @param array<string, mixed> $node
     */
    private function renderChildren(array $node): string
    {
        $children = $node['content'] ?? [];
        if (!is_array($children)) {
            return '';
        }

        $html = '';
        foreach ($children as $child) {
            if (is_array($child)) {
                $html .= $this->renderNode($child);
            }
        }

        return $html;
    }

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https', 'mailto'], true)) {
            return '';
        }

        return $url;
    }
}
