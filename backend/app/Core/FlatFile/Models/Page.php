<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

use PaginiumCMS\Core\Layout\PageLayoutCatalog;

/**
 * Model pre statickú stránku.
 */
class Page extends Content
{
    public function getTemplate(): string
    {
        return $this->frontMatter['template'] ?? 'default';
    }

    public function setTemplate(string $template): self
    {
        $this->frontMatter['template'] = $template;
        return $this;
    }

    /**
     * Layout structure template (It.58c) — distinct from chrome {@see getTemplate()}.
     */
    public function getLayoutTemplate(): string
    {
        $raw = $this->frontMatter['layoutTemplate'] ?? null;
        if (!is_string($raw) || $raw === '') {
            return PageLayoutCatalog::DEFAULT_TEMPLATE;
        }

        return PageLayoutCatalog::normalizeTemplate($raw);
    }

    public function setLayoutTemplate(string $layoutTemplate): self
    {
        $this->frontMatter['layoutTemplate'] = PageLayoutCatalog::normalizeTemplate($layoutTemplate);
        return $this;
    }

    public function isHomePage(): bool
    {
        return $this->getSlug() === 'home' || $this->getPath() === 'pages/home.md';
    }

    public function isContactPage(): bool
    {
        return $this->getSlug() === 'kontakt';
    }
}
