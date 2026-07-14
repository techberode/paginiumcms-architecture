<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PaginiumCMS\Modules\Security\Exception\SecurityException;

/**
 * Implementácia ochrany proti CSRF útokom.
 */
class CsrfProtectionManager implements CsrfProtectionInterface
{
    private SessionManager $session;
    private const TOKEN_PREFIX = 'csrf_token_';
    private const TOKEN_LENGTH = 32;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function generateToken(string $key): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $this->session->set(self::TOKEN_PREFIX . $key, $token);
        return $token;
    }

    public function verifyToken(string $key, string $token): bool
    {
        $storedToken = $this->session->get(self::TOKEN_PREFIX . $key);
        
        if ($storedToken === null) {
            return false;
        }

        return hash_equals($storedToken, $token);
    }

    public function requireValidToken(string $key, string $token): void
    {
        if (!$this->verifyToken($key, $token)) {
            throw new SecurityException('Neplatný CSRF token');
        }
        
        // Token je jednorazový – po overení ho vymažeme
        $this->clearToken($key);
    }

    public function getToken(string $key): string
    {
        $token = $this->session->get(self::TOKEN_PREFIX . $key);
        
        if ($token === null) {
            $token = $this->generateToken($key);
        }
        
        return $token;
    }

    public function clearToken(string $key): void
    {
        $this->session->remove(self::TOKEN_PREFIX . $key);
    }
}
