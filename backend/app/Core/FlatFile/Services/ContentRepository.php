<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Contracts\MarkdownParserInterface;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\FileNotFoundException;

/**
 * Repozitár pre prácu s obsahom.
 *
 * Spravuje CRUD operácie pre stránky a články.
 */
class ContentRepository implements ContentRepositoryInterface
{
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private MarkdownParserInterface $parser;
    private array $typeMapping = [
        'page' => Page::class,
        'article' => Article::class,
    ];

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        MarkdownParserInterface $parser
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->parser = $parser;
    }

    /**
     * {@inheritDoc}
     */
    public function findByPath(string $relativePath): ?Content
    {
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        try {
            $content = $this->reader->read($relativePath);
            $parsed = $this->parser->parse($content);

            // Určenie typu podľa cesty
            $type = $this->determineType($relativePath);

            // Vytvorenie inštancie
            $className = $this->typeMapping[$type] ?? Content::class;
            $object = new $className();

            if (!$object instanceof Content) {
                throw new FlatFileException(sprintf('Neplatná trieda pre typ: %s', $type));
            }

            $object->setPath($relativePath);
            $object->setFrontMatter($parsed['frontMatter']);
            $object->setContent($parsed['content']);
            $object->setHtml($parsed['html']);

            $info = $this->reader->getInfo($relativePath);
            $object->setSize($info['size']);
            $object->setModifiedAt($info['mtime']);

            return $object;
        } catch (FileNotFoundException) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug, string $type = 'page'): ?Content
    {
        $directory = $type === 'article' ? 'blog' : 'pages';
        $files = $this->reader->listFiles($directory, '*.md');

        foreach ($files as $file) {
            // Zostavíme plnú cestu
            if (strpos($file, $directory . '/') !== 0) {
                $fullPath = $directory . '/' . $file;
            } else {
                $fullPath = $file;
            }

            try {
                $content = $this->reader->read($fullPath);
                $frontMatter = $this->parser->extractFrontMatter($content);

                if (($frontMatter['slug'] ?? '') === $slug) {
                    return $this->findByPath($fullPath);
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findAllPages(array $filters = []): array
    {
        return $this->findAll('pages', $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function findAllArticles(array $filters = []): array
    {
        return $this->findAll('blog', $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function save(Content $content): void
    {
        $path = $content->getPath();

        if (empty($path)) {
            // Generovanie cesty na základe typu a slugu
            $type = $content instanceof Article ? 'blog' : 'pages';
            $slug = $content->getSlug();
            $path = $type . '/' . $slug . '.md';
            $content->setPath($path);
        }

        // Serializácia
        $markdown = $this->parser->serialize(
            $content->getFrontMatter(),
            $content->getContent()
        );

        // Zápis
        $this->writer->write($path, $markdown, true);

        // Aktualizácia metadát
        $info = $this->reader->getInfo($path);
        $content->setSize($info['size']);
        $content->setModifiedAt($info['mtime']);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Content $content, bool $permanent = false): void
    {
        $path = $content->getPath();

        if (empty($path)) {
            throw new FlatFileException('Obsah nemá nastavenú cestu');
        }

        $this->writer->delete($path, !$permanent);
    }

    /**
     * {@inheritDoc}
     */
    public function count(string $type, array $filters = []): int
    {
        $directory = $type === 'article' ? 'blog' : 'pages';

        // Získame všetky súbory a spočítame ich priamo
        $files = $this->reader->listFiles($directory, '*.md');

        if (empty($filters)) {
            return count($files);
        }

        // Ak máme filtre, musíme ich aplikovať
        $count = 0;
        foreach ($files as $file) {
            if (strpos($file, $directory . '/') !== 0) {
                $fullPath = $directory . '/' . $file;
            } else {
                $fullPath = $file;
            }

            try {
                $content = $this->reader->read($fullPath);
                $frontMatter = $this->parser->extractFrontMatter($content);

                $matches = true;
                foreach ($filters as $key => $value) {
                    if (($frontMatter[$key] ?? null) !== $value) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    $count++;
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return $count;
    }

    /**
     * Získa všetky položky z adresára.
     *
     * @param string $directory Adresár ('pages' alebo 'blog').
     * @param array<string, mixed> $filters Filtre.
     * @return array<int, Content>
     */
    private function findAll(string $directory, array $filters = []): array
    {
        // Získame zoznam súborov v adresári
        $files = $this->reader->listFiles($directory, '*.md');

        $results = [];

        foreach ($files as $file) {
            // $file je relatívna cesta, napr. 'home.md' alebo 'pages/home.md'
            // Potrebujeme vytvoriť plnú cestu
            if (strpos($file, $directory . '/') !== 0) {
                $fullPath = $directory . '/' . $file;
            } else {
                $fullPath = $file;
            }

            try {
                $content = $this->findByPath($fullPath);

                if ($content === null) {
                    continue;
                }

                // Aplikovanie filtrov
                $matches = true;
                foreach ($filters as $key => $value) {
                    $frontMatter = $content->getFrontMatter();
                    if (($frontMatter[$key] ?? null) !== $value) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    $results[] = $content;
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return $results;
    }

    /**
     * Určí typ obsahu podľa cesty.
     *
     * @param string $path Relatívna cesta.
     * @return string 'page' alebo 'article'.
     */
    private function determineType(string $path): string
    {
        if (strpos($path, 'blog/') === 0) {
            return 'article';
        }

        if (strpos($path, 'pages/') === 0) {
            return 'page';
        }

        return 'page';
    }
}
