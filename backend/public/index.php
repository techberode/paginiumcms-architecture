<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Správne cesty
$backendPath = __DIR__ . '/../';
$rootPath = __DIR__ . '/../../';
$vendorAutoload = $rootPath . 'vendor/autoload.php';
$envPath = $backendPath;

$logFile = $backendPath . 'logs/app.log';
file_put_contents($logFile, "=== APP START ===\n", FILE_APPEND);
file_put_contents($logFile, "Backend path: {$backendPath}\n", FILE_APPEND);
file_put_contents($logFile, "ENV path: {$envPath}\n", FILE_APPEND);

try {
    // 1. Autoloader
    if (!file_exists($vendorAutoload)) {
        throw new Exception("Autoloader not found at: {$vendorAutoload}");
    }
    require_once $vendorAutoload;
    file_put_contents($logFile, "✅ Autoloader loaded\n", FILE_APPEND);

    // 2. .env
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable($envPath);
        $dotenv->load();
        file_put_contents($logFile, "✅ .env loaded from: {$envPath}\n", FILE_APPEND);
    }

    // 3. DI Container
    $containerBuilder = new \DI\ContainerBuilder();

    $containerFile = $backendPath . 'bootstrap/container.php';
    if (file_exists($containerFile)) {
        $definitions = require $containerFile;
        if (is_callable($definitions)) {
            $definitions($containerBuilder);
        }
        file_put_contents($logFile, "✅ Container definitions loaded from bootstrap/container.php\n", FILE_APPEND);
    } else {
        throw new Exception("bootstrap/container.php not found at: {$containerFile}");
    }

    $container = $containerBuilder->build();
    file_put_contents($logFile, "✅ DI container built\n", FILE_APPEND);

    // 4. Slim app
    \Slim\Factory\AppFactory::setContainer($container);
    $app = \Slim\Factory\AppFactory::create();
    file_put_contents($logFile, "✅ Slim app created\n", FILE_APPEND);

    // 5. Routes
    $routesFile = $backendPath . 'bootstrap/routes.php';
    if (file_exists($routesFile)) {
        $routes = require $routesFile;
        if (is_callable($routes)) {
            $routes($app);
        }
        file_put_contents($logFile, "✅ Routes loaded from bootstrap/routes.php\n", FILE_APPEND);
    }

    // 6. Middleware (ak existuje)
    $middlewareFile = $backendPath . 'bootstrap/middleware.php';
    if (file_exists($middlewareFile)) {
        $middleware = require $middlewareFile;
        if (is_callable($middleware)) {
            $middleware($app);
        }
        file_put_contents($logFile, "✅ Middleware loaded\n", FILE_APPEND);
    }

    file_put_contents($logFile, "=== APP RUNNING ===\n", FILE_APPEND);
    $app->run();

} catch (\Exception $e) {
    file_put_contents($logFile, "❌ ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($logFile, "File: " . $e->getFile() . ":" . $e->getLine() . "\n", FILE_APPEND);
    file_put_contents($logFile, "Trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);

    echo "Error: " . $e->getMessage();
}
//<?php
//
//declare(strict_types=1);
//
///**
//* backend/public/index.php
// *
// * OPRAVA (audit 12.7.2026, nález #1 - KRITICKÝ):
// * Tento súbor predtým obsahoval kompletne samostatnú "mock" Slim aplikáciu
// * s natvrdo zapísanými odpoveďami - /api/auth/login akceptoval ĽUBOVOĽNÝ
// * email/heslo a vracal 'roles' => ['ADMIN'], /api/auth/me akceptoval
// * ľubovoľný token. Skutočná aplikácia (bootstrap/app.php - reálna
// * autentifikácia s Argon2id, ContentRepository, atď.) sa vôbec nevolala.
// *
// * Tento súbor teraz robí presne jednu vec: načíta skutočnú aplikáciu a
// * spustí ju. Žiadna logika, žiadne mock dáta, žiadne CORS hlavičky natvrdo
// * (CORS rieši CorsMiddleware v bootstrap/app.php, nie tento súbor -
// * duplicitné CORS hlavičky z dvoch miest môžu spôsobiť konflikty hlavičiek).
// */
//
//require __DIR__ . '/../../vendor/autoload.php';
//
//$app = require __DIR__ . '/../bootstrap/app.php';
//$app->run();
