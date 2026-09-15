<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Navigation;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Navigation;
use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;
use PaginiumCMS\Modules\Navigation\Services\NavigationRichFieldValidator;
use PaginiumCMS\Modules\Navigation\Services\SecondaryNavigationRepository;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NavigationController
{
    public function __construct(
        private NavigationRepositoryInterface $navigationRepository,
        private SecondaryNavigationRepository $secondaryNavigation,
        private NavigationRichFieldValidator $richFieldValidator,
        private SettingsRepositoryInterface $settings,
        private JsonResponder $json
    ) {
    }

    public function getNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $navigation = $this->navigationRepository->load();

        return $this->json->success($response, $navigation->toPublicPayload());
    }

    public function getAdminNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->navigationRepository->load()->jsonSerialize());
    }

    public function getSecondaryNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->secondaryEnabled()) {
            return $this->json->success($response, []);
        }

        return $this->json->success($response, $this->secondaryNavigation->load()->toPublicPayload());
    }

    public function getAdminSecondaryNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->secondaryNavigation->load()->jsonSerialize());
    }

    public function updateNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->persist($request, $response, $this->navigationRepository, $this->resolvePrimaryMaxDepth());
    }

    public function updateSecondaryNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->persist($request, $response, $this->secondaryNavigation, $this->resolveSecondaryMaxDepth());
    }

    private function persist(
        ServerRequestInterface $request,
        ResponseInterface $response,
        NavigationRepositoryInterface $repository,
        int $maxDepth
    ): ResponseInterface {
        $data = RequestJsonBody::decode($request);
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
            $depthError = $this->validateMaxDepth($navigation, $maxDepth);
            if ($depthError !== null) {
                return $this->json->error($response, $depthError, 422);
            }
            $repository->save($navigation);

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
                return Lang::get('max_depth_exceeded', ['max' => (string) $maxLevels], 'navigation');
            }
        }

        return null;
    }

    private function resolvePrimaryMaxDepth(): int
    {
        $depth = (int) $this->settings->get('navigation.maxDepth', 3);

        return max(3, min(4, $depth));
    }

    private function resolveSecondaryMaxDepth(): int
    {
        $depth = (int) $this->settings->get('secondaryNav.maxDepth', 3);

        return max(1, min(6, $depth));
    }

    private function secondaryEnabled(): bool
    {
        return (bool) $this->settings->get('secondaryNav.enabled', false);
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
