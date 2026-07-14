<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Media\MediaController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = $app->getContainer();
    $controller = $container->get(MediaController::class);
    $auth = $container->get(AuthMiddleware::class);

    $app->group('/api/media', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listMedia']);
        $group->post('/upload', [$controller, 'uploadMedia']);
        $group->patch('/{path:.+}', [$controller, 'updateMedia']);
        $group->delete('/{path:.+}', [$controller, 'deleteMedia']);
    })->add($auth);
};
