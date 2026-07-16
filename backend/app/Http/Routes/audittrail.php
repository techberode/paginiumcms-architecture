<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/audittrail.php
 * Presunuté z bootstrap/routing.php (viď poznámku v codeeditor.php).
 */

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Controllers\Admin\AuditTrailController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/admin/audit', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(AuditTrailController::class);

        $group->get('/content/{contentId}', [$controller, 'getContentAudit']);
        $group->get('/user/{userId}', [$controller, 'getUserAudit']);
        $group->get('/stats', [$controller, 'getStats']);
        $group->get('/export', [$controller, 'exportAudit']);
        $group->post('/log', [$controller, 'logEvent']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
