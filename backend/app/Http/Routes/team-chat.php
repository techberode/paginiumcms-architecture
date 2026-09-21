<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\TeamChatController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\TeamChatRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $app->group('/api/team-chat', function (RouteCollectorProxy $group) use ($container): void {
        $controller = $container->get(TeamChatController::class);
        $group->get('/inbox', [$controller, 'inbox']);
        $group->get('', [$controller, 'rooms']);
        $group->get('/{teamId}/search', [$controller, 'search']);
        $group->get('/{teamId}/export', [$controller, 'exportArchive']);
        $group->post('/{teamId}/import', [$controller, 'importArchive']);
        $group->post('/{teamId}/clear-history', [$controller, 'clearHistory']);
        $group->get('/{teamId}', [$controller, 'messages']);
        $group->post('/{teamId}', [$controller, 'post'])
            ->add($container->get(TeamChatRateLimitMiddleware::class));
        $group->post('/{teamId}/files', [$controller, 'upload'])
            ->add($container->get(TeamChatRateLimitMiddleware::class));
        $group->get('/{teamId}/files/{fileId}', [$controller, 'download']);
    })
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
