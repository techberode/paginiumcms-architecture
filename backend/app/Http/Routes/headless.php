<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\SettingsController;
use PaginiumCMS\Http\Controllers\Content\ContentController;
use PaginiumCMS\Http\Controllers\Headless\HeadlessTokenController;
use PaginiumCMS\Http\Middleware\ApiKeyRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\ApiScopeMiddleware;
use PaginiumCMS\Http\Middleware\BearerAuthMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/**
 * Headless API — scoped Bearer API key or short-lived JWT (It.74).
 * Classic public /api/pages remains unchanged for anonymous clients.
 */
return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $content = $container->get(ContentController::class);
    $settings = $container->get(SettingsController::class);
    $tokenController = $container->get(HeadlessTokenController::class);
    $bearer = $container->get(BearerAuthMiddleware::class);
    $scope = $container->get(ApiScopeMiddleware::class);
    $rateLimit = $container->get(ApiKeyRateLimitMiddleware::class);

    $app->group('/api/headless', function (RouteCollectorProxy $group) use ($content, $settings, $tokenController): void {
        $group->get('/pages', [$content, 'listPages']);
        $group->get('/pages/{slug}', [$content, 'getPage']);
        $group->post('/pages', [$content, 'createPage']);
        $group->put('/pages/{slug}', [$content, 'updatePage']);
        $group->get('/articles', [$content, 'listArticles']);
        $group->get('/articles/{slug}', [$content, 'getArticle']);
        $group->post('/articles', [$content, 'createArticle']);
        $group->put('/articles/{slug}', [$content, 'updateArticle']);
        $group->get('/settings/public', [$settings, 'publicSettings']);
        $group->post('/token', [$tokenController, 'issue']);
    })
        ->add($rateLimit)
        ->add($scope)
        ->add($bearer);
};
