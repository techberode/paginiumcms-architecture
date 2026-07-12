use PsrHttpMessageServerRequestInterface as Request;
use PsrHttpMessageResponseInterface as Response;
<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Controllers\Auth\AuthController;
use PaginiumCMS\Http\Controllers\Auth\TwoFactorController;
use PaginiumCMS\Http\Controllers\Admin\BackupController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use DI\Container;
use PaginiumCMS\Http\Controllers\Admin\CodeEditorController;
use PaginiumCMS\Http\Controllers\Admin\VersionController;
use PaginiumCMS\Http\Controllers\Admin\AuditTrailController;

return function (App $app): void {
    $container = $app->getContainer();
if ($container === null) {
    throw new \RuntimeException("Container not available");
}
if ($container === null) {
    throw new \RuntimeException("Container nie je dostupný");
}
    if ($container === null) {
        throw new \RuntimeException('Container nie je dostupný');
    }
    $service = $container->get(SomeClass::class);

    // Registrácia auth routes
    $authRoutes = require __DIR__ . '/../app/Http/Routes/auth.php';
    $authRoutes($app);

    // Registrácia content routes (PRIDAJTE TOTO)
    $contentRoutes = require __DIR__ . '/../app/Http/Routes/content.php';
    $contentRoutes($app);

    // --- AUTH ROUTY ---
    $app->group('/api/auth', function (RouteCollectorProxy $group) use ($container) {
        $authController = $container->get(AuthController::class);
        $twoFactorController = $container->get(TwoFactorController::class);

        $group->post('/register', [$authController, 'register']);
        $group->post('/login', [$authController, 'login']);
        $group->post('/reset-password', [$authController, 'resetPassword']);
        $group->post('/verify-reset-token', [$authController, 'verifyResetToken']);
        $group->get('/csrf-token', [$authController, 'getCsrfToken']);

        $group->group('', function (RouteCollectorProxy $protected) use ($authController, $twoFactorController) {
            $protected->post('/logout', [$authController, 'logout']);
            $protected->post('/change-password', [$authController, 'changePassword']);
            $protected->get('/me', [$authController, 'getCurrentUser']);
            $protected->post('/2fa/enable', [$twoFactorController, 'enable']);
            $protected->post('/2fa/disable', [$twoFactorController, 'disable']);
            $protected->post('/2fa/verify', [$twoFactorController, 'verify']);
            $protected->get('/2fa/qr-code', [$twoFactorController, 'getQrCode']);
            $protected->get('/2fa/status', [$twoFactorController, 'getStatus']);
            $protected->post('/2fa/verify-login', [$twoFactorController, 'verifyLogin']);
        })->add($container->get(AuthMiddleware::class));

        $group->group('/admin', function (RouteCollectorProxy $admin) use ($container) {
            $admin->get('/users', function ($request, $response) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Admin endpoint - users management (TODO)'
                ]));
                return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
            });
        })->add(new RoleMiddleware(
            $container->get(AuthorizationManager::class),
                                   ['ADMIN', 'SUPER_ADMIN']
        ))->add($container->get(AuthMiddleware::class));
    });


    // --- BACKUP ROUTY ---
    $app->group('/api/admin', function (RouteCollectorProxy $group) use ($container) {
        $backupController = $container->get(BackupController::class);

        $group->get('/backups', [$backupController, 'listBackups']);
        $group->post('/backups', [$backupController, 'createBackup']);
        $group->get('/backups/{id}/download', [$backupController, 'downloadBackup']);
        $group->post('/backups/{id}/restore', [$backupController, 'restoreBackup']);
        $group->delete('/backups/{id}', [$backupController, 'deleteBackup']);
    })->add($container->get(AuthMiddleware::class));
};
