<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Security\Firewall\FirewallService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Early-exit WAF middleware (Iteration 50) — runs before rate limiting.
 */
final class FirewallMiddleware implements MiddlewareInterface
{
    /** @var array<int|string, mixed> */
    private array $trustedProxies;

    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(
        private FirewallService $firewall,
        array $trustedProxies = []
    ) {
        $this->trustedProxies = $trustedProxies;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isTestingEnvironment() || !$this->firewall->isEnabled()) {
            return $handler->handle($request);
        }

        $ip = $this->resolveClientIp($request);

        if ($this->firewall->isBanned($ip)) {
            return $this->createJailResponse();
        }

        $uriPath = $request->getUri()->getPath();
        $queryString = $request->getUri()->getQuery();
        $userAgent = $request->getHeaderLine('User-Agent');

        $match = $this->firewall->inspectRequest($ip, $uriPath, $queryString, $userAgent);
        if ($match !== null) {
            return $this->createJailResponse();
        }

        return $handler->handle($request);
    }

    private function createJailResponse(): ResponseInterface
    {
        $settings = $this->firewall->jailSettings();
        $mode = $settings['jailMode'];
        $tarpitSeconds = (int) $settings['tarpitSeconds'];

        if ($mode === 'tarpit' && $tarpitSeconds > 0) {
            sleep($tarpitSeconds);
        }

        $response = new Response(403);

        if ($mode === 'empty' || $mode === 'tarpit') {
            return $response->withHeader('Content-Type', 'text/plain');
        }

        $body = fopen('php://temp', 'r+');
        if ($body === false) {
            return $response->withHeader('Content-Type', 'text/plain; charset=UTF-8');
        }

        $stream = new \Slim\Psr7\Stream($body);
        $stream->write('Access denied');
        $stream->rewind();

        return $response
            ->withBody($stream)
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function resolveClientIp(ServerRequestInterface $request): string
    {
        $remoteAddr = $request->getServerParams()['REMOTE_ADDR'] ?? '127.0.0.1';
        $remoteAddr = is_string($remoteAddr) ? $remoteAddr : '127.0.0.1';

        if ($this->trustedProxies === [] || !in_array($remoteAddr, $this->trustedProxies, true)) {
            return $remoteAddr;
        }

        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded === '') {
            return $remoteAddr;
        }

        $parts = array_map('trim', explode(',', $forwarded));
        $clientIp = $parts[0];

        return filter_var($clientIp, FILTER_VALIDATE_IP) ? $clientIp : $remoteAddr;
    }

    private function isTestingEnvironment(): bool
    {
        return getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing';
    }
}
