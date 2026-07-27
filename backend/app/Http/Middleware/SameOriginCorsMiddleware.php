<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuupola\Middleware\CorsMiddleware;

/**
 * CORS wrapper for same-origin SPA deployments (nginx static + /api proxy).
 *
 * Browsers send an Origin header on fetch/XHR even for same-site requests.
 * Tuupola CorsMiddleware rejects unknown origins with HTTP 401 and an empty
 * text/html body — which breaks login in the admin when APP_URL in .env does
 * not exactly match the public domain (common on demo subdomains).
 *
 * When Origin matches Host (+ X-Forwarded-Proto), the origin is injected into
 * the allow-list for that request. CORS failures also return JSON for easier
 * debugging in DevTools.
 */
final class SameOriginCorsMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, mixed> $corsOptions Tuupola CorsMiddleware options
     */
    public function __construct(
        private array $corsOptions
    ) {
        if (!isset($this->corsOptions['error'])) {
            $this->corsOptions['error'] = function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                ?array $arguments = null
            ): ResponseInterface {
                $message = is_array($arguments)
                    ? (string) ($arguments['message'] ?? 'CORS request rejected')
                    : 'CORS request rejected';

                $response->getBody()->write(JsonHelper::encode([
                    'success' => false,
                    'error' => $message,
                    'code' => 'cors_rejected',
                ]));

                return $response
                    ->withHeader('Content-Type', 'application/json; charset=utf-8');
            };
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $options = $this->corsOptions;
        $requestOrigin = rtrim($request->getHeaderLine('Origin'), '/');

        if ($requestOrigin !== '') {
            $expectedOrigin = $this->resolveExpectedOrigin($request);
            if ($expectedOrigin !== null && $requestOrigin === $expectedOrigin) {
                /** @var list<string> $allowed */
                $allowed = (array) ($options['origin'] ?? []);
                $allowed[] = $requestOrigin;
                $options['origin'] = array_values(array_unique($allowed));
            }
        }

        return (new CorsMiddleware($options))->process($request, $handler);
    }

    private function resolveExpectedOrigin(ServerRequestInterface $request): ?string
    {
        $host = $request->getHeaderLine('Host');
        if ($host === '') {
            return null;
        }

        $hostName = $host;
        $port = null;

        if (!str_starts_with($host, '[') && str_contains($host, ':')) {
            [$hostName, $portPart] = explode(':', $host, 2);
            if (ctype_digit($portPart)) {
                $port = (int) $portPart;
            } else {
                $hostName = $host;
                $port = null;
            }
        }

        $forwardedParts = explode(',', $request->getHeaderLine('X-Forwarded-Proto'));
        $forwardedProto = trim($forwardedParts[0]);
        $scheme = $forwardedProto !== '' ? $forwardedProto : $request->getUri()->getScheme();
        if ($scheme === '') {
            $scheme = 'https';
        }

        if ($port !== null && !in_array($port, [80, 443], true)) {
            return sprintf('%s://%s:%d', $scheme, $hostName, $port);
        }

        return sprintf('%s://%s', $scheme, $hostName);
    }
}
