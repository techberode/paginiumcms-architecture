<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Setup\Services;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Gates browser setup with optional SETUP_TOKEN (required in production when configured).
 */
final class SetupAccessGuard
{
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

        return in_array($env, ['production', 'staging'], true);
    }
}
