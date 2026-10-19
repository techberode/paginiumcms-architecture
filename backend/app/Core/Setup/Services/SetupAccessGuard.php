<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Setup\Services;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Gates browser setup with SETUP_TOKEN. The token may be omitted only in explicit
 * development environments; any other APP_ENV (including typos like `prod`) fails closed.
 */
final class SetupAccessGuard
{
    /** @var list<string> */
    private const TOKEN_OPTIONAL_ENVS = ['development', 'local', 'testing', 'test'];

    public function isInstalledProbeAllowed(SetupStatusService $status): bool
    {
        return !$status->isInstalled();
    }

    public function verifySetupToken(ServerRequestInterface $request, ?string $bodyToken = null): bool
    {
        $expected = trim((string) (getenv('SETUP_TOKEN') ?: ''));
        if ($expected === '') {
            return !$this->isProductionEnvironment();
        }

        $provided = trim($request->getHeaderLine('X-Setup-Token'));
        if ($provided === '' && $bodyToken !== null) {
            $provided = trim($bodyToken);
        }

        if ($provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }

    public function tokenRequired(): bool
    {
        $expected = trim((string) (getenv('SETUP_TOKEN') ?: ''));

        return $expected !== '' || $this->isProductionEnvironment();
    }

    private function isProductionEnvironment(): bool
    {
        $env = strtolower(trim((string) (getenv('APP_ENV') ?: 'production')));

        return !in_array($env, self::TOKEN_OPTIONAL_ENVS, true);
    }
}
