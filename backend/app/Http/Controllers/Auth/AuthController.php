<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Auth;

use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Kontroler pre autentifikáciu.
 */
class AuthController
{
    public function __construct(
        private AuthenticationInterface $auth,
        private CsrfProtectionInterface $csrf,
        private PasswordPolicyInterface $passwordPolicy,
        private UserRepository $userRepository,
        private SettingsRepositoryInterface $settings,
        private NotificationService $notifications,
        private IncidentNotifier $incidentNotifier,
        private LoginAttemptTracker $loginAttempts,
        private SecurityLogger $securityLogger,
        private JsonResponder $json
    ) {
    }

    /**
     * POST /api/auth/login
     * Prihlásenie používateľa.
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json->error($response, 'Email a heslo sú povinné', 400);
        }

        $email = (string) $data['email'];
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
            $user = $this->auth->login($email, (string) $data['password']);

            $this->securityLogger->recordSuccessfulLogin($user->getId(), $email, $ip);

            if ($user->isTwoFactorEnabled()) {
                return $this->json->respond($response, [
                    'success' => true,
                    'requires_two_factor' => true,
                    'user' => $user->jsonSerialize(),
                ]);
            }

            return $this->json->respond($response, [
                'success' => true,
                'user' => $user->jsonSerialize(),
            ]);
        } catch (\Exception $e) {
            $this->securityLogger->recordFailedLogin($ip, $email);
            $this->incidentNotifier->notifyFailedLogin($email, $ip);

            return $this->json->error($response, $e->getMessage(), 401);
        }
    }

    /**
     * POST /api/auth/logout
     * Odhlásenie používateľa.
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->auth->logout();

        return $this->json->respond($response, [
            'success' => true,
            'message' => 'Odhlásenie prebehlo úspešne',
        ]);
    }

    /**
     * POST /api/auth/register
     * Registrácia nového používateľa.
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            return $this->json->error($response, 'Email, heslo a meno sú povinné', 400);
        }

        $general = $this->settings->group('general');
        if (($general['allowRegistration'] ?? true) === false) {
            return $this->json->error($response, 'Registrácia nových používateľov je vypnutá', 403);
        }

        try {
            $this->passwordPolicy->requireValid($data['password']);

            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser !== null) {
                return $this->json->error($response, 'Používateľ s týmto emailom už existuje', 409);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($data['password']);
            $user->setName($data['name']);
            $user->setRoles(['USER']);

            $this->userRepository->save($user);

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'Registrácia prebehla úspešne',
                'user' => $user->jsonSerialize(),
            ], 201);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/change-password
     * Zmena hesla prihláseného používateľa.
     */
    public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['old_password']) || !isset($data['new_password'])) {
            return $this->json->error($response, 'Staré a nové heslo sú povinné', 400);
        }

        try {
            $this->auth->changePassword($user, $data['old_password'], $data['new_password']);

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'Heslo bolo úspešne zmenené',
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/reset-password
     * Reset hesla – odoslanie resetovacieho tokenu.
     */
    public function resetPassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['email'])) {
            return $this->json->error($response, 'Email je povinný', 400);
        }

        try {
            $token = $this->auth->resetPassword($data['email']);
            $user = $this->userRepository->findByEmail($data['email']);

            $smtp = $this->settings->group('smtp');
            $connectors = $this->settings->group('connectors');
            $emailChannel = (bool) ($connectors['emailEnabled'] ?? false) || (bool) ($smtp['enabled'] ?? false);

            if ($emailChannel && $user !== null && in_array('email', $this->notifications->getAdapters(), true)) {
                $general = $this->settings->group('general');
                $siteUrl = rtrim((string) ($general['siteUrl'] ?? getenv('APP_URL') ?: 'http://localhost:5173'), '/');
                $resetUrl = $siteUrl . '/reset-password?token=' . urlencode($token);
                $body = '<p>Password reset was requested for your PaginiumCMS account.</p>'
                    . '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset your password</a></p>'
                    . '<p>Token expires in 24 hours. If you did not request this, ignore this email.</p>';

                $this->notifications->send(
                    'email',
                    $user->getEmail(),
                    'PaginiumCMS password reset',
                    $body,
                    ['html' => $body, 'event' => 'auth.password_reset']
                );

                return $this->json->respond($response, [
                    'success' => true,
                    'message' => 'If the account exists, a reset link was sent by email.',
                ]);
            }

            $payload = [
                'success' => true,
                'message' => 'Reset token generated (SMTP not configured)',
            ];
            if (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'testing') {
                $payload['token'] = $token;
            }

            return $this->json->respond($response, $payload);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/verify-reset-token
     * Overenie resetovacieho tokenu a nastavenie nového hesla.
     */
    public function verifyResetToken(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['token']) || !isset($data['new_password'])) {
            return $this->json->error($response, 'Token a nové heslo sú povinné', 400);
        }

        try {
            $this->auth->verifyResetToken($data['token'], $data['new_password']);

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'Heslo bolo úspešne zmenené',
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * GET /api/auth/me
     * Získanie aktuálneho používateľa.
     */
    public function getCurrentUser(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        return $this->json->respond($response, [
            'success' => true,
            'user' => $user->jsonSerialize(),
        ]);
    }

    /**
     * POST /api/auth/csrf-token
     * Získanie CSRF tokenu.
     */
    public function getCsrfToken(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $key = $request->getQueryParams()['key'] ?? 'default';
        $token = $this->csrf->getToken($key);

        return $this->json->respond($response, [
            'token' => $token,
            'key' => $key,
        ]);
    }
}
