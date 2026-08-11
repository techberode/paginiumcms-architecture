<?php

declare(strict_types=1);

/**
 * Inbound GitHub webhooks (It.63) + admin outbound webhook registry (It.80d).
 */

use PaginiumCMS\Http\Controllers\Admin\WebhookController;
use PaginiumCMS\Http\Controllers\Webhooks\GitHubReleaseWebhookController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);

    $github = $container->get(GitHubReleaseWebhookController::class);
    $app->post('/api/webhooks/github/release', [$github, 'release']);

    $admin = $container->get(WebhookController::class);
    $auth = $container->get(AuthMiddleware::class);
    $twoFactor = $container->get(TwoFactorMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/platform/webhooks', function (RouteCollectorProxy $group) use ($admin): void {
        $group->get('', [$admin, 'index']);
        $group->post('', [$admin, 'create']);
        $group->put('/{id}', [$admin, 'update']);
        $group->delete('/{id}', [$admin, 'delete']);
        $group->post('/{id}/rotate', [$admin, 'rotate']);
        $group->post('/{id}/test', [$admin, 'test']);
        $group->get('/{id}/deliveries', [$admin, 'deliveries']);
    })
        ->add(new PermissionMiddleware($authz, 'webhooks:manage'))
        ->add($twoFactor)
        ->add($auth);
};
