<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use PaginiumCMS\Support\JsonHelper;

/**
 * backend/app/Http/Middleware/RateLimitMiddleware.php
 *
 * OPRAVA (audit 12.7.2026, nález #7):
 * Pôvodná verzia brala IP výhradne z `REMOTE_ADDR` a mala natvrdo
 * `excludedIps: ['127.0.0.1', '::1']`. Ak appka beží za reverse proxy
 * (bežné nasadenie - nginx + PHP-FPM na tom istom stroji/kontajneri),
 * `REMOTE_ADDR` je IP PROXY (často 127.0.0.1), takže:
 *   a) všetci reální používatelia zdieľajú jeden rate-limit kľúč, ALEBO
 *   b) keďže 127.0.0.1 bolo v excludedIps, rate limiting sa nechtiac
 *      úplne vypol pre úplne všetkých.
 *
 * Táto verzia pridáva `trustedProxies`: `X-Forwarded-For` sa použije
 * IBA ak požiadavka prišla z IP uvedenej v `trustedProxies` (t.j. od
 * vášho vlastného nginx) - inak by si ktokoľvek mohol hlavičku
 * `X-Forwarded-For` jednoducho vymyslieť a obísť rate limit úplne.
 * Ak `trustedProxies` je prázdne pole, správanie je bezpečný fallback:
 * vždy sa použije `REMOTE_ADDR` a `X-Forwarded-For` sa úplne ignoruje.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private CacheManager $cache;
    private int $maxRequests;
    private int $window;
    /** @var array<int|string, mixed> */
    private array $excludedPaths = [];
    /** @var array<int|string, mixed> */
    private array $excludedIps = [];
    /** @var array<int|string, mixed> */
    private array $trustedProxies = [];

    /**
     * @param array<int|string, mixed> $excludedPaths
     * @param array<int|string, mixed> $excludedIps
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(
        CacheManager $cache,
        int $maxRequests = 60,
        int $window = 60,
        array $excludedPaths = [],
        array $excludedIps = [],
        array $trustedProxies = []
    ) {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->window = $window;
        $this->excludedPaths = $excludedPaths;
        $this->excludedIps = $excludedIps;
        $this->trustedProxies = $trustedProxies;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if ($this->isTestingEnvironment()) {
            return $handler->handle($request);
        }

        if ($this->isExcluded($request)) {
            return $handler->handle($request);
        }

        $key = $this->getCacheKey($request);
        $current = $this->cache->get($key, 0);

        if ($current >= $this->maxRequests) {
            return $this->createRateLimitResponse($request);
        }

        $this->cache->set($key, $current + 1, $this->window);

        $response = $handler->handle($request);

        return $response
        ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
        ->withHeader('X-RateLimit-Remaining', (string) ($this->maxRequests - $current - 1))
        ->withHeader('X-RateLimit-Reset', (string) (time() + $this->window));
    }

    /**
     * Zisťuje skutočnú klientsku IP. `X-Forwarded-For` sa berie do úvahy
     * IBA ak priame spojenie (REMOTE_ADDR) prichádza od dôveryhodného
     * proxy - inak je táto hlavička ľubovoľne sfalšovateľná útočníkom a
     * použitie by rate limiting úplne znefunkčnilo.
     */
    protected function getClientIp(ServerRequestInterface $request): string
    {
        $remoteAddr = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        if (!in_array($remoteAddr, $this->trustedProxies, true)) {
            return $remoteAddr;
        }

        $forwardedFor = $request->getHeaderLine('X-Forwarded-For');
        if ($forwardedFor === '') {
            return $remoteAddr;
        }

        // Prvá IP v zozname je pôvodný klient (nginx pridáva ďalšie za ňu)
        $parts = array_map('trim', explode(',', $forwardedFor));
        $clientIp = $parts[0];

        return filter_var($clientIp, FILTER_VALIDATE_IP) ? $clientIp : $remoteAddr;
    }

    protected function getCacheKey(ServerRequestInterface $request): string
    {
        $ip = $this->getClientIp($request);
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        return sprintf('rate_limit:%s:%s:%s', md5($ip), md5($path), $method);
    }

    private function isExcluded(ServerRequestInterface $request): bool
    {
        $ip = $this->getClientIp($request);
        $path = $request->getUri()->getPath();

        if (in_array($ip, $this->excludedIps, true)) {
            return true;
        }

        foreach ($this->excludedPaths as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return true;
            }
        }

        return false;
    }

    private function createRateLimitResponse(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => 'Príliš veľa požiadaviek. Skúste to neskôr.',
            'retry_after' => $this->window,
        ], JSON_PRETTY_PRINT));

        return $response
        ->withStatus(429)
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Retry-After', (string) $this->window)
        ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
        ->withHeader('X-RateLimit-Remaining', '0')
        ->withHeader('X-RateLimit-Reset', (string) (time() + $this->window));
    }

    protected function isTestingEnvironment(): bool
    {
        return (getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '')) === 'testing';
    }
}

/**
 * Špecializovaný rate limiter pre login.
 */
final class LoginRateLimitMiddleware extends RateLimitMiddleware
{
    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, array $trustedProxies = [])
    {
        $isTesting = (getenv('APP_ENV') === 'testing');
        parent::__construct(
            $cache,
            maxRequests: $isTesting ? 100000 : 5,
            window: $isTesting ? 60 : 300,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            trustedProxies: $trustedProxies
        );
    }

    protected function getCacheKey(ServerRequestInterface $request): string
    {
        $data = json_decode((string)$request->getBody(), true);
        $email = $data['email'] ?? 'unknown';
        $ip = $this->getClientIp($request);

        return sprintf('rate_limit_login:%s:%s', md5($email), md5($ip));
    }
}
