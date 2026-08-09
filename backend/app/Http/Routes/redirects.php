<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\RedirectController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(RedirectController::class);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->get('/api/public/redirect-resolve', [$controller, 'resolve']);

    $app->group('/api/admin/platform/redirects', function (RouteCollectorProxy $group) use ($controller): void {
        $group->get('', [$controller, 'index']);
        $group->post('', [$controller, 'create']);
        $group->put('/{id}', [$controller, 'update']);
        $group->delete('/{id}', [$controller, 'delete']);
    })
        ->add(new PermissionMiddleware($authz, 'redirects:manage'))
        ->add($twoFactor)
        ->add($auth);
};
