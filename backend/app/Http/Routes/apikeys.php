<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\ApiKeyController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(ApiKeyController::class);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/platform/api-keys', function (RouteCollectorProxy $group) use ($controller): void {
        $group->get('', [$controller, 'index']);
        $group->get('/audit', [$controller, 'audit']);
        $group->post('', [$controller, 'create']);
        $group->post('/token', [$controller, 'issueToken']);
        $group->post('/{id}/rotate', [$controller, 'rotate']);
        $group->delete('/{id}', [$controller, 'revoke']);
    })
        ->add(new PermissionMiddleware($authz, 'api-keys:manage'))
        ->add($twoFactor)
        ->add($auth);
};
