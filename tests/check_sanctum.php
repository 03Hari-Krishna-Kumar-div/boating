<?php
require __DIR__ . '/../vendor/autoload.php';

$file = __DIR__ . '/../vendor/composer/autoload_classmap.php';
if (file_exists($file)) {
    $map = require $file;
    foreach ($map as $class => $path) {
        if (strpos($class, 'Sanctum') !== false) {
            echo "$class => $path\n";
        }
    }
} else {
    echo "No classmap file found\n";
    // Check PSR-4 autoload
    $psr4 = require __DIR__ . '/../vendor/composer/autoload_psr4.php';
    foreach ($psr4 as $prefix => $dirs) {
        if (strpos($prefix, 'Sanctum') !== false) {
            echo "$prefix => " . implode(', ', $dirs) . "\n";
        }
    }
}

echo "\nClass exists check:\n";
echo "EnsureFrontendRequestsAreStateful: " . (class_exists('Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful') ? 'YES' : 'NO') . "\n";
echo "Sanctum: " . (class_exists('Laravel\Sanctum\Sanctum') ? 'YES' : 'NO') . "\n";
echo "SanctumServiceProvider: " . (class_exists('Laravel\Sanctum\SanctumServiceProvider') ? 'YES' : 'NO') . "\n";
