<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Layout\Services\ShortcodeExpanderService;
use PaginiumCMS\Core\Snippets\Services\SnippetCatalogSeeder;
use PaginiumCMS\Core\Snippets\Services\SnippetInvalidationService;
use PaginiumCMS\Core\Snippets\Services\SnippetRepository;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkIdsParser;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Admin API for reusable content snippets (It.81f).
 */
final class SnippetController
{
    public function __construct(
        private SnippetRepository $snippets,
        private SnippetCatalogSeeder $catalogSeeder,
        private SnippetInvalidationService $invalidation,
        private ShortcodeExpanderService $expander,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->catalogSeeder->seedIfEmpty();

        return $this->json->success($response, [
            'snippets' => $this->snippets->list(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $name = (string) ($args['name'] ?? '');

        try {
            return $this->json->success($response, $this->snippets->get($name));
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function save(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $name = (string) ($args['name'] ?? '');
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        try {
            $saved = $this->snippets->save($name, $payload);
            $invalidated = $this->invalidation->invalidateForSnippet($name);

            return $this->json->success($response, [
                'snippet' => $saved,
                'invalidatedReferences' => $invalidated,
            ], 200, 'Snippet saved');
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }
    }

    public function preview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $body = (string) ($payload['body'] ?? '');
        $format = (string) ($payload['format'] ?? 'markdown');
        $tag = '[snippet name="preview"/]';

        try {
            $expanded = $format === 'html'
                ? $this->expander->expand($body)
                : $this->expander->expand($body);

            return $this->json->success($response, [
                'body' => $body,
                'expanded' => $expanded,
                'insertTag' => $tag,
            ]);
        } catch (\Throwable $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $name = (string) ($args['name'] ?? '');

        try {
            $this->snippets->delete($name);
            $this->invalidation->invalidateForSnippet($name);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['name' => $name, 'removed' => true]);
    }

    public function bulkDelete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $names = BulkIdsParser::fromRequest($request);
        if ($names === []) {
            return $this->json->error($response, 'No snippets selected', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($names as $name) {
            try {
                $this->snippets->delete($name);
                $this->invalidation->invalidateForSnippet($name);
                $batch->addSuccess($name);
            } catch (RuntimeException $exception) {
                $batch->addFailure($name, $exception->getMessage());
            }
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Snippets deleted');
    }
}
