<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\FirewallController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(FirewallController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/firewall', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('/stats', [$controller, 'stats']);
        $group->get('/incidents', [$controller, 'incidents']);
        $group->get('/bans', [$controller, 'bans']);
        $group->post('/bans', [$controller, 'createBan']);
        $group->delete('/bans/{ip}', [$controller, 'deleteBan']);
        $group->get('/whitelist', [$controller, 'whitelist']);
        $group->post('/whitelist', [$controller, 'addWhitelist']);
        $group->delete('/whitelist/{ip}', [$controller, 'removeWhitelist']);
    })
        ->add(new RoleMiddleware($authz, ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
