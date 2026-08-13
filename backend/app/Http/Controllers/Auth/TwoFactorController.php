<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Auth;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\AuthenticationInterface;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TwoFactorController
{
    public function __construct(
        private TwoFactorInterface $twoFactor,
        private UserRepository $userRepository,
        private AuthenticationInterface $auth,
        private JsonResponder $json
    ) {
    }

    public function enable(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->json->error($response, 'Používateľ nebol nájdený', 404);
        }

        if ($freshUser->isTwoFactorEnabled() && $freshUser->getTwoFactorVerifiedAt() !== null) {
            return $this->json->error($response, '2FA je už aktivovaná', 400);
        }

        try {
            $secret = $this->twoFactor->enableTwoFactor($freshUser);
            $qrCode = $this->twoFactor->getQRCode($secret, $freshUser->getEmail());
            $updatedUser = $this->userRepository->findByEmail($freshUser->getEmail());
            $this->auth->refreshCurrentUserFromStorage();

            return $this->json->respond($response, [
                'success' => true,
                'secret' => $secret,
                'qr_code' => $qrCode,
                'provisioning_uri' => $this->twoFactor->getProvisioningUri($secret, $freshUser->getEmail()),
                'message' => 'Naskenujte QR kód v Google Authenticator a zadajte overovací kód',
                'enabled' => $updatedUser ? $updatedUser->isTwoFactorEnabled() : false,
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    public function disable(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->json->error($response, 'Používateľ nebol nájdený', 404);
        }

        if (!$freshUser->isTwoFactorEnabled()) {
            return $this->json->error($response, '2FA nie je aktivovaná', 400);
        }

        try {
            $this->twoFactor->disableTwoFactor($freshUser);
            $this->auth->refreshCurrentUserFromStorage();

            return $this->json->respond($response, [
                'success' => true,
                'message' => '2FA bola úspešne deaktivovaná',
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    public function verify(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->json->error($response, 'Používateľ nebol nájdený', 404);
        }

        $data = RequestJsonBody::decode($request);

        if (!isset($data['code'])) {
            return $this->json->error($response, 'TOTP kód je povinný', 400);
        }

        try {
            $isValid = $this->twoFactor->verifyCode($freshUser, $data['code']);

            if (!$isValid) {
                return $this->json->error($response, 'Neplatný TOTP kód', 400);
            }

            $this->auth->refreshCurrentUserFromStorage();

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'TOTP kód bol úspešne overený',
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    public function getQrCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->json->error($response, 'Používateľ nebol nájdený', 404);
        }

        if (!$freshUser->isTwoFactorEnabled()) {
            return $this->json->error($response, '2FA nie je aktivovaná', 400);
        }

        $secret = $freshUser->getTwoFactorSecret();

        if ($secret === null) {
            return $this->json->error($response, 'Tajný kľúč pre 2FA nie je nastavený', 400);
        }

        try {
            $qrCode = $this->twoFactor->getQRCode($secret, $freshUser->getEmail());

            return $this->json->respond($response, [
                'success' => true,
                'qr_code' => $qrCode,
                'provisioning_uri' => $this->twoFactor->getProvisioningUri($secret, $freshUser->getEmail()),
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }

    public function getStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->json->error($response, 'Používateľ nebol nájdený', 404);
        }

        return $this->json->respond($response, [
            'enabled' => $freshUser->isTwoFactorEnabled(),
            'verified' => $this->twoFactor->isTotpVerified(),
            'setup_pending' => $freshUser->isTwoFactorEnabled() && $freshUser->getTwoFactorVerifiedAt() === null,
        ]);
    }

    public function verifyLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->json->error($response, 'Neprihlásený používateľ', 401);
        }

        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->json->error($response, 'Používateľ nebol nájdený', 404);
        }

        $data = RequestJsonBody::decode($request);

        if (!isset($data['code'])) {
            return $this->json->error($response, 'TOTP kód je povinný', 400);
        }

        try {
            $isValid = $this->twoFactor->verifyCode($freshUser, $data['code']);

            if (!$isValid) {
                return $this->json->error($response, 'Neplatný TOTP kód', 400);
            }

            $refreshed = $this->auth->refreshCurrentUserFromStorage();

            return $this->json->respond($response, [
                'success' => true,
                'message' => 'TOTP kód bol úspešne overený',
                'user' => ($refreshed ?? $freshUser)->jsonSerialize(),
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }
}
