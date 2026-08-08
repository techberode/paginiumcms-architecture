<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Models\ApiBearerAuth;

/**
 * Resolves Bearer credentials to a scoped principal — API key or JWT (It.74).
 */
final class ApiBearerAuthenticator
{
    public function __construct(
        private ApiKeyVerifier $keyVerifier,
        private ApiJwtService $jwtService,
    ) {
    }

    public function looksLikeManagedBearer(string $authorizationHeader): bool
    {
        $token = $this->extractBearerToken($authorizationHeader);
        if ($token === null) {
            return false;
        }

        // Any pgk_* attempt is managed — invalid format must not fall back to session (It.74).
        return $this->keyVerifier->looksLikeApiKey($authorizationHeader)
            || $this->jwtService->looksLikeJwt($token);
    }

    public function resolve(string $authorizationHeader): ?ApiBearerAuth
    {
        $token = $this->extractBearerToken($authorizationHeader);
        if ($token === null) {
            return null;
        }

        if ($this->keyVerifier->parseToken($token) !== null) {
            $context = $this->keyVerifier->verifyBearer('Bearer ' . $token);
            if ($context === null) {
                return null;
            }

            return new ApiBearerAuth(
                $context->id,
                ApiBearerAuth::KIND_KEY,
                $context->scopes,
                $context->label,
            );
        }

        if (!$this->jwtService->looksLikeJwt($token)) {
            return null;
        }

        $claims = $this->jwtService->verify($token);
        if ($claims === null) {
            return null;
        }

        /** @var list<string> $scopes */
        $scopes = is_array($claims['scope_list'] ?? null) ? $claims['scope_list'] : [];

        return new ApiBearerAuth(
            (string) ($claims['jti'] ?? ''),
            ApiBearerAuth::KIND_JWT,
            $scopes,
            (string) ($claims['sub'] ?? ''),
        );
    }

    private function extractBearerToken(string $authorizationHeader): ?string
    {
        $authorizationHeader = trim($authorizationHeader);
        if ($authorizationHeader === '') {
            return null;
        }

        if (!preg_match('/^Bearer\s+(\S+)/i', $authorizationHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
