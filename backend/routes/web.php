<?php

use Slim\App;
use App\Presentation\Controllers\{
    HomeController,
    AuthController,
    AdminController
};

return function (App $app) {
    // Verejné routy
    $app->get('/', [HomeController::class, 'index'])->setName('home');
    $app->get('/registracia', [AuthController::class, 'showRegister'])->setName('register');
    $app->post('/registracia', [AuthController::class, 'register']);
    $app->get('/prihlasenie', [AuthController::class, 'showLogin'])->setName('login');
    $app->post('/prihlasenie', [AuthController::class, 'login']);
    $app->post('/odhlasenie', [AuthController::class, 'logout'])->setName('logout');

    // Chránené routy (admin)
    $app->group('/admin', function ($group) {
        $group->get('', [AdminController::class, 'dashboard'])->setName('admin.dashboard');
        $group->get('/stranky', [AdminController::class, 'pages'])->setName('admin.pages');
        $group->get('/stranky/pridat', [AdminController::class, 'createPage'])->setName('admin.page.create');
        $group->post('/stranky/pridat', [AdminController::class, 'storePage']);
        $group->get('/stranky/{id}/upravit', [AdminController::class, 'editPage'])->setName('admin.page.edit');
        $group->post('/stranky/{id}/upravit', [AdminController::class, 'updatePage']);
        $group->delete('/stranky/{id}', [AdminController::class, 'deletePage'])->setName('admin.page.delete');
    })->add(\App\Presentation\Middleware\AuthMiddleware::class);
};
