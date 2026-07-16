<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Developer\DeveloperMode;
use PaginiumCMS\Core\Developer\DeveloperModeGate;
use PaginiumCMS\Core\Developer\Services\DeveloperLogger;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

class DeveloperController
{
    public function __construct(
        private DeveloperModeGate $gate,
        private DeveloperMode $developerMode,
        private DeveloperLogger $developerLogger,
        private TwoFactorInterface $twoFactor
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'success' => true,
            'data' => array_merge(
                $this->gate->getStatus(),
                ['debug' => $this->developerMode->getDebugData()]
            ),
        ]);
    }

    public function unlock(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true) ?: [];
        $user = $request->getAttribute('user');

        if (!empty($data['token'])) {
            $ok = $this->gate->unlockWithToken((string) $data['token']);
        } elseif (!empty($data['totp_code']) && $user instanceof User) {
            $ok = $this->gate->unlockWithTotp($user, (string) $data['totp_code'], $this->twoFactor);
        } else {
            return $this->json($response, [
                'success' => false,
                'error' => 'Poskytnite token alebo totp_code (admin s 2FA)',
            ], 400);
        }

        if (!$ok) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Odomknutie zlyhalo – neplatný token alebo TOTP',
            ], 403);
        }

        $this->developerMode->logEvent('gate', 'Developer Mode unlocked', [
            'method' => $this->gate->getUnlockMethod(),
            'user' => $user instanceof User ? $user->getEmail() : 'token',
        ]);

        return $this->json($response, [
            'success' => true,
            'data' => $this->gate->getStatus(),
        ]);
    }

    public function lock(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->gate->lock();
        $this->developerLogger->flush();

        return $this->json($response, [
            'success' => true,
            'message' => 'Developer Mode zamknutý',
            'data' => $this->gate->getStatus(),
        ]);
    }

    public function logs(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->gate->isUnlocked()) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Developer Mode nie je odomknutý',
            ], 403);
        }

        $params = $request->getQueryParams();
        $limit = min(500, max(1, (int) ($params['limit'] ?? 100)));

        return $this->json($response, [
            'success' => true,
            'data' => $this->developerLogger->tail($limit),
        ]);
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
