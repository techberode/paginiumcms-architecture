<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Capabilities;

use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;

/**
 * Media metadata facade (no binary / write) scoped to media:read (It.89b).
 */
final class PluginMediaGateway
{
    private const MAX_LIST = 50;

    /**
     * @param list<string> $capabilities
     */
    public function __construct(
        private MediaRepositoryInterface $repository,
        private string $pluginId,
        private array $capabilities,
        private ?PluginCapabilityAuditor $auditor = null,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $path): ?array
    {
        $this->assertRead();
        $this->auditor?->used($this->pluginId, PluginCapabilityCatalog::MEDIA_READ);
        $path = trim($path);
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        $file = $this->repository->findByPath($path);

        return $file === null ? null : $this->present($file);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(): array
    {
        $this->assertRead();
        $this->auditor?->used($this->pluginId, PluginCapabilityCatalog::MEDIA_READ);
        $out = [];
        foreach (array_slice($this->repository->findAll(), 0, self::MAX_LIST) as $file) {
            $out[] = $this->present($file);
        }

        return $out;
    }

    private function assertRead(): void
    {
        if (!in_array(PluginCapabilityCatalog::MEDIA_READ, $this->capabilities, true)) {
            throw new PluginCapabilityDeniedException($this->pluginId, PluginCapabilityCatalog::MEDIA_READ);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function present(MediaFile $file): array
    {
        return [
            'id' => $file->getId(),
            'path' => $file->getPath(),
            'fileName' => $file->getFileName(),
            'mimeType' => $file->getMimeType(),
            'sizeBytes' => $file->getSizeBytes(),
            'altText' => $file->getAltText(),
            'folder' => $file->getFolder(),
            'url' => $file->getUrl(),
        ];
    }
}
