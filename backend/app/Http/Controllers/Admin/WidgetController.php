<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Cache\ContentCacheService;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\Layout\Services\WidgetCatalog;
use PaginiumCMS\Core\Layout\Services\WidgetDefinitionRepository;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Built-in + custom public widgets (It.93t). Visual admin + markdown insert.
 */
final class WidgetController
{
    public function __construct(
        private WidgetCatalog $widgets,
        private WidgetDefinitionRepository $custom,
        private ContentCacheService $contentCache,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'widgets' => $this->widgets->catalog(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['name'] ?? '');
        $definition = $this->custom->get($id);
        if ($definition === null) {
            return $this->json->error($response, 'Widget not found', 404);
        }

        return $this->json->success($response, ['widget' => $definition]);
    }

    public function preview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request) ?? [];
        $type = is_string($payload['type'] ?? null) ? strtolower(trim($payload['type'])) : '';
        $attrs = $payload['attrs'] ?? [];
        if (!is_array($attrs)) {
            $attrs = [];
        }

        $parts = ['type="' . $this->attr($type) . '"'];
        foreach ($attrs as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-z][a-z0-9_-]*$/', $key) || $key === 'type') {
                continue;
            }
            if (!is_scalar($value)) {
                continue;
            }
            $parts[] = $key . '="' . $this->attr((string) $value) . '"';
        }

        $rawAttrs = ' ' . implode(' ', $parts);
        $content = is_string($payload['content'] ?? null) ? (string) $payload['content'] : '';
        $html = $this->widgets->render($rawAttrs, $content);

        return $this->json->success($response, [
            'html' => $html,
            'markup' => '[widget' . $rawAttrs . ' /]',
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['name'] ?? '');
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        try {
            $saved = $this->custom->save($id, $payload, $this->widgets->builtinIds());
            $this->contentCache->invalidatePage();

            return $this->json->success($response, ['widget' => $saved], 200, 'Widget saved');
        } catch (CodePolicyViolationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['name'] ?? '');

        try {
            $this->custom->delete($id, $this->widgets->builtinIds());
            $this->contentCache->invalidatePage();
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['deleted' => $id], 200, 'Widget deleted');
    }

    private function attr(string $value): string
    {
        return str_replace(['"', "\n", "\r"], ['', ' ', ''], $value);
    }
}
