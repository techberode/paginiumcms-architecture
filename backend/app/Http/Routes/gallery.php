<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Gallery\GalleryAdminController;
use PaginiumCMS\Http\Controllers\Gallery\GalleryPublicController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Support\RouteBootstrap;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $admin = $container->get(GalleryAdminController::class);
    $public = $container->get(GalleryPublicController::class);
    $auth = $container->get(AuthMiddleware::class);
    $authz = $container->get(AuthorizationInterface::class);

    $app->get('/api/gallery/public', [$public, 'listPublished']);

    $app->group('/api/admin/gallery', function (RouteCollectorProxy $group) use ($admin) {
        $group->get('', [$admin, 'list']);
        $group->get('/export', [$admin, 'export']);
        $group->post('/import', [$admin, 'import']);
        $group->post('', [$admin, 'create']);
        $group->post('/bulk-delete', [$admin, 'bulkDelete']);
        $group->put('/reorder', [$admin, 'reorder']);
        $group->put('/{id}', [$admin, 'update']);
        $group->delete('/{id}', [$admin, 'delete']);
    })
        ->add(new PermissionMiddleware($authz, 'gallery:manage'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($auth);
};
