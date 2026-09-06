<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Models;

/**
 * Model pre blogový článok.
 */
class Article extends Content
{
    public function getFeaturedImage(): string
    {
        $image = $this->frontMatter['featuredImage']
            ?? $this->frontMatter['featured_image']
            ?? $this->frontMatter['seoImage']
            ?? $this->frontMatter['ogImage']
            ?? '';

        return is_string($image) ? $image : '';
    }

    public function setFeaturedImage(string $url): self
    {
        $this->frontMatter['featuredImage'] = $url;
        return $this;
    }

    public function getReadingTime(): int
    {
        // Priemerná rýchlosť čítania: 200 slov/minútu
        $wordCount = str_word_count(strip_tags($this->html));
        return max(1, (int) ceil($wordCount / 200));
    }

    public function getExcerpt(int $length = 160): string
    {
        $text = strip_tags($this->html);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $text = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($text, ' ');

        if ($lastSpace !== false) {
            $text = mb_substr($text, 0, $lastSpace);
        }

        return $text . '…';
    }

    public function getCommentsEnabled(): bool
    {
        $value = $this->frontMatter['commentsEnabled'] ?? true;

        return $value !== false;
    }

    public function setCommentsEnabled(bool $enabled): self
    {
        $this->frontMatter['commentsEnabled'] = $enabled;

        return $this;
    }

    public function getCommentsRequireApproval(): ?bool
    {
        if (!array_key_exists('commentsRequireApproval', $this->frontMatter)) {
            return null;
        }

        return (bool) $this->frontMatter['commentsRequireApproval'];
    }

    public function setCommentsRequireApproval(?bool $value): self
    {
        if ($value === null) {
            unset($this->frontMatter['commentsRequireApproval']);
        } else {
            $this->frontMatter['commentsRequireApproval'] = $value;
        }

        return $this;
    }

    public function getCommentsAllowGuests(): ?bool
    {
        if (!array_key_exists('commentsAllowGuests', $this->frontMatter)) {
            return null;
        }

        return (bool) $this->frontMatter['commentsAllowGuests'];
    }

    public function setCommentsAllowGuests(?bool $value): self
    {
        if ($value === null) {
            unset($this->frontMatter['commentsAllowGuests']);
        } else {
            $this->frontMatter['commentsAllowGuests'] = $value;
        }

        return $this;
    }

    public function getAuthorId(): string
    {
        $raw = $this->frontMatter['authorId'] ?? '';

        return is_string($raw) ? trim($raw) : '';
    }

    public function setAuthorId(?string $authorId): self
    {
        $authorId = trim((string) $authorId);
        if ($authorId === '') {
            unset($this->frontMatter['authorId']);
        } else {
            $this->frontMatter['authorId'] = $authorId;
        }

        return $this;
    }

    public function getAuthorBio(): string
    {
        $raw = $this->frontMatter['authorBio'] ?? '';

        return is_string($raw) ? trim($raw) : '';
    }

    public function setAuthorBio(?string $bio): self
    {
        $bio = trim((string) $bio);
        if ($bio === '') {
            unset($this->frontMatter['authorBio']);
        } else {
            $this->frontMatter['authorBio'] = $bio;
        }

        return $this;
    }

    public function getAuthorAvatarUrl(): string
    {
        $raw = $this->frontMatter['authorAvatarUrl'] ?? '';

        return is_string($raw) ? trim($raw) : '';
    }

    public function setAuthorAvatarUrl(?string $url): self
    {
        $url = trim((string) $url);
        if ($url === '') {
            unset($this->frontMatter['authorAvatarUrl']);
        } else {
            $this->frontMatter['authorAvatarUrl'] = $url;
        }

        return $this;
    }
}
