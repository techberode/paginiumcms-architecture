<?php

declare(strict_types=1);

/**
 * Reference extension route (Wave 5d). Loaded only when hello-widget is enabled.
 */

use PaginiumCMS\Support\JsonHelper;
use Slim\App;

return function (App $app): void {
    $app->get('/api/extensions/hello-widget/ping', function ($request, $response) {
        $payload = JsonHelper::encode([
            'success' => true,
            'message' => 'pong',
            'extension' => 'hello-widget',
        ]);

        $response->getBody()->write($payload);

        return $response->withHeader('Content-Type', 'application/json');
    });
};
