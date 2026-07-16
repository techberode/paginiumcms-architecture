<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Storage\StorageController;
use Slim\App;

return function (App $app): void {
    $storageRoot = realpath(__DIR__ . '/../../../storage') ?: __DIR__ . '/../../../storage';
    $controller = new StorageController($storageRoot);

    $app->get('/storage/{path:.*}', [$controller, 'serve']);
};
