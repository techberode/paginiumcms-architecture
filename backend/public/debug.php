<?php
// === JEDNODUCHÝ DEBUG ===

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Správne cesty
$backendPath = __DIR__ . '/../';  // backend/
$rootPath = __DIR__ . '/../../';  // koreň projektu
$vendorAutoload = $rootPath . 'vendor/autoload.php';
$envPath = $backendPath;          // .env je v backend/

$logFile = $backendPath . 'logs/debug.log';

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}

writeLog('=== DEBUG START ===');
writeLog('Backend path: ' . $backendPath);
writeLog('Root path: ' . $rootPath);
writeLog('ENV path: ' . $envPath);
writeLog('Vendor autoload: ' . $vendorAutoload);

// Test 1: PHP funguje
writeLog('Test 1: PHP works');
echo "✅ PHP works\n";

// Test 2: Kontrola súborov
writeLog('Test 2: Checking files');
$files = [
    $vendorAutoload,
$envPath . '.env',
$backendPath . 'bootstrap/container.php',
$backendPath . 'bootstrap/routes.php',
$backendPath . 'config/app.php',
];

foreach ($files as $file) {
    $exists = file_exists($file) ? '✅' : '❌';
    writeLog("  {$exists} {$file}");
    echo "  {$exists} " . basename($file) . "\n";
}

// Test 3: Načítať autoloader
writeLog('Test 3: Loading autoloader');
try {
    if (!file_exists($vendorAutoload)) {
        throw new Exception("Autoloader not found at: {$vendorAutoload}");
    }
    require_once $vendorAutoload;
    writeLog('✅ Autoloader loaded');
    echo "✅ Autoloader loaded\n";
} catch (Exception $e) {
    writeLog('❌ Autoloader failed: ' . $e->getMessage());
    echo "❌ Autoloader failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 4: Načítať .env
writeLog('Test 4: Loading .env');
try {
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createUnsafeImmutable($envPath);
        $dotenv->load();
        writeLog('✅ .env loaded from: ' . $envPath);
        echo "✅ .env loaded\n";
    } else {
        writeLog('❌ Dotenv class not found');
        echo "❌ Dotenv class not found\n";
    }
} catch (Exception $e) {
    writeLog('❌ .env failed: ' . $e->getMessage());
    echo "❌ .env failed: " . $e->getMessage() . "\n";
}

// Test 5: Skúsiť vytvoriť DI container
writeLog('Test 5: Building DI container');
try {
    $containerBuilder = new \DI\ContainerBuilder();

    $containerFile = $backendPath . 'bootstrap/container.php';
    if (file_exists($containerFile)) {
        $definitions = require $containerFile;
        if (is_callable($definitions)) {
            $definitions($containerBuilder);
        }
        writeLog('✅ Container definitions loaded');
        echo "✅ Container definitions loaded\n";
    } else {
        writeLog('❌ Container file not found: ' . $containerFile);
        echo "❌ Container file not found\n";
    }

    $container = $containerBuilder->build();
    writeLog('✅ DI container built');
    echo "✅ DI container built\n";

} catch (Exception $e) {
    writeLog('❌ DI container failed: ' . $e->getMessage());
    writeLog('Stack trace: ' . $e->getTraceAsString());
    echo "❌ DI container failed: " . $e->getMessage() . "\n";
}

writeLog('=== DEBUG END ===');
echo "\n✅ Debug complete. Check logs/debug.log\n";
