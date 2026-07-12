<?php
require_once __DIR__ . '/../vendor/autoload.php';
echo "Autoloader loaded successfully\n";

// Skúsiť načítať triedu
if (class_exists('Slim\App')) {
    echo "✅ Slim App class exists\n";
} else {
    echo "❌ Slim App class not found\n";
}
