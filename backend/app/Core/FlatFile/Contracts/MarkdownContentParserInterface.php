<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

/**
 * Rozhranie pre parser Markdown obsahu (bez Front Matter).
 */
interface MarkdownContentParserInterface
{
    /**
     * Prevedie Markdown na HTML.
     *
     * @param string $markdown Markdown reťazec.
     * @return string HTML výstup.
     */
    public function parse(string $markdown): string;

    /**
     * Prevedie Markdown na HTML (iba inline prvky).
     *
     * @param string $markdown Markdown reťazec.
     * @return string HTML výstup.
     */
    public function parseInline(string $markdown): string;

    /**
     * Odstráni Markdown syntax a vráti čistý text.
     *
     * @param string $markdown Markdown reťazec.
     * @return string Čistý text.
     */
    public function stripMarkdown(string $markdown): string;

    /**
     * Extrahuje ukážku textu (excerpt) z Markdown.
     *
     * @param string $markdown Markdown reťazec.
     * @param int $length Maximálna dĺžka ukážky.
     * @return string Ukážka textu.
     */
    public function extractExcerpt(string $markdown, int $length = 160): string;
}
