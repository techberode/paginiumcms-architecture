<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/analytics.php
 * Admin analytics reports (Iteration 6) + public SPA pageview beacon.
 */

use PaginiumCMS\Http\Controllers\Admin\AnalyticsController;
use PaginiumCMS\Http\Controllers\Analytics\AnalyticsPageviewController;
use PaginiumCMS\Http\Middleware\AnalyticsPageviewRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $pageview = $container->get(AnalyticsPageviewController::class);
    $app->post('/api/analytics/pageview', [$pageview, 'track'])
        ->add($container->get(AnalyticsPageviewRateLimitMiddleware::class));

    $app->group('/api/admin/analytics', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(AnalyticsController::class);

        $group->get('/overview', [$controller, 'overview']);
        $group->get('/chart', [$controller, 'chart']);
        $group->get('/realtime', [$controller, 'realtime']);

        $notFound = $container->get(\PaginiumCMS\Http\Controllers\Admin\NotFoundReportController::class);
        $group->get('/not-found', [$notFound, 'index']);
        $group->get('/not-found/export.csv', [$notFound, 'export']);
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
