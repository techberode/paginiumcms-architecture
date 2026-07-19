<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\SessionManager;

/**
 * Session wrapper with optional hijack checks (IP / User-Agent) and absolute max age.
 *
 * IP/UA binding is OFF by default (`SESSION_STRICT` must be explicitly true).
 * This avoids LAN/nginx proxy flip-flops that destroyed sessions mid-save.
 */
final class SecureSessionManager extends SessionManager
{
    private const IP_KEY = '_session_ip';
    private const UA_KEY = '_session_user_agent';
    private const CREATED_KEY = '_session_created';
    private const LAST_ACTIVITY_KEY = '_session_last_activity';

    /** @var list<string> */
    private array $trustedProxies;

    private bool $strictBinding;

    private bool $validated = false;

    public function __construct()
    {
        parent::__construct();

        $this->trustedProxies = ClientIpResolver::trustedProxiesFromEnv();
        $this->strictBinding = self::isStrictBindingEnabled();
    }

    public function ensureValid(): bool
    {
        if ($this->validated) {
            return $this->isAuthenticated();
        }

        $this->validated = true;

        if (!$this->isAuthenticated()) {
            return false;
        }

        return $this->validateSession();
    }

    public function setUser(User $user): void
    {
        parent::setUser($user);

        $_SESSION[self::IP_KEY] = ClientIpResolver::resolve(null, $this->trustedProxies);
        $_SESSION[self::UA_KEY] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $_SESSION[self::CREATED_KEY] = time();
        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
        $this->validated = true;
    }

    /**
     * Keeps server-side session file fresh during long edits (extends gc_maxlifetime window).
     */
    public function touch(): void
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $_SESSION[self::LAST_ACTIVITY_KEY] = time();
    }

    private function validateSession(): bool
    {
        if ($this->strictBinding) {
            $currentIp = ClientIpResolver::resolve(null, $this->trustedProxies);
            $storedIp = $_SESSION[self::IP_KEY] ?? null;

            if ($storedIp === null) {
                $_SESSION[self::IP_KEY] = $currentIp;
            } elseif ($storedIp !== $currentIp) {
                $this->forceDestroy();

                return false;
            }

            $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $storedUa = $_SESSION[self::UA_KEY] ?? null;

            if ($storedUa === null) {
                $_SESSION[self::UA_KEY] = $currentUa;
            } elseif ($storedUa !== $currentUa) {
                $this->forceDestroy();

                return false;
            }
        }

        $created = $_SESSION[self::CREATED_KEY] ?? null;
        if ($created === null) {
            $_SESSION[self::CREATED_KEY] = time();
        }

        $maxAbsolute = max(3600, (int) (getenv('SESSION_ABSOLUTE_MAX') ?: ($_ENV['SESSION_ABSOLUTE_MAX'] ?? 86400)));
        if ($created !== null && (time() - (int) $created) > $maxAbsolute) {
            $this->forceDestroy();

            return false;
        }

        $this->touch();

        return true;
    }

    private function forceDestroy(): void
    {
        $this->destroy();
        $this->validated = true;
    }

    private static function isStrictBindingEnabled(): bool
    {
        $raw = getenv('SESSION_STRICT') ?: ($_ENV['SESSION_STRICT'] ?? null);
        if ($raw === null || $raw === '') {
            return false;
        }

        return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
    }
}
