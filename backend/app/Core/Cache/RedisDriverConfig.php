<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

/**
 * Non-secret Redis connection parameters (Iteration 69 Redis driver).
 */
final class RedisDriverConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $password,
        public readonly int $database,
        public readonly string $keyPrefix,
        public readonly float $connectTimeoutSeconds = 1.5,
    ) {
    }

    /**
     * @param array<string, mixed> $engineGroup
     */
    public static function fromEngineAndEnv(array $engineGroup): ?self
    {
        $host = trim((string) (getenv('REDIS_HOST') ?: ($engineGroup['redisHost'] ?? '')));
        if ($host === '') {
            return null;
        }

        $port = (int) (getenv('REDIS_PORT') ?: ($engineGroup['redisPort'] ?? 6379));
        if ($port < 1 || $port > 65535) {
            $port = 6379;
        }

        $password = (string) (getenv('REDIS_PASSWORD') ?: ($engineGroup['redisPassword'] ?? ''));
        $database = (int) ($engineGroup['redisDatabase'] ?? 0);
        if ($database < 0) {
            $database = 0;
        }
        if ($database > 15) {
            $database = 15;
        }

        $prefix = trim((string) ($engineGroup['redisKeyPrefix'] ?? 'paginium:'));
        if ($prefix === '') {
            $prefix = 'paginium:';
        }

        return new self($host, $port, $password, $database, $prefix);
    }
}
