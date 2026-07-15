<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Contact\ContactController;
use Slim\App;

return function (App $app): void {
    $container = $app->getContainer();
    $controller = $container->get(ContactController::class);

    $app->post('/api/contact', [$controller, 'submit']);
};
