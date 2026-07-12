<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Auth\AuthController;
use PaginiumCMS\Http\Controllers\Auth\TwoFactorController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $app->get('/api/auth/csrf-token', function (Request $request, Response $response) {
        // Dočasne vrátime testovací token
        $token = bin2hex(random_bytes(32));

        $data = [
            'csrf_token' => $token,
            'expires_in' => 3600
        ];

        $response->getBody()->write(json_encode($data));
        return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('X-CSRF-Token', $token);
    });

    // Skupina pre autentifikáciu
    $app->group('/api/auth', function (RouteCollectorProxy $group) {
        // Verejné endpointy
        $group->post('/login', AuthController::class . ':login');
        $group->post('/register', AuthController::class . ':register');
        $group->post('/reset-password', AuthController::class . ':resetPassword');
        $group->post('/verify-reset-token', AuthController::class . ':verifyResetToken');
        $group->get('/csrf-token', AuthController::class . ':getCsrfToken');

        // Chránené endpointy (vyžadujú prihlásenie)
        $group->group('', function (RouteCollectorProxy $protected) {
            $protected->post('/logout', AuthController::class . ':logout');
            $protected->post('/change-password', AuthController::class . ':changePassword');
            $protected->get('/me', AuthController::class . ':getCurrentUser');

            // 2FA endpointy
            $protected->post('/2fa/enable', TwoFactorController::class . ':enable');
            $protected->post('/2fa/disable', TwoFactorController::class . ':disable');
            $protected->post('/2fa/verify', TwoFactorController::class . ':verify');
            $protected->get('/2fa/qr-code', TwoFactorController::class . ':getQrCode');
            $protected->get('/2fa/status', TwoFactorController::class . ':getStatus');
            $protected->post('/2fa/verify-login', TwoFactorController::class . ':verifyLogin');
        })->add(AuthMiddleware::class);

        // Endpointy vyžadujúce ADMIN rolu
        $group->group('/admin', function (RouteCollectorProxy $admin) {
            $admin->get('/users', function ($request, $response) {
                // TODO: Implementovať správu používateľov
                return $response;
            });
        })->add(new RoleMiddleware(
            $app->getContainer()->get(AuthorizationManager::class),
            ['ADMIN', 'SUPER_ADMIN']
        ))->add(AuthMiddleware::class);
    })->add(TwoFactorMiddleware::class);
};
