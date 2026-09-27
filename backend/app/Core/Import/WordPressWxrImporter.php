<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

/**
 * Parses WordPress WXR export XML into normalized import rows (It.80g phase 1).
 *
 * @phpstan-import-type NormalizedImportRow from ImportRowTypes
 */
final class WordPressWxrImporter
{
    /**
     * @return list<NormalizedImportRow>
     */
    public function parseFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new FlatFileException('WordPress export file is not readable: ' . $path);
        }

        $xml = file_get_contents($path);
        if ($xml === false || trim($xml) === '') {
            throw new FlatFileException('WordPress export file is empty');
        }

        return $this->parseXml($xml);
    }

    /**
     * @return list<NormalizedImportRow>
     */
    public function parseXml(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $document = simplexml_load_string(
            $xml,
            \SimpleXMLElement::class,
            LIBXML_NONET | LIBXML_NOCDATA
        );

        if ($document === false) {
            $message = 'Invalid WordPress WXR XML';
            $errors = libxml_get_errors();
            if ($errors !== []) {
                $message .= ': ' . trim($errors[0]->message);
            }
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            throw new FlatFileException($message);
        }

        libxml_use_internal_errors($previous);

        $wpNamespace = $this->resolveWordPressNamespace($document);
        $excerptNamespace = str_contains($wpNamespace, '/1.0/') ? $wpNamespace : 'http://wordpress.org/export/1.2/excerpt/';

        $document->registerXPathNamespace('wp', $wpNamespace);
        $document->registerXPathNamespace('content', 'http://purl.org/rss/1.0/modules/content/');
        $document->registerXPathNamespace('excerpt', $excerptNamespace);

        $items = [];
        foreach ($document->channel->item ?? [] as $item) {
            $item->registerXPathNamespace('wp', $wpNamespace);
            $item->registerXPathNamespace('content', 'http://purl.org/rss/1.0/modules/content/');
            $item->registerXPathNamespace('excerpt', $excerptNamespace);

            $postType = trim((string) ($item->children('wp', true)->post_type ?? ''));
            if (!in_array($postType, ['post', 'page'], true)) {
                continue;
            }

            $status = trim((string) ($item->children('wp', true)->status ?? 'draft'));
            if ($status === 'trash' || $status === 'auto-draft') {
                continue;
            }

            $slug = trim((string) ($item->children('wp', true)->post_name ?? ''));
            if ($slug === '') {
                continue;
            }

            $title = trim((string) ($item->title ?? 'Untitled'));
            $encoded = trim((string) ($item->children('content', true)->encoded ?? ''));
            $excerpt = trim(strip_tags((string) ($item->children('excerpt', true)->encoded ?? '')));
            $postDate = trim((string) ($item->children('wp', true)->post_date ?? ''));

            /** @var list<string> $tags */
            $tags = [];
            foreach ($item->category ?? [] as $category) {
                $domain = (string) ($category->attributes()['domain'] ?? '');
                if ($domain === 'post_tag' || $domain === 'category') {
                    $tag = trim((string) $category);
                    if ($tag !== '') {
                        $tags[] = $tag;
                    }
                }
            }

            $items[] = ContentImportRowFactory::create(
                $postType === 'page' ? 'page' : 'article',
                $slug,
                $title,
                $encoded,
                $this->mapStatus($status),
                $postDate !== '' ? $postDate : gmdate('Y-m-d H:i:s'),
                $excerpt,
                $tags,
                'wordpress',
            );
        }

        return $items;
    }

    private function resolveWordPressNamespace(\SimpleXMLElement $document): string
    {
        $namespaces = $document->getNamespaces(true);
        foreach (['http://wordpress.org/export/1.2/', 'http://wordpress.org/export/1.1/', 'http://wordpress.org/export/1.0/'] as $candidate) {
            if (in_array($candidate, $namespaces, true)) {
                return $candidate;
            }
        }

        return 'http://wordpress.org/export/1.2/';
    }

    private function mapStatus(string $wpStatus): string
    {
        return match ($wpStatus) {
            'publish', 'future' => 'published',
            'private' => 'draft',
            default => 'draft',
        };
    }
}
