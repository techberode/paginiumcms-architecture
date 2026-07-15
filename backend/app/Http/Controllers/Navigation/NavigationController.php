<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Navigation;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Navigation;
use PaginiumCMS\Core\FlatFile\Models\NavigationItem;
use PaginiumCMS\Modules\Navigation\Contracts\NavigationRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NavigationController
{
    public function __construct(
        private NavigationRepositoryInterface $navigationRepository
    ) {
    }

    public function getNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $navigation = $this->navigationRepository->load();

        return $this->jsonSuccess($response, $navigation->jsonSerialize());
    }

    public function updateNavigation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->jsonError($response, Lang::get('invalid_payload', [], 'navigation'), 400);
        }

        $itemsPayload = $data['items'] ?? $data;
        if (!is_array($itemsPayload)) {
            return $this->jsonError($response, Lang::get('invalid_payload', [], 'navigation'), 400);
        }

        try {
            $navigation = $this->buildNavigation($itemsPayload);
            $this->navigationRepository->save($navigation);

            return $this->jsonSuccess(
                $response,
                $navigation->jsonSerialize(),
                Lang::get('updated', [], 'navigation')
            );
        } catch (FlatFileException $e) {
            return $this->jsonError($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $itemsPayload
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
                $prop->setAccessible(true);
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

    private function jsonSuccess(ResponseInterface $response, mixed $data, ?string $message = null, int $status = 200): ResponseInterface
    {
        $payload = ['success' => true, 'data' => $data];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
