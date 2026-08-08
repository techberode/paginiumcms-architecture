<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Models\ApiBearerAuth;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Rate limit headless API traffic by bearer principal ID instead of client IP (It.74).
 */
final class ApiKeyRateLimitMiddleware extends RateLimitMiddleware
{
    protected function getCacheKey(ServerRequestInterface $request): string
    {
        $context = $request->getAttribute('api_bearer');
        if ($context instanceof ApiBearerAuth) {
            $path = $request->getUri()->getPath();
            $method = $request->getMethod();

            return sprintf('rate_limit_api_bearer:%s:%s:%s', $context->id, md5($path), $method);
        }

        return parent::getCacheKey($request);
    }
}
