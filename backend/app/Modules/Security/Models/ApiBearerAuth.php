<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Models;

/**
 * Unified scoped bearer principal for API keys and short-lived JWTs (It.74).
 */
final class ApiBearerAuth
{
    public const KIND_KEY = 'key';
    public const KIND_JWT = 'jwt';

    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly string $id,
        public readonly string $kind,
        public readonly array $scopes,
        public readonly ?string $label = null,
    ) {
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
