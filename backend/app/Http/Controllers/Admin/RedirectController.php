<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Seo\Services\RedirectStore;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkIdsParser;
use PaginiumCMS\Http\Support\JsonResponder;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin CRUD for HTTP redirect rules (It.80a).
 */
final class RedirectController
{
    public function __construct(
        private RedirectStore $store,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'rules' => $this->store->listRules(),
            'statusOptions' => [301, 302],
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->parseBody($request);
        $from = is_string($body['from'] ?? null) ? trim($body['from']) : '';
        $to = is_string($body['to'] ?? null) ? trim($body['to']) : '';
        $status = isset($body['status']) && is_numeric($body['status']) ? (int) $body['status'] : 301;
        $note = is_string($body['note'] ?? null) ? trim($body['note']) : '';

        if ($from === '') {
            return $this->json->validation($response, 'Validation failed', ['from' => 'From path is required']);
        }
        if ($to === '') {
            return $this->json->validation($response, 'Validation failed', ['to' => 'To path is required']);
        }

        try {
            $rule = $this->store->create($from, $to, $status, $note);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['rule' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['rule' => $rule], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = is_string($args['id'] ?? null) ? $args['id'] : '';
        if ($id === '') {
            return $this->json->error($response, 'Missing redirect id', 400);
        }

        $body = $this->parseBody($request);

        try {
            $rule = $this->store->update($id, $body);
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Redirect rule not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['rule' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['rule' => $rule]);
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = is_string($args['id'] ?? null) ? $args['id'] : '';
        if ($id === '') {
            return $this->json->error($response, 'Missing redirect id', 400);
        }

        try {
            $this->store->delete($id);
        } catch (InvalidArgumentException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['deleted' => true]);
    }

    public function bulkDelete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = BulkIdsParser::fromRequest($request);
        if ($ids === []) {
            return $this->json->error($response, 'No redirect rules selected', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            try {
                $this->store->delete($id);
                $batch->addSuccess($id);
            } catch (InvalidArgumentException $exception) {
                $batch->addFailure($id, $exception->getMessage());
            }
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Redirect rules deleted');
    }

    public function resolve(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $path = is_string($params['path'] ?? null) ? $params['path'] : '/';

        try {
            $match = $this->store->match($path);
        } catch (InvalidArgumentException) {
            return $response->withStatus(404);
        }

        if ($match === null) {
            return $response->withStatus(404);
        }

        return $response
            ->withStatus($match['status'])
            ->withHeader('Location', $match['to']);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseBody(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();

        return is_array($parsed) ? $parsed : [];
    }
}
