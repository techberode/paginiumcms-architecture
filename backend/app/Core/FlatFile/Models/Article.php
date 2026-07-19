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
}
