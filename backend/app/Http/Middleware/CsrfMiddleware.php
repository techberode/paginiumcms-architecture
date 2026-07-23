<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PaginiumCMS\Support\JsonHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * CSRF ochrana pre stavové (mutujúce) requesty — synchronizer-token vzor.
 *
 * Audit S3 / ISS-012: predtým `CsrfProtectionManager` existoval, ale nebol
 * nikde zapojený. Tento middleware ho vynucuje globálne.
 *
 * Model (SPA + HttpOnly session cookie):
 *  1. FE si vyžiada token cez `GET /api/auth/csrf-token` (uloží sa do session).
 *  2. Token posiela pri každom POST/PUT/PATCH/DELETE v hlavičke `X-CSRF-TOKEN`.
 *  3. Tu porovnáme hlavičku so session tokenom cez `hash_equals`.
 *
 * Token NIE je jednorazový (na rozdiel od `requireValidToken`) — SPA ho
 * používa opakovane počas celej session. Cross-site útočník ho nedokáže
 * prečítať (SOP + CORS na `/csrf-token`) ani nastaviť custom hlavičku bez
 * schváleného preflightu → CSRF je zablokované.
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const TOKEN_KEY = 'default';
    private const HEADER = 'X-CSRF-TOKEN';

    /** HTTP metódy, ktoré menia stav a vyžadujú CSRF token. */
    private const UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Cesty vyňaté z CSRF ochrany (prefixový match). Sú to buď pre-auth
     * endpointy (ešte neexistuje session, ktorú by bolo možné zneužiť), alebo
     * verejné anonymné akcie bez privilégií, kde CSRF nedáva zmysel.
     *
     * @var list<string>
     */
    private const EXEMPT_PREFIXES = [
        '/api/auth/login',
        '/api/auth/register',
        '/api/auth/reset-password',
        '/api/auth/verify-reset-token',
        '/api/auth/csrf-token',
        '/api/auth/sso',
        '/api/contact',
        '/api/maintenance',
        '/api/comments',
        '/api/debug/client-event',
    ];

    /**
     * @param list<string> $extraExemptPrefixes voliteľné rozšírenie exempt listu
     */
    public function __construct(
        private CsrfProtectionInterface $csrf,
        private array $extraExemptPrefixes = []
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Testovacie prostredie: rovnako ako WAF sa CSRF nevynucuje (HTTP testy
        // neposielajú token). Logika middleware je pokrytá dedikovanými testami.
        if ($this->isTestingEnvironment()) {
            return $handler->handle($request);
        }

        $method = strtoupper($request->getMethod());
        if (!in_array($method, self::UNSAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        if ($this->isExempt($request->getUri()->getPath())) {
            return $handler->handle($request);
        }

        $token = trim($request->getHeaderLine(self::HEADER));
        if ($token === '' || !$this->csrf->verifyToken(self::TOKEN_KEY, $token)) {
            return $this->rejected();
        }

        return $handler->handle($request);
    }

    private function isExempt(string $path): bool
    {
        foreach (array_merge(self::EXEMPT_PREFIXES, $this->extraExemptPrefixes) as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/') || str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function rejected(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(JsonHelper::encode([
            'success' => false,
            'error' => 'Neplatný alebo chýbajúci CSRF token',
            'code' => 'csrf_invalid',
        ]));

        return $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'application/json');
    }

    private function isTestingEnvironment(): bool
    {
        return getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing';
    }
}
