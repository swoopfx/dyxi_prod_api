<?php

declare(strict_types=1);

use Laminas\Mvc\Application;

chdir(__DIR__ . '/../');

require 'vendor/autoload.php';

// Load .env variables into environment if present
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!getenv($name)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

$appConfig = include 'config/application.config.php';

// Enable config caching and module map caching to generate fresh cache files
$appConfig['module_listener_options']['config_cache_enabled'] = true;
$appConfig['module_listener_options']['module_map_cache_enabled'] = true;

// Initialize Laminas Application to compile and write the merged configuration cache
Application::init($appConfig);

echo "Successfully built and packaged fresh configuration cache in data/cache/" . PHP_EOL;
