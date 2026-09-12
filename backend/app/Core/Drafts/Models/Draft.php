<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Drafts\Models;

use JsonSerializable;

/**
 * === Model: Draft ===
 * Rozpracovaný koncept obsahu (auto-save). Ukladá sa do samostatného flat-file
 * `data/drafts/{type}/{slug}.json`, oddelene od publikovaného obsahu.
 *
 * Anatómia:
 *  - type            : 'page' | 'article'
 *  - slug            : identifikátor obsahu
 *  - title           : rozpracovaný titulok (aktívna locale)
 *  - content         : rozpracovaný obsah (aktívna locale)
 *  - status          : draft/published/archived (rozpracovaný stav)
 *  - baseRevision    : revízia publikovaného obsahu, z ktorej koncept vychádza
 *  - editorSnapshot  : voliteľný JSON stav editora (locale, SEO, …) ak je zapnuté v nastaveniach
 *  - savedBy         : ID používateľa
 *  - savedAt         : čas posledného auto-save (unix)
 */
final class Draft implements JsonSerializable
{
    /**
     * @param array<string, mixed>|null $editorSnapshot
     */
    public function __construct(
        private string $type,
        private string $slug,
        private string $title,
        private string $content,
        private string $status,
        private string $baseRevision,
        private string $savedBy,
        private int $savedAt,
        private ?array $editorSnapshot = null
    ) {
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $snapshot = $data['editorSnapshot'] ?? null;

        return new self(
            (string) ($data['type'] ?? 'page'),
            (string) ($data['slug'] ?? ''),
            (string) ($data['title'] ?? ''),
            (string) ($data['content'] ?? ''),
            (string) ($data['status'] ?? 'draft'),
            (string) ($data['baseRevision'] ?? ''),
            (string) ($data['savedBy'] ?? ''),
            (int) ($data['savedAt'] ?? 0),
            is_array($snapshot) ? $snapshot : null
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getBaseRevision(): string
    {
        return $this->baseRevision;
    }

    public function getSavedAt(): int
    {
        return $this->savedAt;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEditorSnapshot(): ?array
    {
        return $this->editorSnapshot;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function jsonSerialize(): array
    {
        $payload = [
            'type' => $this->type,
            'slug' => $this->slug,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'baseRevision' => $this->baseRevision,
            'savedBy' => $this->savedBy,
            'savedAt' => $this->savedAt,
        ];

        if ($this->editorSnapshot !== null && $this->editorSnapshot !== []) {
            $payload['editorSnapshot'] = $this->editorSnapshot;
        }

        return $payload;
    }
}
