<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Blueprint\Models\Blueprint;
use PaginiumCMS\Core\Blueprint\Models\FieldDefinition;
use PaginiumCMS\Core\Blueprint\Services\BlueprintRepository;
use PaginiumCMS\Core\Blueprint\Services\DynamicValidator;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class BlueprintController
{
    public function __construct(
        private BlueprintRepository $blueprints,
        private DynamicValidator $dynamicValidator,
        private JsonResponder $json
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'blueprints' => $this->blueprints->list(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? '');

        try {
            $blueprint = $this->blueprints->get($type);
        } catch (RuntimeException) {
            return $this->json->error($response, 'Blueprint not found', 404);
        }

        return $this->json->success($response, $blueprint->toArray());
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? '');
        $payload = $this->parseJsonBody($request);

        try {
            $existing = $this->blueprints->get($type);
        } catch (RuntimeException) {
            return $this->json->error($response, 'Blueprint not found', 404);
        }

        $payload['type'] = $type;
        if ($existing->system) {
            $payload['system'] = true;
        }

        $fields = [];
        foreach ($payload['fields'] ?? [] as $field) {
            if (!is_array($field)) {
                continue;
            }

            $fields[] = FieldDefinition::fromArray($field);
        }

        if ($fields === []) {
            return $this->json->error($response, 'Blueprint must contain at least one field', 422);
        }

        $blueprint = new Blueprint(
            type: $type,
            label: (string) ($payload['label'] ?? $existing->label),
            description: (string) ($payload['description'] ?? $existing->description),
            fields: $fields,
            system: $existing->system
        );

        $saved = $this->blueprints->save($blueprint);

        return $this->json->success($response, $saved->toArray(), 200, 'Blueprint saved');
    }

    /**
     * @param array<string, string> $args
     */
    public function validatePayload(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? '');
        $payload = $this->parseJsonBody($request);
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        try {
            $validated = $this->dynamicValidator->validate($type, $data);
        } catch (ValidationException $e) {
            return $this->json->validation($response, 'Validation failed', $e->getErrors());
        }

        return $this->json->success($response, [
            'valid' => true,
            'validated' => $validated,
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $type = (string) ($args['type'] ?? '');

        try {
            $this->blueprints->delete($type);
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }

        return $this->json->success($response, null, 200, 'Blueprint deleted');
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();
        if (trim($raw) === '') {
            return [];
        }

        $decoded = JsonHelper::decode($raw);

        return $this->normalizeBody($decoded);
    }

    /**
     * @param array<int|string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizeBody(array $decoded): array
    {
        $normalized = [];
        foreach ($decoded as $key => $value) {
            $normalized[(string) $key] = $value;
        }

        return $normalized;
    }
}
