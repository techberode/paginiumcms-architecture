<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Modules\Demo\Services\DemoStorageService;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class DemoController
{
    public function __construct(
        private DemoStorageService $demoStorage,
        private AuthenticationInterface $auth,
        private LoginAttemptTracker $loginAttempts,
        private SecurityLogger $securityLogger,
        private JsonResponder $json
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->demoStorage->status());
    }

    /**
     * Public demo info — no secrets, only when DEMO_MODE=true.
     */
    public function publicInfo(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->demoStorage->isEnabled()) {
            return $this->json->success($response, ['enabled' => false]);
        }

        $status = $this->demoStorage->status();

        return $this->json->success($response, [
            'enabled' => true,
            'loginEmail' => DemoFixtures::ADMIN_EMAIL,
            'credentials' => [
                'email' => DemoFixtures::ADMIN_EMAIL,
                'password' => DemoFixtures::ADMIN_PASSWORD,
            ],
            'auto_reset_minutes' => $status['auto_reset_minutes'],
            'last_reset_at' => $status['last_reset_at'],
            'next_reset_at' => $status['next_reset_at'],
            'seconds_until_reset' => $status['seconds_until_reset'],
            'isolated' => true,
        ]);
    }

    /**
     * One-click demo admin login — password stays server-side (S-DEMOCREDS).
     */
    public function quickLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->demoStorage->isEnabled()) {
            return $this->json->error($response, 'Demo quick login je dostupný len na demo inštancii', 404);
        }

        $email = DemoFixtures::ADMIN_EMAIL;
        $ip = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');

        $lockStatus = $this->loginAttempts->status($ip, $email);
        if ($lockStatus['locked']) {
            $minutes = (int) ceil($lockStatus['retryAfter'] / 60);

            return $this->json->error(
                $response,
                sprintf('Príliš veľa neúspešných pokusov. Skúste znova o %d min.', max(1, $minutes)),
                429
            );
        }

        try {
            $user = $this->auth->login($email, DemoFixtures::ADMIN_PASSWORD);

            if (!$this->auth->isAuthenticated()) {
                return $this->json->error(
                    $response,
                    'Prihlásenie prebehlo, ale session sa nepodarilo uložiť.',
                    500
                );
            }

            $this->securityLogger->recordSuccessfulLogin($user->getId(), $email, $ip);

            return $this->json->respond($response, [
                'success' => true,
                'user' => $user->jsonSerialize(),
            ]);
        } catch (\Throwable $e) {
            $this->securityLogger->recordFailedLogin($ip, $email);

            return $this->json->error($response, 'Demo prihlásenie zlyhalo', 401);
        }
    }

    public function reset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            return $this->json->error($response, 'Neautorizovaný prístup', 403);
        }

        $roles = $user->getRoles();
        if (!in_array('ADMIN', $roles, true) && !in_array('SUPER_ADMIN', $roles, true)) {
            return $this->json->error($response, 'Len ADMIN môže resetovať demo úložisko', 403);
        }

        try {
            $result = $this->demoStorage->reset();
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }

        return $this->json->success($response, $result, 200, 'Demo úložisko bolo obnovené');
    }
}
