<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\NewsletterAdminController;
use PaginiumCMS\Http\Controllers\Newsletter\NewsletterController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\NewsletterSubscribeRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\NewsletterTokenRateLimitMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $public = $container->get(NewsletterController::class);
    $admin = $container->get(NewsletterAdminController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);
    $tokenRateLimit = $container->get(NewsletterTokenRateLimitMiddleware::class);

    $app->post('/api/newsletter/subscribe', [$public, 'subscribe'])
        ->add($container->get(NewsletterSubscribeRateLimitMiddleware::class));

    $app->get('/api/newsletter/confirm', [$public, 'confirm'])
        ->add($tokenRateLimit);

    $app->get('/api/newsletter/unsubscribe', [$public, 'unsubscribe'])
        ->add($tokenRateLimit);

    $app->get('/api/newsletter/manage', [$public, 'manageGet'])
        ->add($tokenRateLimit);

    $app->post('/api/newsletter/manage', [$public, 'manageUpdate'])
        ->add($tokenRateLimit);

    $app->group('/api/admin/newsletter', function (RouteCollectorProxy $group) use ($admin) {
        $group->get('/subscribers', [$admin, 'listSubscribers']);
        $group->get('/subscribers/export', [$admin, 'exportSubscribers']);
        $group->post('/subscribers/bulk-unsubscribe', [$admin, 'bulkUnsubscribe']);
        $group->post('/subscribers/bulk-delete', [$admin, 'bulkDelete']);
        $group->post('/subscribers/{id}/unsubscribe', [$admin, 'unsubscribeSubscriber']);
        $group->delete('/subscribers/{id}', [$admin, 'deleteSubscriber']);
        $group->get('/send/status', [$admin, 'sendStatus']);
    })->add(new RoleMiddleware($authz, ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);

    $app->group('/api/admin/newsletter/send', function (RouteCollectorProxy $group) use ($admin) {
        $group->post('/weekly-digest', [$admin, 'sendWeeklyDigestNow']);
        $group->post('/test', [$admin, 'sendTest']);
        $group->post('/cms-release', [$admin, 'sendCmsRelease']);
    })->add(new RoleMiddleware($authz, ['SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
