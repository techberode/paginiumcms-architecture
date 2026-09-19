<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\PublicApi\StaffDirectoryController;
use PaginiumCMS\Http\Middleware\StaffChatRateLimitMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(StaffDirectoryController::class);

    $app->get('/api/public/staff', [$controller, 'index']);
    $app->post('/api/public/staff/{id}/message', [$controller, 'message'])
        ->add($container->get(StaffChatRateLimitMiddleware::class));
};
