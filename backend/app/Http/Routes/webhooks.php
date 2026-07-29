<?php

declare(strict_types=1);

/**
 * Inbound webhooks (Iteration 63 v3).
 */

use PaginiumCMS\Http\Controllers\Webhooks\GitHubReleaseWebhookController;
use Slim\App;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(GitHubReleaseWebhookController::class);

    $app->post('/api/webhooks/github/release', [$controller, 'release']);
};
