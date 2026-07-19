<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Auth\SsoController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(SsoController::class);

    $app->get('/api/auth/sso/providers', [$controller, 'providers']);
    $app->get('/api/auth/sso/{provider}/start', [$controller, 'start']);
    $app->get('/api/auth/sso/{provider}/callback', [$controller, 'callback']);
};
