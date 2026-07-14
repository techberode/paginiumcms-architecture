<?php
require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;

try {
    $builder = new ContainerBuilder();
    $builder->addDefinitions(__DIR__ . '/../app/Bootstrap/container.php');
    $container = $builder->build();
    echo "✅ DI Container built successfully\n";
} catch (Exception $e) {
    echo "❌ DI Container failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
