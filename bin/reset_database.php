<?php

declare(strict_types=1);

// Set directory to root
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Doctrine\ORM\Tools\SchemaTool;

echo "====================================================\n";
echo "   🔥 DYXI DATABASE DROP & RESET TOOL              \n";
echo "====================================================\n\n";

// Require confirmation if not passed --force flag
$force = in_array('--force', $argv, true) || in_array('-f', $argv, true);

if (! $force) {
    echo "⚠️  WARNING: This operation will COMPLETELY DROP and RESET the database!\n";
    echo "All tables, rows, and data in the database will be permanently erased.\n\n";
    echo "To proceed, run with --force flag:\n";
    echo "  php bin/reset_database.php --force\n";
    exit(0);
}

echo "Bootstrapping application...\n";

try {
    $container = require 'config/container.php';
    $generalService = $container->get('general_service');
    $em = $generalService->getEm();
} catch (\Exception $e) {
    echo "Bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // 1. Drop existing database schema
    echo "\n[1/4] Dropping existing database schema...\n";
    $metadatas = $em->getMetadataFactory()->getAllMetadata();
    $schemaTool = new SchemaTool($em);

    if (! empty($metadatas)) {
        $schemaTool->dropSchema($metadatas);
        echo " ✔ Database schema dropped successfully.\n";
    } else {
        echo " ⚠️  No entity metadata found to drop.\n";
    }

    // 2. Re-create database schema from scratch
    echo "\n[2/4] Creating database schema from entity definitions...\n";
    $schemaTool->createSchema($metadatas);
    echo " ✔ Database schema created successfully.\n";

    // 3. Hydrate preset data (UserStates, Roles, Genders, WardStatuses)
    echo "\n[3/4] Hydrating preset data...\n";
    require __DIR__ . '/hydrate_preset_data.php';

    // 4. Setup default user accounts & sample ward
    echo "\n[4/5] Setting up default user accounts & sample ward...\n";
    require __DIR__ . '/setup_users.php';
    require __DIR__ . '/create_ward.php';

    // 5. Grant user access authorization in Database & Redis
    echo "\n[5/5] Granting access authorization in Database & Redis...\n";
    $argv = ['authorize_user.php', '--email=swoopfx@gmail.com', '--role=1000'];
    require __DIR__ . '/authorize_user.php';

    // Clear caches
    echo "\nClearing caches...\n";
    if (file_exists(__DIR__ . '/clear-config-cache.php')) {
        require __DIR__ . '/clear-config-cache.php';
    }

    echo "\n====================================================\n";
    echo "   🎉 DATABASE RESET COMPLETE!                       \n";
    echo "====================================================\n";
} catch (\Exception $e) {
    echo "\n❌ Database reset failed: " . $e->getMessage() . "\n";
    exit(1);
}
