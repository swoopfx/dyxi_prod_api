<?php

use Laminas\Mvc\Application;
use Laminas\Stdlib\ArrayUtils;

// Load .env variables into environment if present
if (file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

// Retrieve configuration
$appConfig = require __DIR__ . '/application.config.php';
if (file_exists(__DIR__ . '/development.config.php')) {
    /** @var array $devConfig */
    $devConfig = require __DIR__ . '/development.config.php';
    $appConfig = ArrayUtils::merge($appConfig, $devConfig);
}

return Application::init($appConfig)
    ->getServiceManager();
