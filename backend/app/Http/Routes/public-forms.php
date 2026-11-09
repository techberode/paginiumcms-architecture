<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Public\PublicFormController;
use PaginiumCMS\Http\Middleware\ContactRateLimitMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(PublicFormController::class);

    $app->get('/api/public/forms/{id}', [$controller, 'schema']);
    $app->post('/api/public/forms/{id}/submit', [$controller, 'submit'])
        ->add($container->get(ContactRateLimitMiddleware::class));
};
