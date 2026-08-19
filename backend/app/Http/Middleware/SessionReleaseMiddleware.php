<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Services\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Releases the PHP session write lock early on read-only HTTP methods.
 *
 * Without this, parallel SPA XHR (dashboard Promise.all) serialize on the
 * same session file and wall-clock latency grows linearly with request count.
 */
final class SessionReleaseMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const READ_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private SessionManager $session
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isReadMethod($request->getMethod())) {
            $this->session->releaseWriteLock();
        }

        return $handler->handle($request);
    }

    private function isReadMethod(string $method): bool
    {
        return in_array(strtoupper($method), self::READ_METHODS, true);
    }
}
