<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Auth;

use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Modules\Security\Contracts\CsrfProtectionInterface;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

/**
 * Kontroler pre autentifikáciu.
 */
class AuthController
{
    private AuthenticationInterface $auth;
    private AuthorizationInterface $authz;
    private CsrfProtectionInterface $csrf;
    private PasswordPolicyInterface $passwordPolicy;
    private TwoFactorInterface $twoFactor;
    private UserRepository $userRepository;
    private SettingsRepositoryInterface $settings;
    private NotificationService $notifications;
    private IncidentNotifier $incidentNotifier;

    public function __construct(
        AuthenticationInterface $auth,
        AuthorizationInterface $authz,
        CsrfProtectionInterface $csrf,
        PasswordPolicyInterface $passwordPolicy,
        TwoFactorInterface $twoFactor,
        UserRepository $userRepository,
        SettingsRepositoryInterface $settings,
        NotificationService $notifications,
        IncidentNotifier $incidentNotifier
    ) {
        $this->auth = $auth;
        $this->authz = $authz;
        $this->csrf = $csrf;
        $this->passwordPolicy = $passwordPolicy;
        $this->twoFactor = $twoFactor;
        $this->userRepository = $userRepository;
        $this->settings = $settings;
        $this->notifications = $notifications;
        $this->incidentNotifier = $incidentNotifier;
    }

    /**
     * POST /api/auth/login
     * Prihlásenie používateľa.
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->jsonError($response, 'Email a heslo sú povinné', 400);
        }

        try {
            $user = $this->auth->login($data['email'], $data['password']);

            // Ak má používateľ aktivovanú 2FA, vyžadujeme TOTP kód
            if ($user->isTwoFactorEnabled()) {
                return $this->jsonResponse($response, [
                    'success' => true,
                    'requires_two_factor' => true,
                    'user' => $user->jsonSerialize(),
                ]);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'user' => $user->jsonSerialize(),
            ]);
        } catch (\Exception $e) {
            $this->incidentNotifier->notifyFailedLogin(
                (string) ($data['email'] ?? 'unknown'),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            );

            return $this->jsonError($response, $e->getMessage(), 401);
        }
    }

    /**
     * POST /api/auth/logout
     * Odhlásenie používateľa.
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->auth->logout();

        return $this->jsonResponse($response, [
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
        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])) {
            return $this->jsonError($response, 'Email, heslo a meno sú povinné', 400);
        }

        try {
            // Overenie hesla
            $this->passwordPolicy->requireValid($data['password']);

            // Kontrola, či používateľ už existuje
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser !== null) {
                return $this->jsonError($response, 'Používateľ s týmto emailom už existuje', 409);
            }

            // Vytvorenie nového používateľa
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($data['password']);
            $user->setName($data['name']);
            $user->setRoles(['USER']);

            $this->userRepository->save($user);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Registrácia prebehla úspešne',
                'user' => $user->jsonSerialize(),
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
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
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['old_password']) || !isset($data['new_password'])) {
            return $this->jsonError($response, 'Staré a nové heslo sú povinné', 400);
        }

        try {
            $this->auth->changePassword($user, $data['old_password'], $data['new_password']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Heslo bolo úspešne zmenené',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/reset-password
     * Reset hesla – odoslanie resetovacieho tokenu.
     */
    public function resetPassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['email'])) {
            return $this->jsonError($response, 'Email je povinný', 400);
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

                return $this->jsonResponse($response, [
                    'success' => true,
                    'message' => 'If the account exists, a reset link was sent by email.',
                ]);
            }

            // Development fallback when SMTP is not configured
            $payload = [
                'success' => true,
                'message' => 'Reset token generated (SMTP not configured)',
            ];
            if (getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'testing') {
                $payload['token'] = $token;
            }

            return $this->jsonResponse($response, $payload);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/verify-reset-token
     * Overenie resetovacieho tokenu a nastavenie nového hesla.
     */
    public function verifyResetToken(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['token']) || !isset($data['new_password'])) {
            return $this->jsonError($response, 'Token a nové heslo sú povinné', 400);
        }

        try {
            $this->auth->verifyResetToken($data['token'], $data['new_password']);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Heslo bolo úspešne zmenené',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
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
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        return $this->jsonResponse($response, [
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

        return $this->jsonResponse($response, [
            'token' => $token,
            'key' => $key,
        ]);
    }

    /**
     * Pomocná metóda pre JSON odpovede.
     */
    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $json = json_encode_utf8($data);
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json charset=utf-8');
    }

    /**
     * Pomocná metóda pre JSON chyby.
     */
    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        return $this->jsonResponse($response, [
            'success' => false,
            'error' => $message,
        ], $status);
    }
}
