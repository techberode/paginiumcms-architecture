<?php

declare(strict_types=1);

/**
 * backend/app/Http/Routes/codeeditor.php
 *
 * OPRAVA (audit 12.7.2026): pôvodne v bootstrap/routing.php, ktorý sa
 * NIKDY nenačítaval (public/index.php ho nevolal) - táto funkcia teda
 * bola úplne nedosiahnuteľná. Naviac obsahovala:
 *   - fatálnu syntax chybu `/ Code Editor Routes` (chýbajúce druhé `/`)
 *   - chýbajúci `use ... AuthorizationInterface;` import - odkaz na
 *     AuthorizationInterface by spadol s "Class not found"
 * Obe opravené. Súbor je teraz auto-discovered z bootstrap/app.php.
 *
 * POZOR: samotná CodeEditorManager (Core/CodeEditor/Services) mala
 * kritickú path-traversal zraniteľnosť - opravené samostatne v
 * CodeEditorManager.php. Táto routes-vrstva je iba wiring, žiadna
 * bezpečnostná logika tu nie je (a ani nemá byť - to patrí do Manageru).
 */

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use PaginiumCMS\Http\Controllers\Admin\CodeEditorController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;

return function (App $app): void {
    $container = $app->getContainer();

    $app->group('/api/admin/code-editor', function (RouteCollectorProxy $group) use ($container) {
        $controller = $container->get(CodeEditorController::class);

        $group->get('/files', [$controller, 'listFiles']);
        $group->get('/file', [$controller, 'getFile']);
        $group->post('/save', [$controller, 'saveFile']);
        $group->get('/backups', [$controller, 'getBackups']);
    })->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
