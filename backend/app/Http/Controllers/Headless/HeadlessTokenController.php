<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Headless;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\ApiBearerAuth;
use PaginiumCMS\Modules\Security\Services\ApiJwtService;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Issues short-lived JWTs for headless clients with `token:issue` scope (It.74 Phase 74b).
 */
final class HeadlessTokenController
{
    public function __construct(
        private ApiJwtService $jwtService,
        private SecurityAuditStore $audit,
        private JsonResponder $json,
    ) {
    }

    public function issue(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->jwtService->isConfigured()) {
            return $this->json->error($response, 'API_JWT_KEY is not configured on this instance', 503);
        }

        $bearer = $request->getAttribute('api_bearer');
        if (!$bearer instanceof ApiBearerAuth || $bearer->kind !== ApiBearerAuth::KIND_KEY) {
            return $this->json->error($response, 'API key context required', 403);
        }

        $body = $this->parseBody($request);
        $requestedScopes = is_array($body['scopes'] ?? null) ? $body['scopes'] : [];
        /** @var list<string> $scopeList */
        $scopeList = array_values(array_filter($requestedScopes, static fn ($scope): bool => is_string($scope) && $scope !== ''));

        if ($scopeList === []) {
            return $this->json->validation($response, 'Validation failed', ['scopes' => 'Select at least one scope']);
        }

        foreach ($scopeList as $scope) {
            if (!in_array($scope, $bearer->scopes, true)) {
                return $this->json->error($response, 'Requested scope exceeds API key allowance', 403);
            }
        }

        $ttl = is_numeric($body['ttl'] ?? null) ? (int) $body['ttl'] : ApiJwtService::MAX_TTL_SECONDS;
        $ttl = max(60, min($ttl, ApiJwtService::MAX_TTL_SECONDS));

        try {
            $token = $this->jwtService->issue($scopeList, 'api-key:' . $bearer->id, $ttl, $bearer->id);
        } catch (\InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['scopes' => $exception->getMessage()]);
        }

        $this->audit->append(
            'api_jwt_issued',
            'INFO',
            'Short-lived JWT issued via headless API key',
            $bearer->id,
            null,
            null,
            [
                'keyId' => $bearer->id,
                'scopes' => $scopeList,
                'ttl' => $ttl,
            ]
        );

        return $this->json->success($response, [
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => $ttl,
            'scopes' => $scopeList,
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseBody(ServerRequestInterface $request): array
    {
        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
