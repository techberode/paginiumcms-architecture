<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Navigation;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Navigation;
use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;
use PaginiumCMS\Modules\Navigation\Services\NavigationRichFieldValidator;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NavigationController
{
    public function __construct(
        private NavigationRepositoryInterface $navigationRepository,
        private NavigationRichFieldValidator $richFieldValidator,
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

        foreach ($itemsPayload as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $richError = $this->richFieldValidator->validateEntry($entry);
            if ($richError !== null) {
                return $this->json->error($response, $richError, 422);
            }
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
     * @param array<int, mixed> $itemsPayload
     */
    private function buildNavigation(array $itemsPayload): Navigation
    {
        $items = [];

        foreach ($itemsPayload as $index => $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $item = NavigationItem::fromPayload($entry, $index);
            if ($item !== null) {
                $items[] = $item;
            }
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
