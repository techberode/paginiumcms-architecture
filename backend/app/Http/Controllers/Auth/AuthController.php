<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Auth;

use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Security\TwoFactorPolicy;
use PaginiumCMS\Core\Security\Services\LoginAttemptTracker;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationRules;
use PaginiumCMS\Core\Workflow\Services\OtpWorkflowService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Demo\Services\DemoLoginGuard;
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
        private LoginAttemptTracker $loginAttempts,
        private SecurityLogger $securityLogger,
        private OtpWorkflowService $otpWorkflow,
        private JsonResponder $json,
        private DemoLoginGuard $demoLoginGuard
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

        $blocked = $this->demoLoginGuard->blockedLoginMessage($email);
        if ($blocked !== null) {
            return $this->json->error($response, $blocked, 401);
        }

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

            if (!$this->auth->isAuthenticated()) {
                return $this->json->error(
                    $response,
                    'Prihlásenie prebehlo, ale session sa nepodarilo uložiť. Skontrolujte SESSION_* v .env a reštart PHP.',
                    500
                );
            }

            $this->securityLogger->recordSuccessfulLogin($user->getId(), $email, $ip);

            if (
                TwoFactorPolicy::isRequired()
                && $user->isTwoFactorEnabled()
                && $user->getTwoFactorVerifiedAt() !== null
            ) {
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

            $message = $this->demoLoginGuard->failedLoginMessage($email, $e->getMessage());

            return $this->json->error($response, $message, 401);
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

        $password = (string) $data['password'];
        $passwordConfirm = (string) ($data['passwordConfirm'] ?? $data['password_confirm'] ?? '');
        $confirmErrors = ValidationRules::validatePasswordConfirmation($password, $passwordConfirm);
        if ($confirmErrors !== []) {
            return $this->json->validation($response, $confirmErrors[0], ['passwordConfirm' => $confirmErrors]);
        }

        $general = $this->settings->group('general');
        if (($general['allowRegistration'] ?? true) === false) {
            return $this->json->error($response, 'Registrácia nových používateľov je vypnutá', 403);
        }

        $maintenance = $this->settings->group('maintenance');
        if (\PaginiumCMS\Core\Settings\MaintenanceMode::isActive($maintenance)) {
            return $this->json->error($response, 'Registrácia je počas režimu údržby vypnutá', 403);
        }

        try {
            $this->passwordPolicy->requireValid($data['password']);

            $email = (string) $data['email'];
            $name = (string) $data['name'];

            $existingUser = $this->userRepository->findByEmail($email);
            if ($existingUser !== null) {
                return $this->json->error($response, 'Používateľ s týmto emailom už existuje', 409);
            }

            if ($this->otpWorkflow->isRegistrationOtpEnabled()) {
                $otp = $this->otpWorkflow->startRegistration($email, $name, $password);

                return $this->json->respond($response, [
                    'success' => true,
                    'requires_otp' => true,
                    'message' => 'Overovací kód bol odoslaný na email',
                    'challenge_id' => $otp['challenge_id'],
                    'expires_at' => $otp['expires_at'],
                    'debug_code' => $otp['debug_code'] ?? null,
                ], 202);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setPassword($password);
            $user->setName($name);
            $user->setRoles(['USER']);

            $this->userRepository->save($user);

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'Registrácia prebehla úspešne',
                'user' => $user->jsonSerialize(),
            ], 201);
        } catch (\Exception $e) {
            $status = str_contains($e->getMessage(), 'už existuje') ? 409 : 400;

            return $this->json->error($response, $e->getMessage(), $status);
        }
    }

    /**
     * POST /api/auth/register/verify-otp
     * Dokončenie registrácie po overení e-mailového OTP kódu.
     */
    public function verifyRegisterOtp(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['challenge_id']) || !isset($data['code'])) {
            return $this->json->error($response, 'challenge_id a code sú povinné', 400);
        }

        if (!$this->otpWorkflow->isRegistrationOtpEnabled()) {
            return $this->json->error($response, 'OTP registrácia nie je zapnutá', 403);
        }

        try {
            $result = $this->otpWorkflow->verifyRegistration(
                (string) $data['challenge_id'],
                (string) $data['code']
            );

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'Registrácia prebehla úspešne',
                'user' => $result['user'],
            ], 201);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/register/resend-otp
     * Opätovné odoslanie registračného OTP kódu.
     */
    public function resendRegisterOtp(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data['challenge_id'])) {
            return $this->json->error($response, 'challenge_id je povinný', 400);
        }

        if (!$this->otpWorkflow->isRegistrationOtpEnabled()) {
            return $this->json->error($response, 'OTP registrácia nie je zapnutá', 403);
        }

        try {
            $result = $this->otpWorkflow->resendRegistration((string) $data['challenge_id']);

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'Nový overovací kód bol odoslaný',
                'challenge_id' => $result['challenge_id'],
                'expires_at' => $result['expires_at'],
                'debug_code' => $result['debug_code'] ?? null,
            ]);
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

        // Anti-enumeration: odpoveď je vždy rovnaká bez ohľadu na to,
        // či účet existuje. Token sa nikdy nevracia v produkcii.
        $genericMessage = 'If the account exists, a reset link was sent by email.';
        $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? '');
        $isDev = $appEnv === 'development' || $appEnv === 'testing';

        try {
            $token = $this->auth->resetPassword($data['email']);
        } catch (\Throwable $e) {
            // Neexistujúci účet ani iná chyba nesmie prezradiť stav účtu.
            $this->securityLogger->logSuspiciousActivity(
                'auth.password_reset_unknown',
                'Reset requested for non-existent or invalid account'
            );

            return $this->json->respond($response, [
                'success' => true,
                'message' => $genericMessage,
            ]);
        }

        try {
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
            }
        } catch (\Throwable) {
            // Zlyhanie odoslania e-mailu nesmie zmeniť odpoveď (anti-enumeration).
        }

        $payload = [
            'success' => true,
            'message' => $genericMessage,
        ];
        if ($isDev) {
            $payload['token'] = $token;
        }

        return $this->json->respond($response, $payload);
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
