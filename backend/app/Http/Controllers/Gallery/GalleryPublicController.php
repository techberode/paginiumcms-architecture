<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Gallery;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\SettingsSchema;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Gallery\Contracts\GalleryRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GalleryPublicController
{
    public function __construct(
        private GalleryRepositoryInterface $repository,
        private SettingsRepositoryInterface $settings,
        private JsonResponder $json
    ) {
    }

    public function listPublished(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $gallerySettings = $this->settings->all()['gallery'] ?? [];
        $defaults = SettingsSchema::defaults()['gallery'] ?? [];
        $enabled = (bool) ($gallerySettings['enabled'] ?? $defaults['enabled'] ?? false);

        if (!$enabled) {
            return $this->json->success($response, [
                'items' => [],
                'count' => 0,
            ]);
        }

        $items = array_map(
            static fn ($item) => $item->jsonSerialize(),
            $this->repository->findPublishedOrdered()
        );

        return $this->json->success($response, [
            'items' => $items,
            'count' => count($items),
        ]);
    }
}
