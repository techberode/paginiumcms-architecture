<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\MessageController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(MessageController::class);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/messages', function (RouteCollectorProxy $group) use ($controller): void {
        $group->get('', [$controller, 'listMessages']);
        $group->get('/routing', [$controller, 'routing']);
        $group->put('/routing', [$controller, 'saveRouting']);
        $group->post('/bulk', [$controller, 'bulkAction']);
        $group->post('/{id}/claim', [$controller, 'claim']);
        $group->post('/{id}/release', [$controller, 'release']);
        $group->post('/{id}/replies', [$controller, 'reply']);
        $group->patch('/{id}', [$controller, 'updateMessage']);
        $group->delete('/{id}', [$controller, 'deleteMessage']);
    })
        ->add(new RoleMiddleware($authz, ['EDITOR', 'ADMIN', 'SUPER_ADMIN']))
        ->add($twoFactor)
        ->add($auth);
};
