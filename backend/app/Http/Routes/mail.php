<?php

declare(strict_types=1);

/**
 * Domain IMAP inbox (It.93m). Auto-discovered from bootstrap/app.php.
 *
 *  - GET  /api/admin/mail
 *  - PUT  /api/admin/mail/password
 *  - POST /api/admin/mail/accounts
 *  - DELETE /api/admin/mail/accounts
 *  - PUT  /api/admin/mail/active
 *  - GET  /api/admin/mail/folders
 *  - DELETE /api/admin/mail/folders
 *  - PATCH /api/admin/mail/messages/{uid}/flags
 *  - POST /api/admin/mail/messages/{uid}/hide
 *  - POST /api/admin/mail/messages/{uid}/unhide
 *  - GET  /api/admin/mail/messages
 *  - GET  /api/admin/mail/messages/{uid}
 *  - PUT  /api/admin/mail/messages/{uid}/tags
 *  - POST /api/admin/mail/send
 *  - POST /api/admin/mail/messages/{uid}/spam
 */

use PaginiumCMS\Http\Controllers\Admin\MailController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\PermissionMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use PaginiumCMS\Http\Support\RouteBootstrap;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $authz = $container->get(AuthorizationInterface::class);

    $app->group('/api/admin/mail', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(MailController::class);

        $group->get('', [$controller, 'status']);
        $group->put('/password', [$controller, 'savePassword']);
        $group->post('/accounts', [$controller, 'addAccount']);
        $group->delete('/accounts', [$controller, 'removeAccount']);
        $group->put('/active', [$controller, 'selectAccount']);
        $group->get('/folders', [$controller, 'folders']);
        $group->post('/folders', [$controller, 'createFolder']);
        $group->delete('/folders', [$controller, 'deleteFolder']);
        $group->get('/messages', [$controller, 'messages']);
        $group->get('/messages/{uid}', [$controller, 'message']);
        $group->put('/messages/{uid}/tags', [$controller, 'tag']);
        $group->patch('/messages/{uid}/flags', [$controller, 'flags']);
        $group->post('/messages/{uid}/hide', [$controller, 'hide']);
        $group->post('/messages/{uid}/unhide', [$controller, 'unhide']);
        $group->post('/send', [$controller, 'send']);
        $group->post('/messages/{uid}/spam', [$controller, 'spam']);
    })
        ->add(new PermissionMiddleware($authz, 'mail:read-own'))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
