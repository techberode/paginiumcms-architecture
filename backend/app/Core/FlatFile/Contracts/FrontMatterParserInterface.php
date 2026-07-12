<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Contracts;

/**
 * Rozhranie pre parser YAML Front Matter.
 */
interface FrontMatterParserInterface
{
    /**
     * Parsuje Front Matter z Markdown obsahu.
     *
     * @param string $content Celý obsah Markdown súboru.
     * @return array<string, mixed> Parsovaný Front Matter.
     */
    public function parse(string $content): array;

    /**
     * Serializuje Front Matter do YAML reťazca.
     *
     * @param array<string, mixed> $frontMatter Asociatívne pole.
     * @return string YAML reťazec s delimiterom.
     */
    public function serialize(array $frontMatter): string;

    /**
     * Extrahuje iba Front Matter z obsahu.
     *
     * @param string $content Celý obsah.
     * @return array<string, mixed> Parsovaný Front Matter.
     */
    public function extractFrontMatter(string $content): array;

    /**
     * Extrahuje iba obsah (bez Front Matter).
     *
     * @param string $content Celý obsah.
     * @return string Obsah bez Front Matter.
     */
    public function extractContent(string $content): string;

    /**
     * Zistí, či obsah obsahuje Front Matter.
     *
     * @param string $content Celý obsah.
     * @return bool TRUE ak obsahuje Front Matter.
     */
    public function hasFrontMatter(string $content): bool;
}
