<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use function utf8_normalize;
use PaginiumCMS\Core\FlatFile\Contracts\FrontMatterParserInterface;
use PaginiumCMS\Core\FlatFile\Exception\InvalidFrontMatterException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Parser pre YAML Front Matter.
 *
 * Extrahuje a serializuje YAML hlavičku z Markdown súborov.
 */
class FrontMatterParser implements FrontMatterParserInterface
{
    private const DELIMITER = '---';

    /**
     * {@inheritDoc}
     */
    public function parse(string $content): array
    {
        $content = utf8_normalize($content);
        $parts = $this->splitContent($content);

        if ($parts === null) {
            return [];
        }

        try {
            $frontMatter = Yaml::parse($parts['frontMatter']);
        } catch (ParseException $e) {
            throw new InvalidFrontMatterException(
                $parts['frontMatter'],
                sprintf('Chyba pri parsovaní YAML: %s', $e->getMessage()),
                0,
                $e
            );
        }

        return $frontMatter ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(array $frontMatter): string
    {
        if (empty($frontMatter)) {
            return '';
        }

        $yaml = Yaml::dump($frontMatter, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        return self::DELIMITER . "\n" . $yaml . self::DELIMITER . "\n";
    }

    /**
     * {@inheritDoc}
     */
    public function extractFrontMatter(string $content): array
    {
        $parts = $this->splitContent($content);

        if ($parts === null) {
            return [];
        }

        try {
            return Yaml::parse($parts['frontMatter']) ?? [];
        } catch (ParseException) {
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function extractContent(string $content): string
    {
        $parts = $this->splitContent($content);

        if ($parts === null) {
            return $content;
        }

        return $parts['content'];
    }

    /**
     * {@inheritDoc}
     */
    public function hasFrontMatter(string $content): bool
    {
        return $this->splitContent($content) !== null;
    }

    /**
     * Rozdelí obsah na Front Matter a zvyšok.
     *
     * @param string $content Celý obsah.
     * @return array{frontMatter: string, content: string}|null
     */
    private function splitContent(string $content): ?array
    {
        // Odstránenie BOM
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Kontrola, či začína delimiterom
        if (!str_starts_with(trim($content), self::DELIMITER)) {
            return null;
        }

        // Nájdenie konca prvého delimitera
        $firstDelimiterPos = strpos($content, self::DELIMITER);
        if ($firstDelimiterPos === false) {
            return null;
        }

        // Nájdenie konca druhého delimitera
        $secondDelimiterPos = strpos($content, self::DELIMITER, $firstDelimiterPos + strlen(self::DELIMITER));
        if ($secondDelimiterPos === false) {
            return null;
        }

        $frontMatterStart = $firstDelimiterPos + strlen(self::DELIMITER);
        $frontMatterEnd = $secondDelimiterPos;

        $frontMatter = trim(substr($content, $frontMatterStart, $frontMatterEnd - $frontMatterStart));
        $contentStart = $secondDelimiterPos + strlen(self::DELIMITER);
        $content = ltrim(substr($content, $contentStart));

        return [
            'frontMatter' => $frontMatter,
            'content' => $content,
        ];
    }
}
