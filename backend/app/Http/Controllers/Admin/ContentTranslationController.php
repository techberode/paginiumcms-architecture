<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Core\Translation\Services\TranslationService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Assisted content-translation API (It.76). Distinct from It.18d file catalog /api/admin/translations.
 */
final class ContentTranslationController
{
    public function __construct(
        private TranslationService $translations,
        private JsonResponder $json,
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->translations->status());
    }

    public function testConnection(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return $this->json->success($response, $this->translations->testConnection());
        } catch (TranslationException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $actor = $this->actor($request);
            $body = $request->getParsedBody();
            $payload = is_array($body) ? $body : [];
            $proposal = $this->translations->propose(
                (string) ($args['type'] ?? ''),
                (string) ($args['slug'] ?? ''),
                $payload,
                $actor['id'],
                $actor['email']
            );

            return $this->json->success($response, $proposal, 201, 'Translation proposal ready');
        } catch (TranslationException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $actor = $this->actor($request);

            return $this->json->success(
                $response,
                $this->translations->getProposal((string) ($args['jobId'] ?? ''), $actor['id'])
            );
        } catch (TranslationException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function apply(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $actor = $this->actor($request);
            $result = $this->translations->apply((string) ($args['jobId'] ?? ''), $actor['id'], $actor['email']);

            return $this->json->success($response, $result, 200, 'Translation applied as draft');
        } catch (TranslationException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function discard(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $actor = $this->actor($request);
            $this->translations->discard((string) ($args['jobId'] ?? ''), $actor['id']);

            return $this->json->success($response, ['discarded' => true]);
        } catch (TranslationException $e) {
            return $this->json->error($response, $e->getMessage(), $e->httpStatus, ['code' => $e->errorCode]);
        }
    }

    /**
     * @return array{id: string, email: string|null}
     */
    private function actor(ServerRequestInterface $request): array
    {
        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            return ['id' => $user->getId(), 'email' => $user->getEmail()];
        }

        throw new TranslationException('Authentication required', 401, 'UNAUTHENTICATED');
    }
}
