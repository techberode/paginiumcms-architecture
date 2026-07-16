<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Contact\ContactController;
use Slim\App;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(ContactController::class);

    $app->post('/api/contact', [$controller, 'submit']);
};
