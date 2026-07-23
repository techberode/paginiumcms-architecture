<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\AclController;
use PaginiumCMS\Http\Controllers\Admin\SecurityAuditController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/security', function (RouteCollectorProxy $group) use ($container): void {
        $audit = $container->get(SecurityAuditController::class);

        $group->get('/audit', [$audit, 'list']);
        $group->get('/audit/export', [$audit, 'export']);
    })
        ->add(new RoleMiddleware($authz, ['ADMIN', 'SUPER_ADMIN']))
        ->add($twoFactor)
        ->add($auth);

    $app->group('/api/admin/security', function (RouteCollectorProxy $group) use ($container): void {
        $acl = $container->get(AclController::class);

        $group->get('/acl', [$acl, 'get']);
        $group->put('/acl', [$acl, 'update']);
    })
        ->add(new RoleMiddleware($authz, ['SUPER_ADMIN']))
        ->add($twoFactor)
        ->add($auth);
};
