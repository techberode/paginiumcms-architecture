<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use function utf8_normalize;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;

class MarkdownContentParser implements MarkdownContentParserInterface
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $config = [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'external_link' => [
                'internal_hosts' => ['localhost', 'paginiumcms.local'],
                'open_in_new_window' => true,
                'html_class' => 'external-link',
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new TaskListExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    public function parse(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    public function parseInline(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    public function stripMarkdown(string $markdown): string
    {
        $text = utf8_normalize($markdown);
        $text = preg_replace('/^#{1,6}\s+/m', '', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text) ?? $text;
        $text = preg_replace('/__(.+?)__/', '$1', $text) ?? $text;
        $text = preg_replace('/\*(.+?)\*/', '$1', $text) ?? $text;
        $text = preg_replace('/_(.+?)_/', '$1', $text) ?? $text;
        $text = preg_replace('/~~(.+?)~~/', '$1', $text) ?? $text;
        $text = preg_replace('/`(.+?)`/', '$1', $text) ?? $text;
        $text = preg_replace('/\[(.+?)\]\(.+?\)/', '$1', $text) ?? $text;
        $text = preg_replace('/!\[(.+?)\]\(.+?\)/', '$1', $text) ?? $text;
        $text = preg_replace('/^[\-\*\+]\s+/m', '', $text) ?? $text;
        $text = preg_replace('/^\d+\.\s+/m', '', $text) ?? $text;
        $text = preg_replace('/^>\s+/m', '', $text) ?? $text;

        return trim($text);
    }

    public function extractExcerpt(string $markdown, int $length = 160): string
    {
        $text = $this->stripMarkdown($markdown);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        $text = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($text, ' ');
        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace);
        }
        return $text . '…';
    }
}
