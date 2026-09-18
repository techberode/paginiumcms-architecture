<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Content;
use RuntimeException;

/**
 * Content facade scoped to a plugin's declared capabilities (It.89b).
 */
final class PluginContentGateway
{
    private const MAX_LIST = 50;

    /**
     * @param list<string> $capabilities
     */
    public function __construct(
        private ContentRepositoryInterface $repository,
        private string $pluginId,
        private array $capabilities,
        private ?string $actorUserId,
        private ?PluginCapabilityAuditor $auditor = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $type, string $slug): ?array
    {
        $this->assertCan(PluginCapabilityCatalog::CONTENT_READ);
        $this->auditor?->used($this->pluginId, PluginCapabilityCatalog::CONTENT_READ);
        $type = $this->normalizeType($type);
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $content = $this->repository->findBySlug($slug, $type);

        return $content === null ? null : $this->present($content, $type);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(string $type): array
    {
        $this->assertCan(PluginCapabilityCatalog::CONTENT_READ);
        $this->auditor?->used($this->pluginId, PluginCapabilityCatalog::CONTENT_READ);
        $type = $this->normalizeType($type);
        $items = $type === 'article'
            ? $this->repository->findAllArticles()
            : $this->repository->findAllPages();

        $out = [];
        foreach (array_slice($items, 0, self::MAX_LIST) as $item) {
            $out[] = $this->present($item, $type);
        }

        return $out;
    }

    /**
     * Update title / description / body on an existing document. Status and path are not writable.
     *
     * @param array<string, mixed> $fields
     */
    public function save(string $type, string $slug, array $fields): void
    {
        $type = $this->normalizeType($type);
        $slug = trim($slug);
        if ($slug === '') {
            throw new RuntimeException('Content slug is required.');
        }

        $content = $this->repository->findBySlug($slug, $type);
        if ($content === null) {
            throw new RuntimeException('Content not found.');
        }

        $this->assertCanWrite($content);
        $writeCap = in_array(PluginCapabilityCatalog::CONTENT_WRITE, $this->capabilities, true)
            ? PluginCapabilityCatalog::CONTENT_WRITE
            : PluginCapabilityCatalog::CONTENT_WRITE_OWN;
        $this->auditor?->used($this->pluginId, $writeCap);

        if (array_key_exists('title', $fields) && is_string($fields['title'])) {
            $content->setTitle($fields['title']);
        }
        if (array_key_exists('description', $fields) && is_string($fields['description'])) {
            $content->setDescription($fields['description']);
        }
        if (array_key_exists('body', $fields) && is_string($fields['body'])) {
            $content->setContent($fields['body']);
        }

        $this->repository->save($content);
    }

    private function assertCanWrite(Content $content): void
    {
        if (in_array(PluginCapabilityCatalog::CONTENT_WRITE, $this->capabilities, true)) {
            return;
        }

        if (!in_array(PluginCapabilityCatalog::CONTENT_WRITE_OWN, $this->capabilities, true)) {
            throw new PluginCapabilityDeniedException($this->pluginId, PluginCapabilityCatalog::CONTENT_WRITE);
        }

        $author = $content->getAuthor();
        if ($this->actorUserId === null || $this->actorUserId === '' || $author !== $this->actorUserId) {
            throw new PluginCapabilityDeniedException($this->pluginId, PluginCapabilityCatalog::CONTENT_WRITE_OWN);
        }
    }

    private function assertCan(string $capability): void
    {
        if (!in_array($capability, $this->capabilities, true)) {
            throw new PluginCapabilityDeniedException($this->pluginId, $capability);
        }
    }

    private function normalizeType(string $type): string
    {
        $type = trim($type);

        return $type === 'article' ? 'article' : 'page';
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Content $content, string $type): array
    {
        return [
            'type' => $type,
            'slug' => $content->getSlug(),
            'title' => $content->getTitle(),
            'status' => $content->getStatus(),
            'author' => $content->getAuthor(),
            'description' => $content->getDescription(),
            'body' => $content->getContent(),
            'tags' => $content->getTags(),
            'category' => $content->getCategory(),
        ];
    }
}
