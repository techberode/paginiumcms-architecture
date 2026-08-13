<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dedikovaný rate limiter pre OTP workflow (audit S10 / ISS-058).
 *
 * Oddelené limity podľa akcie:
 * - start  — registrácia / odoslanie prvej výzvy (email + IP)
 * - verify — overenie kódu (challenge_id + IP)
 * - resend — opätovné odoslanie kódu (challenge_id + IP, prísnejší limit)
 */
class OtpRateLimitMiddleware extends RateLimitMiddleware
{
    public const ACTION_VERIFY = 'verify';
    public const ACTION_RESEND = 'resend';
    public const ACTION_START = 'start';

    private string $action;

    /**
     * @param array<int|string, mixed> $trustedProxies
     */
    public function __construct(CacheManager $cache, string $action, array $trustedProxies = [])
    {
        $this->action = $action;

        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        $isTesting = $appEnv === 'testing';
        $isDevelopment = $appEnv === 'development' || $appEnv === 'local';

        [$maxRequests, $window] = match ($action) {
            self::ACTION_VERIFY => $isTesting
                ? [100000, 60]
                : ($isDevelopment ? [30, 900] : [10, 900]),
            self::ACTION_RESEND => $isTesting
                ? [100000, 60]
                : ($isDevelopment ? [10, 900] : [3, 3600]),
            self::ACTION_START => $isTesting
                ? [100000, 60]
                : ($isDevelopment ? [10, 900] : [5, 3600]),
            default => [5, 3600],
        };

        parent::__construct(
            $cache,
            maxRequests: $maxRequests,
            window: $window,
            excludedPaths: [],
            excludedIps: $isTesting ? ['127.0.0.1', '::1'] : [],
            trustedProxies: $trustedProxies
        );
    }

    protected function getCacheKey(ServerRequestInterface $request): string
    {
        /** @var array<string, mixed> $data */
        $data = RequestJsonBody::decode($request) ?? [];
        $ip = md5($this->getClientIp($request));

        if ($this->action === self::ACTION_START) {
            $email = strtolower(trim((string) ($data['email'] ?? 'unknown')));

            return sprintf('rate_limit_otp:start:%s:%s', md5($email), $ip);
        }

        $challengeId = (string) ($data['challenge_id'] ?? 'unknown');

        return sprintf('rate_limit_otp:%s:%s:%s', $this->action, md5($challengeId), $ip);
    }
}
