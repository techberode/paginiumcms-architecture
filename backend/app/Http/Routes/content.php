<?php

declare(strict_types=1);

/**
 * Content API routes – napojené na ContentController cez DI kontajner.
 * URL kontrakt: /api/pages a /api/articles (bez /content/ prefixu).
 */

use PaginiumCMS\Http\Controllers\Content\ContentController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Support\JsonHelper;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(ContentController::class);
    $auth = $container->get(AuthMiddleware::class);

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

    // Verejné čítanie
    $app->group('/api/pages', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listPages']);
        $group->get('/{slug}', [$controller, 'getPage']);
    });

    $app->group('/api/articles', function (RouteCollectorProxy $group) use ($controller) {
        $group->get('', [$controller, 'listArticles']);
        $group->get('/{slug}', [$controller, 'getArticle']);
    });

    // Zápis vyžaduje prihlásenie
    $app->group('/api/pages', function (RouteCollectorProxy $group) use ($controller) {
        $group->post('', [$controller, 'createPage']);
        $group->put('/{slug}', [$controller, 'updatePage']);
        $group->patch('/{slug}/status', [$controller, 'updatePageStatus']);
        $group->delete('/{slug}', [$controller, 'deletePage']);
    })->add($auth);

    $app->group('/api/articles', function (RouteCollectorProxy $group) use ($controller) {
        $group->post('', [$controller, 'createArticle']);
        $group->put('/{slug}', [$controller, 'updateArticle']);
        $group->patch('/{slug}/status', [$controller, 'updateArticleStatus']);
        $group->delete('/{slug}', [$controller, 'deleteArticle']);
    })->add($auth);
};
