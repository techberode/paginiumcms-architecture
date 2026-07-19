<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Auth;

use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Exception\AuthenticationException;
use PaginiumCMS\Modules\Security\Services\OAuthSsoService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SsoController
{
    public function __construct(
        private OAuthSsoService $sso,
        private SettingsRepositoryInterface $settings,
        private SecurityLogger $securityLogger,
        private JsonResponder $json
    ) {
    }

    public function providers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'enabled' => ($this->settings->group('sso')['enabled'] ?? false) === true,
            'providers' => $this->sso->listPublicProviders(),
        ]);
    }

    /**
     * @param array<string, string> $args
     */
    public function start(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $providerId = (string) ($args['provider'] ?? '');
        $params = $request->getQueryParams();
        $redirectUri = trim((string) ($params['redirect_uri'] ?? ''));
        if ($redirectUri === '') {
            $general = $this->settings->group('general');
            $siteUrl = rtrim((string) ($general['siteUrl'] ?? ''), '/');
            $redirectUri = ($siteUrl !== '' ? $siteUrl : 'http://localhost:3025')
                . '/api/auth/sso/' . rawurlencode($providerId) . '/callback';
        }

        try {
            $payload = $this->sso->buildAuthorizationRequest($providerId, $redirectUri);

            return $this->json->success($response, $payload);
        } catch (AuthenticationException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function callback(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $providerId = (string) ($args['provider'] ?? '');
        $params = $request->getQueryParams();
        $code = (string) ($params['code'] ?? '');
        $state = (string) ($params['state'] ?? '');
        $redirectUri = trim((string) ($params['redirect_uri'] ?? ''));

        if ($code === '' || $state === '') {
            return $this->json->error($response, 'Missing OAuth code or state', 400);
        }

        if ($redirectUri === '') {
            $general = $this->settings->group('general');
            $siteUrl = rtrim((string) ($general['siteUrl'] ?? ''), '/');
            $redirectUri = ($siteUrl !== '' ? $siteUrl : 'http://localhost:3025')
                . '/api/auth/sso/' . rawurlencode($providerId) . '/callback';
        }

        try {
            $user = $this->sso->completeAuthorization($providerId, $code, $state, $redirectUri);
            $ip = (string) ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
            $this->securityLogger->logSsoLogin($user, $providerId, $ip);

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
        } catch (AuthenticationException $e) {
            return $this->json->error($response, $e->getMessage(), 401);
        }
    }
}
