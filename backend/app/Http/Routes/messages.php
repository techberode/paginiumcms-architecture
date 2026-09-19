<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\MessageController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(MessageController::class);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);

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
    })->add($twoFactor)->add($auth);
};
