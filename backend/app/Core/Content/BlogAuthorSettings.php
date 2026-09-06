<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Content\DefaultAuthorAvatar;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;

/**
 * Blog author identity for public articles — site defaults, CMS user linkage, or per-article overrides.
 */
final class BlogAuthorSettings
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private UserRepository $users,
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
        $configured = trim((string) ($this->settings->group('content')['blogAuthorAvatarUrl'] ?? ''));

        return $configured !== '' ? $configured : DefaultAuthorAvatar::STORAGE_URL;
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
        $user = $this->resolveLinkedUser($article);

        $storedAuthor = trim($article->getAuthor());
        if ($storedAuthor !== '') {
            $author = $storedAuthor;
        } elseif ($user !== null) {
            $author = $this->displayNameForUser($user);
        } else {
            $author = $this->defaultName();
        }

        $bioOverride = $article->getAuthorBio();
        if ($bioOverride !== '') {
            $authorBio = $bioOverride;
        } elseif ($user !== null && trim($user->getBio()) !== '') {
            $authorBio = trim($user->getBio());
        } else {
            $authorBio = $this->bio();
        }

        $avatarOverride = $article->getAuthorAvatarUrl();
        if ($avatarOverride !== '') {
            $authorAvatarUrl = $avatarOverride;
        } elseif ($user !== null) {
            $authorAvatarUrl = trim((string) ($user->getAvatarUrl() ?? ''));
            if ($authorAvatarUrl === '') {
                $authorAvatarUrl = $this->avatarUrl();
            }
        } else {
            $authorAvatarUrl = $this->avatarUrl();
        }

        return [
            'author' => $author,
            'authorBio' => $authorBio,
            'authorAvatarUrl' => $authorAvatarUrl,
            'showAuthorBox' => $this->showAuthorBox(),
        ];
    }

    /**
     * Keeps index/calendar author string populated when only authorId is stored.
     */
    public function syncStoredAuthorName(Article $article): void
    {
        if (trim($article->getAuthor()) !== '') {
            return;
        }

        $user = $this->resolveLinkedUser($article);
        if ($user === null) {
            return;
        }

        $article->setAuthor($this->displayNameForUser($user));
    }

    private function resolveLinkedUser(Article $article): ?User
    {
        $authorId = $article->getAuthorId();
        if ($authorId === '') {
            return null;
        }

        return $this->users->findById($authorId);
    }

    private function displayNameForUser(User $user): string
    {
        $name = trim($user->getName());

        return $name !== '' ? $name : $user->getEmail();
    }
}
