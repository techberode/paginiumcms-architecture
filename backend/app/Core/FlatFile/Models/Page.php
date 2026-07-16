<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

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

    public function isHomePage(): bool
    {
        return $this->getSlug() === 'home' || $this->getPath() === 'pages/home.md';
    }

    public function isContactPage(): bool
    {
        return $this->getSlug() === 'kontakt';
    }
}
