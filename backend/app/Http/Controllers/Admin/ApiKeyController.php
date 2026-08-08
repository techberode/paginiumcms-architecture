<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\ApiJwtService;
use PaginiumCMS\Modules\Security\Services\ApiKeyStore;
use PaginiumCMS\Modules\Security\Services\ApiKeyVerifier;
use PaginiumCMS\Modules\Security\Services\SecurityAuditStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin lifecycle for scoped API keys (It.74).
 */
final class ApiKeyController
{
    /** @var list<string> */
    private const AUDIT_TYPES = [
        'api_key_created',
        'api_key_revoked',
        'api_key_rotated',
        'api_jwt_issued',
    ];

    public function __construct(
        private ApiKeyStore $store,
        private ApiKeyVerifier $verifier,
        private ApiJwtService $jwtService,
        private SecurityAuditStore $audit,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'keys' => $this->store->listMetadata(),
            'availableScopes' => ApiKeyStore::ALL_SCOPES,
            'scopeGroups' => [
                'read' => ApiKeyStore::READ_SCOPES,
                'write' => ApiKeyStore::WRITE_SCOPES,
                'token' => ApiKeyStore::TOKEN_SCOPES,
            ],
        ]);
    }

    public function audit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $events = [];
        foreach (self::AUDIT_TYPES as $type) {
            foreach ($this->audit->list(['type' => $type], 50) as $event) {
                $events[] = $event;
            }
        }

        usort($events, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));
        $events = array_slice($events, 0, 100);

        return $this->json->success($response, [
            'events' => $events,
            'types' => self::AUDIT_TYPES,
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->verifier->isConfigured()) {
            return $this->json->error($response, 'API_KEY_PEPPER is not configured on this instance', 503);
        }

        $body = $this->parseBody($request);
        $label = is_string($body['label'] ?? null) ? trim($body['label']) : '';
        $scopes = is_array($body['scopes'] ?? null) ? $body['scopes'] : [];
        $expiresAt = is_string($body['expiresAt'] ?? null) && trim($body['expiresAt']) !== ''
            ? trim($body['expiresAt'])
            : null;

        if ($label === '') {
            return $this->json->validation($response, 'Validation failed', ['label' => 'Label is required']);
        }

        /** @var list<string> $scopeList */
        $scopeList = array_values(array_filter($scopes, static fn ($scope): bool => is_string($scope)));
        if ($scopeList === []) {
            return $this->json->validation($response, 'Validation failed', ['scopes' => 'Select at least one scope']);
        }

        $creator = $this->creatorId($request);

        try {
            $created = $this->store->create($label, $scopeList, $expiresAt, $creator, $this->verifier);
        } catch (\InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['scopes' => $exception->getMessage()]);
        }

        $record = $created['record'];
        $user = $request->getAttribute('user');
        $this->audit->append(
            'api_key_created',
            'INFO',
            'API key created',
            $user instanceof User ? (string) $user->getId() : $creator,
            $user instanceof User ? $user->getEmail() : null,
            null,
            [
                'keyId' => $record['id'],
                'idPrefix' => $record['idPrefix'],
                'label' => $record['label'],
                'scopes' => $record['scopes'],
            ]
        );

        return $this->json->success($response, [
            'key' => [
                'id' => $record['id'],
                'idPrefix' => $record['idPrefix'],
                'label' => $record['label'],
                'scopes' => $record['scopes'],
                'status' => 'active',
                'createdAt' => $record['createdAt'],
                'expiresAt' => $record['expiresAt'],
                'createdBy' => $record['createdBy'],
            ],
            'token' => $created['token'],
            'copyOnce' => true,
        ], 201);
    }

    public function issueToken(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->jwtService->isConfigured()) {
            return $this->json->error($response, 'API_JWT_KEY is not configured on this instance', 503);
        }

        $body = $this->parseBody($request);
        $requestedScopes = is_array($body['scopes'] ?? null) ? $body['scopes'] : [];
        /** @var list<string> $scopeList */
        $scopeList = array_values(array_filter($requestedScopes, static fn ($scope): bool => is_string($scope) && $scope !== ''));

        if ($scopeList === []) {
            return $this->json->validation($response, 'Validation failed', ['scopes' => 'Select at least one scope']);
        }

        foreach ($scopeList as $scope) {
            if (!in_array($scope, ApiKeyStore::ALL_SCOPES, true)) {
                return $this->json->validation($response, 'Validation failed', ['scopes' => 'Unknown scope: ' . $scope]);
            }
        }

        $ttl = is_numeric($body['ttl'] ?? null) ? (int) $body['ttl'] : ApiJwtService::MAX_TTL_SECONDS;
        $ttl = max(60, min($ttl, ApiJwtService::MAX_TTL_SECONDS));
        $user = $request->getAttribute('user');
        $subject = $user instanceof User ? 'user:' . $user->getId() : 'admin';

        try {
            $token = $this->jwtService->issue($scopeList, $subject, $ttl);
        } catch (\InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['scopes' => $exception->getMessage()]);
        }

        $this->audit->append(
            'api_jwt_issued',
            'INFO',
            'Short-lived JWT issued from admin session',
            $user instanceof User ? (string) $user->getId() : null,
            $user instanceof User ? $user->getEmail() : null,
            null,
            [
                'scopes' => $scopeList,
                'ttl' => $ttl,
                'subject' => $subject,
            ]
        );

        return $this->json->success($response, [
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => $ttl,
            'scopes' => $scopeList,
        ], 201);
    }

    /** @param array<string, string> $args */
    public function rotate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (!$this->verifier->isConfigured()) {
            return $this->json->error($response, 'API_KEY_PEPPER is not configured on this instance', 503);
        }

        $id = $args['id'] ?? '';
        if ($id === '' || !preg_match('/^[a-f0-9]{16}$/', $id)) {
            return $this->json->error($response, 'Invalid API key id', 400);
        }

        $creator = $this->creatorId($request);
        $rotated = $this->store->rotate($id, $creator, $this->verifier);
        if ($rotated === null) {
            return $this->json->error($response, 'API key not found or already revoked', 404);
        }

        $record = $rotated['record'];
        $user = $request->getAttribute('user');
        $this->audit->append(
            'api_key_rotated',
            'WARNING',
            'API key rotated',
            $user instanceof User ? (string) $user->getId() : $creator,
            $user instanceof User ? $user->getEmail() : null,
            null,
            [
                'previousKeyId' => $rotated['previousId'],
                'newKeyId' => $record['id'],
                'idPrefix' => $record['idPrefix'],
                'label' => $record['label'],
            ]
        );

        return $this->json->success($response, [
            'key' => [
                'id' => $record['id'],
                'idPrefix' => $record['idPrefix'],
                'label' => $record['label'],
                'scopes' => $record['scopes'],
                'status' => 'active',
                'createdAt' => $record['createdAt'],
                'expiresAt' => $record['expiresAt'],
                'createdBy' => $record['createdBy'],
            ],
            'token' => $rotated['token'],
            'copyOnce' => true,
            'previousId' => $rotated['previousId'],
        ]);
    }

    /** @param array<string, string> $args */
    public function revoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? '';
        if ($id === '' || !preg_match('/^[a-f0-9]{16}$/', $id)) {
            return $this->json->error($response, 'Invalid API key id', 400);
        }

        if (!$this->store->revoke($id)) {
            return $this->json->error($response, 'API key not found', 404);
        }

        $user = $request->getAttribute('user');
        $this->audit->append(
            'api_key_revoked',
            'WARNING',
            'API key revoked',
            $user instanceof User ? (string) $user->getId() : $this->creatorId($request),
            $user instanceof User ? $user->getEmail() : null,
            null,
            ['keyId' => $id]
        );

        return $this->json->success($response, ['revoked' => true, 'id' => $id]);
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

    private function creatorId(ServerRequestInterface $request): string
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? (string) $user->getId() : 'system';
    }
}
