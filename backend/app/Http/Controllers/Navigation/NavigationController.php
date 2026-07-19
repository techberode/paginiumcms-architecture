<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Navigation;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Navigation;
use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NavigationController
{
    public function __construct(
        private NavigationRepositoryInterface $navigationRepository,
        private JsonResponder $json
    ) {
    }

    public function getNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $navigation = $this->navigationRepository->load();

        return $this->json->success($response, $navigation->jsonSerialize());
    }

    public function updateNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'navigation'), 400);
        }

        $itemsPayload = $data['items'] ?? $data;
        if (!is_array($itemsPayload)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'navigation'), 400);
        }

        try {
            $navigation = $this->buildNavigation($itemsPayload);
            $depthError = $this->validateMaxDepth($navigation, 3);
            if ($depthError !== null) {
                return $this->json->error($response, $depthError, 422);
            }
            $this->navigationRepository->save($navigation);

            return $this->json->success(
                $response,
                $navigation->jsonSerialize(),
                200,
                Lang::get('updated', [], 'navigation')
            );
        } catch (FlatFileException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int, array<int|string, mixed>> $itemsPayload
     * @param array<int|string, mixed> $itemsPayload
     */
    private function buildNavigation(array $itemsPayload): Navigation
    {
        $items = [];

        foreach ($itemsPayload as $index => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $label = trim((string) ($entry['label'] ?? ''));
            $path = trim((string) ($entry['path'] ?? ''));
            if ($label === '' || $path === '') {
                continue;
            }

            $item = new NavigationItem($label, $path);
            if (!empty($entry['id'])) {
                $reflection = new \ReflectionClass($item);
                $prop = $reflection->getProperty('id');
                $prop->setValue($item, (string) $entry['id']);
            }

            $item->setOrder((int) ($entry['order'] ?? $index));
            if (!empty($entry['target'])) {
                $item->setTarget((string) $entry['target']);
            }
            if (array_key_exists('parentId', $entry)) {
                $item->setParentId($entry['parentId'] !== null ? (string) $entry['parentId'] : null);
            }
            if (array_key_exists('icon', $entry)) {
                $item->setIcon($entry['icon'] !== null ? (string) $entry['icon'] : null);
            }

            $items[] = $item;
        }

        return new Navigation($items);
    }

    private function validateMaxDepth(Navigation $navigation, int $maxLevels): ?string
    {
        foreach ($navigation->getItems() as $item) {
            $depth = $this->itemDepth($navigation, $item->getId(), 1);
            if ($depth > $maxLevels) {
                return Lang::get('max_depth_exceeded', [], 'navigation');
            }
        }

        return null;
    }

    private function itemDepth(Navigation $navigation, string $itemId, int $depth): int
    {
        $item = $navigation->getItemById($itemId);
        if ($item === null || $item->getParentId() === null) {
            return $depth;
        }

        return $this->itemDepth($navigation, $item->getParentId(), $depth + 1);
    }
}
