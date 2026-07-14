<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Auth;

use PaginiumCMS\Modules\Security\Services\UserRepository;
use PaginiumCMS\Modules\Security\Contracts\TwoFactorInterface;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class TwoFactorController
{
    private TwoFactorInterface $twoFactor;
    private UserRepository $userRepository;

    public function __construct(TwoFactorInterface $twoFactor, UserRepository $userRepository)
    {
        $this->twoFactor = $twoFactor;
        $this->userRepository = $userRepository;
    }

    public function enable(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        // Načítame čerstvé dáta z repozitára
        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->jsonError($response, 'Používateľ nebol nájdený', 404);
        }

        if ($freshUser->isTwoFactorEnabled()) {
            return $this->jsonError($response, '2FA je už aktivovaná', 400);
        }

        try {
            $secret = $this->twoFactor->enableTwoFactor($freshUser);
            $qrCode = $this->twoFactor->getQRCode($secret, $freshUser->getEmail());

            // Znova načítame používateľa pre overenie
            $updatedUser = $this->userRepository->findByEmail($freshUser->getEmail());

            return $this->jsonResponse($response, [
                'success' => true,
                'secret' => $secret,
                'qr_code' => $qrCode,
                'provisioning_uri' => $this->twoFactor->getProvisioningUri($secret, $freshUser->getEmail()),
                                       'message' => 'Naskenujte QR kód v Google Authenticator a zadajte overovací kód',
                                       'enabled' => $updatedUser ? $updatedUser->isTwoFactorEnabled() : false,
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    public function disable(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        // Načítame čerstvé dáta
        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->jsonError($response, 'Používateľ nebol nájdený', 404);
        }

        if (!$freshUser->isTwoFactorEnabled()) {
            return $this->jsonError($response, '2FA nie je aktivovaná', 400);
        }

        try {
            $this->twoFactor->disableTwoFactor($freshUser);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => '2FA bola úspešne deaktivovaná',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    public function verify(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        // Načítame čerstvé dáta
        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->jsonError($response, 'Používateľ nebol nájdený', 404);
        }

        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['code'])) {
            return $this->jsonError($response, 'TOTP kód je povinný', 400);
        }

        try {
            $isValid = $this->twoFactor->verifyCode($freshUser, $data['code']);

            if (!$isValid) {
                return $this->jsonError($response, 'Neplatný TOTP kód', 400);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'TOTP kód bol úspešne overený',
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    public function getQrCode(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        // Načítame čerstvé dáta
        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->jsonError($response, 'Používateľ nebol nájdený', 404);
        }

        if (!$freshUser->isTwoFactorEnabled()) {
            return $this->jsonError($response, '2FA nie je aktivovaná', 400);
        }

        $secret = $freshUser->getTwoFactorSecret();

        if ($secret === null) {
            return $this->jsonError($response, 'Tajný kľúč pre 2FA nie je nastavený', 400);
        }

        try {
            $qrCode = $this->twoFactor->getQRCode($secret, $freshUser->getEmail());

            return $this->jsonResponse($response, [
                'success' => true,
                'qr_code' => $qrCode,
                'provisioning_uri' => $this->twoFactor->getProvisioningUri($secret, $freshUser->getEmail()),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    public function getStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        // Načítame čerstvé dáta
        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->jsonError($response, 'Používateľ nebol nájdený', 404);
        }

        return $this->jsonResponse($response, [
            'enabled' => $freshUser->isTwoFactorEnabled(),
                                   'verified' => $this->twoFactor->isTotpVerified(),
        ]);
    }

    public function verifyLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            return $this->jsonError($response, 'Neprihlásený používateľ', 401);
        }

        // Načítame čerstvé dáta
        $freshUser = $this->userRepository->findByEmail($user->getEmail());

        if ($freshUser === null) {
            return $this->jsonError($response, 'Používateľ nebol nájdený', 404);
        }

        $data = json_decode((string)$request->getBody(), true);

        if (!isset($data['code'])) {
            return $this->jsonError($response, 'TOTP kód je povinný', 400);
        }

        try {
            $isValid = $this->twoFactor->verifyCode($freshUser, $data['code']);

            if (!$isValid) {
                return $this->jsonError($response, 'Neplatný TOTP kód', 400);
            }

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'TOTP kód bol úspešne overený',
                'user' => $freshUser->jsonSerialize(),
            ]);
        } catch (\Exception $e) {
            return $this->jsonError($response, $e->getMessage(), 400);
        }
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
        return $response
        ->withStatus($status)
        ->withHeader('Content-Type', 'application/json');
    }

    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        return $this->jsonResponse($response, [
            'success' => false,
            'error' => $message,
        ], $status);
    }
}
