<?php

declare(strict_types=1);

use PaginiumCMS\Http\Controllers\Admin\WorkflowController;
use PaginiumCMS\Http\Middleware\AuthMiddleware;
use PaginiumCMS\Http\Middleware\RoleMiddleware;
use PaginiumCMS\Http\Middleware\TwoFactorMiddleware;
use PaginiumCMS\Http\Support\RouteBootstrap;
use PaginiumCMS\Modules\Security\Contracts\AuthorizationInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    $container = RouteBootstrap::container($app);
    $controller = $container->get(WorkflowController::class);

    $app->group('/api/admin/workflows', function (RouteCollectorProxy $group) use ($controller, $container) {
        $group->post('/otp/verify', [$controller, 'verifyOtp'])
            ->add($container->get(\PaginiumCMS\Http\Middleware\OtpVerifyRateLimitMiddleware::class));
        $group->post('/otp/resend', [$controller, 'resendOtp'])
            ->add($container->get(\PaginiumCMS\Http\Middleware\OtpResendRateLimitMiddleware::class));
    })
        ->add(new RoleMiddleware($container->get(AuthorizationInterface::class), ['EDITOR', 'ADMIN', 'SUPER_ADMIN']))
        ->add($container->get(TwoFactorMiddleware::class))
        ->add($container->get(AuthMiddleware::class));
};
