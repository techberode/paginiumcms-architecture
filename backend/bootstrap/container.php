<?php

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        'config' => function () {
            return [
                'app' => require __DIR__ . '/../config/app.php',
                'storage' => require __DIR__ . '/../config/database.php',
                'security' => require __DIR__ . '/../config/security.php',
                'template' => require __DIR__ . '/../config/template.php',
            ];
        },
    ]);
};
