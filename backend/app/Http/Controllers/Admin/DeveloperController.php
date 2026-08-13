<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Developer\DeveloperMode;
use PaginiumCMS\Core\Developer\DeveloperModeGate;
use PaginiumCMS\Core\Developer\Services\DeveloperLogger;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeveloperController
{
    public function __construct(
        private DeveloperModeGate $gate,
        private DeveloperMode $developerMode,
        private DeveloperLogger $developerLogger,
        private TwoFactorInterface $twoFactor,
        private UserRepository $userRepository,
        private AuthenticationInterface $auth,
        private JsonResponder $json
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, array_merge(
            $this->gate->getStatus(),
            ['debug' => $this->developerMode->getDebugData()]
        ));
    }

    public function unlock(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->gate->isFeatureAvailable()) {
            return $this->json->error(
                $response,
                'Developer Mode nie je povolený v konfigurácii. Nastavte DEVELOPER_MODE=true alebo APP_DEBUG=true v .env.',
                403
            );
        }

        $data = RequestJsonBody::decode($request) ?? [];
        $sessionUser = $request->getAttribute('user');

        if (!empty($data['token'])) {
            $ok = $this->gate->unlockWithToken((string) $data['token']);
            if (!$ok) {
                return $this->json->error(
                    $response,
                    'Neplatný alebo expirovaný dev token. Vygenerujte a zaregistrujte ho cez backend/bin/dev-token.php.',
                    403
                );
            }
        } elseif (!empty($data['totp_code']) && $sessionUser instanceof User) {
            $freshUser = $this->userRepository->findByEmail($sessionUser->getEmail());
            if ($freshUser === null) {
                return $this->json->error($response, 'Používateľ nebol nájdený', 404);
            }

            if (!$freshUser->isTwoFactorEnabled()) {
                return $this->json->error(
                    $response,
                    'Pre odomknutie musíte mať aktivovanú a overenú 2FA v sekcii Bezpečnosť účtu.',
                    403
                );
            }

            $ok = $this->gate->unlockWithTotp($sessionUser, (string) $data['totp_code'], $this->twoFactor);
            if (!$ok) {
                return $this->json->error(
                    $response,
                    'Neplatný TOTP kód. Použite aktuálny 6-miestny kód z Google Authenticator (alebo inej TOTP aplikácie).',
                    403
                );
            }

            $this->auth->refreshCurrentUserFromStorage();
        } else {
            return $this->json->error($response, 'Poskytnite token alebo totp_code (admin s 2FA)', 400);
        }

        $this->developerMode->logEvent('gate', 'Developer Mode unlocked', [
            'method' => $this->gate->getUnlockMethod(),
            'user' => $sessionUser instanceof User ? $sessionUser->getEmail() : 'token',
        ]);

        return $this->json->success($response, $this->gate->getStatus());
    }

    public function lock(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->gate->lock();
        $this->developerLogger->flush();

        return $this->json->success($response, $this->gate->getStatus(), 200, 'Developer Mode zamknutý');
    }

    public function logs(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->gate->isUnlocked()) {
            return $this->json->error($response, 'Developer Mode nie je odomknutý', 403);
        }

        $params = $request->getQueryParams();
        $limit = min(500, max(1, (int) ($params['limit'] ?? 100)));

        return $this->json->success($response, $this->developerLogger->tail($limit));
    }
}
