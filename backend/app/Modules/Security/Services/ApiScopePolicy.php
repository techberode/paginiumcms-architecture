<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

/**
 * Explicit route+method allow-list for API key scopes (It.74 Phase 74a).
 */
final class ApiScopePolicy
{
    /**
     * @var array<string, list<string>>
     */
    private const ROUTE_SCOPES = [
        'GET /api/headless/pages' => ['content:read'],
        'GET /api/headless/pages/{slug}' => ['content:read'],
        'GET /api/headless/articles' => ['content:read'],
        'GET /api/headless/articles/{slug}' => ['content:read'],
        'GET /api/headless/settings/public' => ['settings:read'],
        'POST /api/headless/pages' => ['content:write'],
        'PUT /api/headless/pages/{slug}' => ['content:write'],
        'POST /api/headless/articles' => ['content:write'],
        'PUT /api/headless/articles/{slug}' => ['content:write'],
        'POST /api/headless/token' => ['token:issue'],
    ];

    /**
     * @param list<string> $keyScopes
     */
    public function allows(string $method, string $path, array $keyScopes): bool
    {
        $required = $this->requiredScopes($method, $path);
        if ($required === null) {
            return false;
        }

        foreach ($required as $scope) {
            if (!in_array($scope, $keyScopes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>|null
     */
    public function requiredScopes(string $method, string $path): ?array
    {
        $method = strtoupper($method);
        $normalizedPath = '/' . ltrim($path, '/');
        $key = $method . ' ' . $normalizedPath;

        if (isset(self::ROUTE_SCOPES[$key])) {
            return self::ROUTE_SCOPES[$key];
        }

        foreach (self::ROUTE_SCOPES as $pattern => $scopes) {
            if ($this->matchesPattern($pattern, $key)) {
                return $scopes;
            }
        }

        return null;
    }

    private function matchesPattern(string $pattern, string $actual): bool
    {
        [$patternMethod, $patternPath] = explode(' ', $pattern, 2);
        [$actualMethod, $actualPath] = explode(' ', $actual, 2);

        if ($patternMethod !== $actualMethod) {
            return false;
        }

        $parts = preg_split('#(\{[^/]+\})#', $patternPath, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $regex = '#^';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('#^\{[^/]+\}$#', $part) === 1) {
                $regex .= '[^/]+';
            } else {
                $regex .= preg_quote($part, '#');
            }
        }
        $regex .= '$#';

        return (bool) preg_match($regex, $actualPath);
    }
}
