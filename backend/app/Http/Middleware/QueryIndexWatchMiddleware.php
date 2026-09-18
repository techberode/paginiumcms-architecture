<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexFailureHandler;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexRuntimeWatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Lightweight runtime watch when SQLite query index is configured (It.92).
 */
final class QueryIndexWatchMiddleware implements MiddlewareInterface
{
    public function __construct(
        private QueryIndexRuntimeWatch $watch,
        private QueryIndexFailureHandler $failureHandler
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->watch->isWatchActive()) {
            $this->failureHandler->handleDetectedIssue();
        }

        return $handler->handle($request);
    }
}
