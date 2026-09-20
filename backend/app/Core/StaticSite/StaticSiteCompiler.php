<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\StaticSite;

use PaginiumCMS\Core\Editor\Services\ContentBodyRenderer;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;

/**
 * Writes one derived HTML document. Does not list SSOT (It.48 / 58g).
 */
final class StaticSiteCompiler
{
    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private StaticSiteSettings $settings,
        private ContentBodyRenderer $bodyRenderer,
    ) {
    }

    /**
     * @return array{type: string, slug: string, path: string, fingerprint: string}|null
     */
    public function writePublished(Content $content, string $type): ?array
    {
        try {
            $slug = StaticSitePath::assertSlug($content->getSlug());
            $type = StaticSitePath::assertType($type);
        } catch (\InvalidArgumentException) {
            return null;
        }

        $path = StaticSitePath::htmlRelative($type, $slug);
        $bodyHtml = $this->compiledBody($content);
        $this->writer->write($path, $this->wrapDocument($content, $type, $bodyHtml), false);

        return [
            'type' => $type,
            'slug' => $slug,
            'path' => $path,
            'fingerprint' => hash('sha256', $bodyHtml),
        ];
    }

    public function removeOne(string $type, string $slug): void
    {
        $path = StaticSitePath::htmlRelative($type, $slug);
        if ($this->reader->exists($path)) {
            $this->writer->delete($path, false);
        }
    }

    public function typeOf(Content $content): string
    {
        return $content instanceof Article ? 'article' : 'page';
    }

    private function compiledBody(Content $content): string
    {
        $cached = trim($content->getHtml());
        $format = $this->bodyRenderer->normalizeContentFormat(
            $content->getFrontMatter()['contentFormat'] ?? null,
            $content->getContent()
        );

        return $this->bodyRenderer->resolveHtml($content->getContent(), $format, $cached !== '' ? $cached : null);
    }

    private function wrapDocument(Content $content, string $type, string $bodyHtml): string
    {
        $title = htmlspecialchars($content->getTitle() !== '' ? $content->getTitle() : $content->getSlug(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $lang = htmlspecialchars($this->settings->htmlLang(), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = htmlspecialchars(trim($content->getDescription()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $kind = $content instanceof Article || $type === 'article' ? 'article' : 'page';
        $metaDescription = $description !== ''
            ? "\n<meta name=\"description\" content=\"{$description}\">"
            : '';

        return '<!DOCTYPE html>' . "\n"
            . '<html lang="' . $lang . '">' . "\n"
            . '<head>' . "\n"
            . '<meta charset="utf-8">' . "\n"
            . '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
            . '<meta name="generator" content="PaginiumCMS">' . "\n"
            . '<meta name="paginium-kind" content="' . $kind . '">' . "\n"
            . '<title>' . $title . '</title>' . $metaDescription . "\n"
            . '</head>' . "\n"
            . '<body>' . "\n"
            . '<article class="pg-static-body">' . "\n"
            . $bodyHtml . "\n"
            . '</article>' . "\n"
            . '</body>' . "\n"
            . '</html>' . "\n";
    }
}
