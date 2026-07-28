<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Maintenance\MaintenanceController;
use PaginiumCMS\Http\Middleware\NewsletterSubscribeRateLimitMiddleware;
use Slim\App;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(MaintenanceController::class);

    $app->post('/api/maintenance/newsletter', [$controller, 'subscribe'])
        ->add($container->get(NewsletterSubscribeRateLimitMiddleware::class));
    $app->post('/api/maintenance/message', [$controller, 'sendMessage']);
};
