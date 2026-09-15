<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\PublicApi\StaffDirectoryController;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(StaffDirectoryController::class);

    $app->get('/api/public/staff', [$controller, 'index']);
};
