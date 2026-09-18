<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Health\Services;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Binds the current HTTP request server params for health checks (HTTPS behind proxy).
 */
final class HealthRuntimeContext
{
    /** @var array<string, mixed>|null */
    private static ?array $serverParams = null;

    public static function bindFromRequest(ServerRequestInterface $request): void
    {
        $server = $request->getServerParams();
        foreach ($request->getHeaders() as $name => $values) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $server[$key] = implode(', ', $values);
        }

        self::$serverParams = $server;
    }

    public static function clear(): void
    {
        self::$serverParams = null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function serverParams(): array
    {
        if (self::$serverParams !== null) {
            return self::$serverParams;
        }

        return $_SERVER;
    }
}
