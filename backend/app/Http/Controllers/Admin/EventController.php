<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Core\Events\Services\EventRepository;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin CRUD for site events (It.93n). ADMIN+ via RoleMiddleware.
 */
final class EventController
{
    public function __construct(
        private EventRepository $events,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'events' => $this->events->list(),
            'statuses' => EventRepository::STATUSES,
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $event = $this->events->get((string) ($args['id'] ?? ''));
        if ($event === null) {
            return $this->json->error($response, 'Event not found', 404);
        }

        return $this->json->success($response, ['event' => $event]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $event = $this->events->create($body);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['event' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['event' => $event], 201);
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = RequestJsonBody::decode($request) ?? [];

        try {
            $event = $this->events->update((string) ($args['id'] ?? ''), $body);
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Event not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['event' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['event' => $event]);
    }

    /**
     * @param array<string, string> $args
     */
    public function destroy(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->events->delete((string) ($args['id'] ?? ''));
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Event not found') {
                return $this->json->error($response, $exception->getMessage(), 404);
            }

            return $this->json->validation($response, 'Validation failed', ['id' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['removed' => true]);
    }
}
