<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\NewsletterAdminController;
use PaginiumCMS\Http\Controllers\Newsletter\NewsletterController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $public = $container->get(NewsletterController::class);
    $admin = $container->get(NewsletterAdminController::class);
    $auth = $container->get(AuthMiddleware::class);

    $app->post('/api/newsletter/subscribe', [$public, 'subscribe']);

    $app->group('/api/admin/newsletter', function (RouteCollectorProxy $group) use ($admin) {
        $group->get('/subscribers', [$admin, 'listSubscribers']);
        $group->get('/subscribers/export', [$admin, 'exportSubscribers']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
