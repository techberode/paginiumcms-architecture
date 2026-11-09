<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Forms\Services\FormDefinitionPresets;
use PaginiumCMS\Core\Forms\Services\FormDefinitionRepository;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class FormController
{
    public function __construct(
        private FormDefinitionRepository $forms,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'forms' => $this->forms->list(),
            'presets' => FormDefinitionPresets::ids(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $definition = $this->forms->get($id);
        if ($definition === null) {
            return $this->json->error($response, 'Form not found', 404);
        }

        return $this->json->success($response, ['form' => $definition]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request) ?? [];

        try {
            $id = is_string($payload['id'] ?? null) ? (string) $payload['id'] : '';
            if ($id === '') {
                throw new InvalidArgumentException('Form id is required.');
            }
            $saved = $this->forms->save($id, $payload);
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }

        return $this->json->success($response, ['form' => $saved], 201);
    }

    public function createFromPreset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request) ?? [];
        $preset = is_string($payload['preset'] ?? null) ? trim((string) $payload['preset']) : '';
        $customId = is_string($payload['id'] ?? null) ? trim((string) $payload['id']) : '';

        try {
            $saved = $this->forms->createFromPreset($preset, $customId);
        } catch (RuntimeException | InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        return $this->json->success($response, ['form' => $saved], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        $payload = RequestJsonBody::decode($request) ?? [];
        $payload['id'] = $id;

        try {
            $saved = $this->forms->save($id, $payload);
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        return $this->json->success($response, ['form' => $saved]);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');
        try {
            $this->forms->delete($id);
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }

        return $this->json->success($response, ['deleted' => true]);
    }
}
