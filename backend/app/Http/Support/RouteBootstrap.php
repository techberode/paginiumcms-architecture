<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use Psr\Container\ContainerInterface;
use Slim\App;

final class RouteBootstrap
{
    /**
     * @param App<ContainerInterface> $app
     */
    public static function container(App $app): ContainerInterface
    {
        $container = $app->getContainer();

        return $container;
    }
}
