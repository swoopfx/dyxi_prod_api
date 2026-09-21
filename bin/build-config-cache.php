<?php

declare(strict_types=1);

use Laminas\Mvc\Application;

chdir(__DIR__ . '/../');

require 'vendor/autoload.php';

$appConfig = include 'config/application.config.php';

// Enable config caching and module map caching to generate fresh cache files
$appConfig['module_listener_options']['config_cache_enabled'] = true;
$appConfig['module_listener_options']['module_map_cache_enabled'] = true;

// Initialize Laminas Application to compile and write the merged configuration cache
Application::init($appConfig);

echo "Successfully built and packaged fresh configuration cache in data/cache/" . PHP_EOL;
