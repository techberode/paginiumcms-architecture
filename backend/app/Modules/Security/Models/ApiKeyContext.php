<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Models;

/**
 * Authenticated API key context attached to headless requests (It.74).
 */
final class ApiKeyContext
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly array $scopes,
    ) {
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
