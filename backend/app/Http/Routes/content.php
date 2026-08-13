<?php

declare(strict_types=1);

/**
 * Content API routes – napojené na ContentController cez DI kontajner.
 * URL kontrakt: /api/pages a /api/articles (bez /content/ prefixu).
 */

use PaginiumCMS\Http\Controllers\Content\ContentController;
use PaginiumCMS\Http\Controllers\Content\ContentMetaController;
use PaginiumCMS\Http\Controllers\Content\SearchController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Support\JsonHelper;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(ContentController::class);
    $metaController = $container->get(ContentMetaController::class);
    $searchController = $container->get(SearchController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->get('/api/test', function ($request, $response) {
        $response->getBody()->write(JsonHelper::encode([
            'success' => true,
            'data' => [
                'status' => 'ok',
                'message' => 'API beží!',
                'timestamp' => date('Y-m-d H:i:s'),
            ],
        ], JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/search', [$searchController, 'search']);

    // Verejné čítanie
    $app->group('/api/pages', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listPages']);
        $group->get('/{slug}', [$controller, 'getPage']);
    });

    $app->group('/api/articles', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listArticles']);
        $group->get('/{slug}', [$controller, 'getArticle']);
    });

    // Zápis vyžaduje prihlásenie + RBAC oprávnenia
    $app->group('/api/pages', function (RouteCollectorProxy $group) use ($controller) {
        $group->post('', [$controller, 'createPage']);
    })
        ->add(new PermissionMiddleware($authz, 'content:create'))
        ->add($auth);

    $app->group('/api/pages', function (RouteCollectorProxy $group) use ($controller) {
        $group->put('/{slug}', [$controller, 'updatePage']);
        $group->patch('/{slug}/status', [$controller, 'updatePageStatus']);
    })
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);

    $app->group('/api/pages', function (RouteCollectorProxy $group) use ($controller) {
        $group->delete('/{slug}', [$controller, 'deletePage']);
        $group->post('/bulk-delete', [$controller, 'bulkDeletePages']);
    })
        ->add(new PermissionMiddleware($authz, 'content:delete'))
        ->add($auth);

    $app->group('/api/pages', function (RouteCollectorProxy $group) use ($controller) {
        $group->patch('/bulk-status', [$controller, 'bulkUpdatePageStatus']);
    })
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);

    $app->group('/api/articles', function (RouteCollectorProxy $group) use ($controller) {
        $group->post('', [$controller, 'createArticle']);
    })
        ->add(new PermissionMiddleware($authz, 'content:create'))
        ->add($auth);

    $app->group('/api/articles', function (RouteCollectorProxy $group) use ($controller) {
        $group->put('/{slug}', [$controller, 'updateArticle']);
        $group->patch('/{slug}/status', [$controller, 'updateArticleStatus']);
    })
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);

    $app->group('/api/articles', function (RouteCollectorProxy $group) use ($controller) {
        $group->delete('/{slug}', [$controller, 'deleteArticle']);
        $group->post('/bulk-delete', [$controller, 'bulkDeleteArticles']);
    })
        ->add(new PermissionMiddleware($authz, 'content:delete'))
        ->add($auth);

    $app->group('/api/articles', function (RouteCollectorProxy $group) use ($controller) {
        $group->patch('/bulk-status', [$controller, 'bulkUpdateArticleStatus']);
    })
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);

    $app->post('/api/admin/content/suggest-meta', [$metaController, 'suggestMeta'])
        ->add($container->get(\PaginiumCMS\Http\Middleware\ContentSuggestMetaRateLimitMiddleware::class))
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);

    $app->post('/api/admin/content/render-preview', [$metaController, 'renderPreview'])
        ->add($container->get(\PaginiumCMS\Http\Middleware\ContentSuggestMetaRateLimitMiddleware::class))
        ->add(new PermissionMiddleware($authz, 'content:edit'))
        ->add($auth);
};
