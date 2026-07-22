<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Exception\AuthenticationException;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Generic OAuth2 SSO (Iteration 11).
 */
final class OAuthSsoService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private UserRepository $users,
        private SessionManager $session
    ) {
    }

    /**
     * @return list<array{id: string, name: string, type: string}>
     */
    public function listPublicProviders(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $providers = [];
        $sso = $this->settings->group('sso');

        if (($sso['githubEnabled'] ?? false) === true && (string) ($sso['githubClientId'] ?? '') !== '') {
            $providers[] = ['id' => 'github', 'name' => 'GitHub', 'type' => 'oauth2'];
        }

        if (($sso['genericEnabled'] ?? false) === true && (string) ($sso['genericClientId'] ?? '') !== '') {
            $providers[] = [
                'id' => 'generic',
                'name' => (string) ($sso['genericName'] ?? 'OAuth'),
                'type' => 'oauth2',
            ];
        }

        return $providers;
    }

    /**
     * @return array{authorizationUrl: string, state: string}
     */
    public function buildAuthorizationRequest(string $providerId, string $redirectUri): array
    {
        $config = $this->providerConfig($providerId);
        $state = bin2hex(random_bytes(16));
        $_SESSION['sso_state'] = [
            'provider' => $providerId,
            'state' => $state,
            'redirect_uri' => $redirectUri,
            'expires' => time() + 600,
        ];

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $config['scope'],
            'state' => $state,
        ];

        return [
            'authorizationUrl' => $config['authorize_url'] . '?' . http_build_query($params),
            'state' => $state,
        ];
    }

    public function completeAuthorization(string $providerId, string $code, string $state, string $redirectUri): User
    {
        $sessionState = $_SESSION['sso_state'] ?? null;
        if (!is_array($sessionState)) {
            throw new AuthenticationException('SSO state missing');
        }

        // Timing-safe porovnanie provider + state (anti CSRF/state fixation).
        if (
            !hash_equals((string) ($sessionState['provider'] ?? ''), $providerId)
            || !hash_equals((string) ($sessionState['state'] ?? ''), $state)
        ) {
            throw new AuthenticationException('Invalid SSO state');
        }

        // redirect_uri MUSÍ zodpovedať tomu, s ktorým sa flow začal – inak by
        // útočník mohol podstrčiť vlastné redirect_uri do výmeny kódu za token.
        if (!hash_equals((string) ($sessionState['redirect_uri'] ?? ''), $redirectUri)) {
            throw new AuthenticationException('Invalid SSO redirect URI');
        }

        if ((int) ($sessionState['expires'] ?? 0) < time()) {
            throw new AuthenticationException('SSO state expired');
        }

        unset($_SESSION['sso_state']);

        $config = $this->providerConfig($providerId);
        $tokenPayload = $this->exchangeCode($config, $code, $redirectUri);
        $profile = $this->fetchUserProfile($config, $tokenPayload);

        $email = strtolower(trim((string) ($profile['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AuthenticationException('OAuth provider did not return a valid email');
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            $user = $this->provisionUser($email, (string) ($profile['name'] ?? $email));
        }

        if (!$user->isActive()) {
            throw new AuthenticationException('Účet je deaktivovaný');
        }

        $this->session->setUser($user);
        $this->session->clearTotpVerified();

        return $user;
    }

    private function isEnabled(): bool
    {
        $sso = $this->settings->group('sso');

        return ($sso['enabled'] ?? false) === true;
    }

    /**
     * @return array{
     *     client_id: string,
     *     client_secret: string,
     *     authorize_url: string,
     *     token_url: string,
     *     userinfo_url: string,
     *     scope: string
     * }
     */
    private function providerConfig(string $providerId): array
    {
        if (!$this->isEnabled()) {
            throw new AuthenticationException('SSO is disabled');
        }

        $sso = $this->settings->group('sso');

        if ($providerId === 'github') {
            if (($sso['githubEnabled'] ?? false) !== true) {
                throw new AuthenticationException('GitHub SSO is disabled');
            }

            return [
                'client_id' => (string) ($sso['githubClientId'] ?? ''),
                'client_secret' => (string) ($sso['githubClientSecret'] ?? ''),
                'authorize_url' => 'https://github.com/login/oauth/authorize',
                'token_url' => 'https://github.com/login/oauth/access_token',
                'userinfo_url' => 'https://api.github.com/user',
                'scope' => 'read:user user:email',
            ];
        }

        if ($providerId === 'generic') {
            if (($sso['genericEnabled'] ?? false) !== true) {
                throw new AuthenticationException('Generic OAuth provider is disabled');
            }

            return [
                'client_id' => (string) ($sso['genericClientId'] ?? ''),
                'client_secret' => (string) ($sso['genericClientSecret'] ?? ''),
                'authorize_url' => (string) ($sso['genericAuthorizeUrl'] ?? ''),
                'token_url' => (string) ($sso['genericTokenUrl'] ?? ''),
                'userinfo_url' => (string) ($sso['genericUserInfoUrl'] ?? ''),
                'scope' => (string) ($sso['genericScope'] ?? 'openid email profile'),
            ];
        }

        throw new AuthenticationException('Unknown SSO provider');
    }

    /**
     * @param array{
     *     client_id: string,
     *     client_secret: string,
     *     authorize_url: string,
     *     token_url: string,
     *     userinfo_url: string,
     *     scope: string
     * } $config
     * @return array<string, mixed>
     */
    private function exchangeCode(array $config, string $code, string $redirectUri): array
    {
        $body = $this->httpPostForm($config['token_url'], [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ], [
            'Accept: application/json',
        ]);

        $payload = JsonHelper::decode($body);
        if (!isset($payload['access_token'])) {
            throw new AuthenticationException('OAuth token exchange failed');
        }

        return [
            'access_token' => (string) $payload['access_token'],
        ];
    }

    /**
     * @param array{
     *     client_id: string,
     *     client_secret: string,
     *     authorize_url: string,
     *     token_url: string,
     *     userinfo_url: string,
     *     scope: string
     * } $config
     * @param array<string, mixed> $tokenPayload
     * @return array{email?: string, name?: string}
     */
    private function fetchUserProfile(array $config, array $tokenPayload): array
    {
        $accessToken = (string) $tokenPayload['access_token'];
        $body = $this->httpGet(
            $config['userinfo_url'],
            ['Authorization: Bearer ' . $accessToken, 'Accept: application/json']
        );

        $profile = JsonHelper::decode($body);

        $email = (string) ($profile['email'] ?? '');
        if ($email === '' && isset($profile['login'])) {
            $email = (string) $profile['login'] . '@users.noreply.github.com';
        }

        return [
            'email' => $email,
            'name' => (string) ($profile['name'] ?? $profile['login'] ?? $email),
        ];
    }

    private function provisionUser(string $email, string $name): User
    {
        // Bezpečný default: JIT-provisionovaný SSO účet dostáva najnižšiu rolu
        // (USER). Privilegované roly (ADMIN/SUPER_ADMIN) sa nikdy neprideľujú
        // automaticky z IdP – povýšenie musí spraviť admin manuálne.
        $sso = $this->settings->group('sso');
        $role = strtoupper((string) ($sso['defaultRole'] ?? 'USER'));
        if (!in_array($role, ['USER', 'EDITOR'], true)) {
            $role = 'USER';
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword(bin2hex(random_bytes(16)) . 'Aa1!');
        $user->addRole($role);
        $this->users->save($user);

        return $user;
    }

    /**
     * @param array<string, string> $fields
     * @param list<string> $headers
     */
    private function httpPostForm(string $url, array $fields, array $headers = []): string
    {
        // SSRF guard (C14): token_url je admin-konfigurovateľný → nesmie mieriť
        // na interné/privátne služby ani ne-HTTPS ciele (v produkcii).
        OutboundUrlGuard::fromEnv()->assertAllowed($url);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers)),
                'content' => http_build_query($fields),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            throw new RuntimeException('OAuth HTTP POST failed');
        }

        return $result;
    }

    /**
     * @param list<string> $headers
     */
    private function httpGet(string $url, array $headers = []): string
    {
        // SSRF guard (C14): userinfo_url je admin-konfigurovateľný.
        OutboundUrlGuard::fromEnv()->assertAllowed($url);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $result = file_get_contents($url, false, $context);
        if ($result === false) {
            throw new RuntimeException('OAuth HTTP GET failed');
        }

        return $result;
    }
}
