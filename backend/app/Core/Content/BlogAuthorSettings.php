<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Site-wide blog author identity (name, bio, avatar) from CMS settings — not CMS user accounts.
 */
final class BlogAuthorSettings
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function defaultName(): string
    {
        $content = $this->settings->group('content');
        $name = trim((string) ($content['blogAuthorName'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        $siteName = trim((string) ($this->settings->group('general')['siteName'] ?? ''));

        return $siteName !== '' ? $siteName : 'Redakcia';
    }

    public function bio(): string
    {
        return trim((string) ($this->settings->group('content')['blogAuthorBio'] ?? ''));
    }

    public function avatarUrl(): string
    {
        return trim((string) ($this->settings->group('content')['blogAuthorAvatarUrl'] ?? ''));
    }

    public function showAuthorBox(): bool
    {
        return ($this->settings->group('content')['blogShowAuthorBox'] ?? true) === true;
    }

    /**
     * @return array{author: string, authorBio: string, authorAvatarUrl: string, showAuthorBox: bool}
     */
    public function resolveForArticle(Article $article): array
    {
        $frontMatter = $article->getFrontMatter();
        $storedAuthor = trim($article->getAuthor());
        $author = $storedAuthor !== '' ? $storedAuthor : $this->defaultName();

        $bioOverride = trim((string) ($frontMatter['authorBio'] ?? ''));
        $authorBio = $bioOverride !== '' ? $bioOverride : $this->bio();

        return [
            'author' => $author,
            'authorBio' => $authorBio,
            'authorAvatarUrl' => $this->avatarUrl(),
            'showAuthorBox' => $this->showAuthorBox(),
        ];
    }
}
