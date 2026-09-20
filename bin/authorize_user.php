<?php

declare(strict_types=1);

// Set directory to root
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Authentication\Entity\Roles;
use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Authentication\Service\AuthenticationService;
use Authorization\Entity\Permission;
use Authorization\Entity\RolePermission;
use Authorization\Service\AuthorizationService;
use General\Service\RedisCacheService;
use Ramsey\Uuid\Uuid;

// Prevent session header warning output in CLI
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

echo "====================================================\n";
echo "   🔑 DYXI USER ACCESS AUTHORIZATION TOOL           \n";
echo "====================================================\n\n";

// Parse command line options & flags
$options = getopt("e:r:p::", ["email:", "role:", "password::", "help"]);

if (isset($options['help'])) {
    echo "Usage: php bin/authorize_user.php [options]\n";
    echo "Options:\n";
    echo "  -e, --email      User email address (default: swoopfx@gmail.com)\n";
    echo "  -r, --role       User role ID or Name (1000=SuperAdmin, 500=Admin, 100=Guardian, 200=Consultant; default: 1000)\n";
    echo "  -p, --password   User password (default: Oluwaseun1)\n";
    echo "      --help       Display this help message\n";
    exit(0);
}

// Extract positional arguments ignoring flags starting with '-'
$positionalArgs = array_values(array_filter(array_slice($argv, 1), fn ($arg) => ! str_starts_with((string) $arg, '-')));

$email = $options['email'] ?? $options['e'] ?? $positionalArgs[0] ?? 'swoopfx@gmail.com';
$roleInput = $options['role'] ?? $options['r'] ?? $positionalArgs[1] ?? '1000'; // Default SuperAdmin (1000)
$password = $options['password'] ?? $options['p'] ?? 'Oluwaseun1';

// 1. Bootstrap application container
echo "[1/4] Bootstrapping application container...\n";

try {
    $container = require 'config/container.php';
    $generalService = $container->get('general_service');
    $em = $generalService->getEm();
} catch (\Throwable $e) {
    echo "❌ Bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Ensure DB preset data is hydrated
echo "[2/4] Hydrating DB preset data & system permissions...\n";
require __DIR__ . '/hydrate_preset_data.php';

try {
    // Resolve target role ID
    $roleMap = [
        'guest'      => AuthenticationService::USER_ROLE_GUEST,
        'guardian'   => AuthenticationService::USER_ROLE_GUARDIAN,
        'consultant' => AuthenticationService::USER_ROLE_CONSULTANT,
        'admin'      => AuthenticationService::USER_ROLE_ADMIN,
        'superadmin' => AuthenticationService::USER_ROLE_SUPER_ADMIN,
    ];

    if (is_numeric($roleInput)) {
        $targetRoleId = (int)$roleInput;
    } else {
        $targetRoleId = $roleMap[strtolower(trim((string)$roleInput))] ?? AuthenticationService::USER_ROLE_SUPER_ADMIN;
    }

    $targetRoleName = array_search($targetRoleId, $roleMap, true) ?: 'Role_' . $targetRoleId;
    $targetRoleName = ucfirst((string)$targetRoleName);

    /** @var Roles|null $roleEntity */
    $roleEntity = $em->find(Roles::class, $targetRoleId);
    if (! $roleEntity) {
        $roleEntity = $em->getRepository(Roles::class)->findOneBy(['name' => $targetRoleName]);
    }
    if (! $roleEntity) {
        $roleEntity = new Roles();
        $roleEntity->setId($targetRoleId);
        $roleEntity->setName($targetRoleName);
        $em->persist($roleEntity);
        $em->flush();
    }

    // Check if Authorization module is activated
    $isAuthorizationActivated = false;
    if ($container->has('ModuleManager')) {
        $loadedModules = $container->get('ModuleManager')->getLoadedModules();
        $isAuthorizationActivated = isset($loadedModules['Authorization']);
    } else {
        $modulesConfig = file_exists('config/modules.config.php') ? require 'config/modules.config.php' : [];
        $isAuthorizationActivated = in_array('Authorization', $modulesConfig, true);
    }

    if ($isAuthorizationActivated) {
        // Seed system permissions in Database
        $systemPermissions = [
            '*'           => 'Wildcard full access to all system modules and endpoints',
            'ward.*'      => 'Full access to ward management operations',
            'adhd.*'      => 'Full access to ADHD evaluation module',
            'dyslexia.*'  => 'Full access to Dyslexia evaluation module',
            'dyscalculia.*' => 'Full access to Dyscalculia evaluation module',
            'wallet.*'    => 'Full access to wallet and payments module',
            'resources.*' => 'Full access to learning resources module',
            'general.*'   => 'Full access to general system APIs',
            'evaluation.*'=> 'Full access to diagnostic evaluation module',
            'game.*'      => 'Full access to gamified learning module',
        ];

        $permissionRepo = $em->getRepository(Permission::class);
        $rolePermRepo = $em->getRepository(RolePermission::class);

        foreach ($systemPermissions as $permName => $permDesc) {
            $permObj = $permissionRepo->findOneBy(['name' => $permName]);
            if (! $permObj) {
                $permObj = new Permission();
                $permObj->setName($permName);
                $permObj->setDescription($permDesc);
                $em->persist($permObj);
                $em->flush();
                echo " - Created Permission: {$permName}\n";
            }

            // Link permission to role in role_permissions table
            $rolePermObj = $rolePermRepo->findOneBy(['role' => $roleEntity, 'permission' => $permObj]);
            if (! $rolePermObj) {
                $rolePermObj = new RolePermission();
                $rolePermObj->setRole($roleEntity);
                $rolePermObj->setPermission($permObj);
                $em->persist($rolePermObj);
                echo " - Linked Permission '{$permName}' to Role '{$roleEntity->getName()}'\n";
            }
        }
        $em->flush();
    } else {
        echo " ℹ Authorization module is NOT activated. Skipping permission setting in database.\n";
    }

    // 3. Grant database user access
    echo "\n[3/4] Granting database user access for email: {$email}...\n";

    $stateEnabled = $em->find(UserState::class, AuthenticationService::USER_STATE_ENABLED);
    if (! $stateEnabled) {
        echo "Error: UserState with ID " . AuthenticationService::USER_STATE_ENABLED . " (Enabled) not found.\n";
        exit(1);
    }

    /** @var User|null $user */
    $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
    $isNew = false;

    if (! $user) {
        $user = new User();
        $isNew = true;
    }

    $user->setUsername($email)
         ->setEmail($email)
         ->setPassword(AuthenticationService::encryptPassword($password))
         ->setFullname($user->getFullname() ?: 'Swoopfx Admin')
         ->setState($stateEnabled)
         ->setEmailConfirmed(true)
         ->setIsProfiled(true)
         ->setRole($roleEntity);

    if ($isNew) {
        $user->setUid(uniqid("resu"))
             ->setUuid(Uuid::uuid4()->toString())
             ->setRegistrationDate(new \DateTime())
             ->setCreatedOn(new \DateTime());
    } else {
        $user->setUpdatedOn(new \DateTime());
    }

    $em->persist($user);
    $em->flush();

    echo " ✔ Database User updated successfully:\n";
    echo "    - User ID: " . $user->getId() . "\n";
    echo "    - Email: " . $user->getEmail() . "\n";
    echo "    - Role: " . $user->getRole()->getName() . " (ID: " . $user->getRole()->getId() . ")\n";
    echo "    - State: " . $user->getState()->getState() . " (ID: " . $user->getState()->getId() . ")\n";
    echo "    - Email Confirmed: " . ($user->getEmailConfirmed() ? 'Yes' : 'No') . "\n";
    echo "    - Is Profiled: " . ($user->getIsProfiled() ? 'Yes' : 'No') . "\n";

    // 4. Grant & cache access authorization in Redis
    echo "\n[4/4] Hydrating Redis authorization cache...\n";

    /** @var AuthorizationService|null $authService */
    $authService = null;
    if ($container->has(AuthorizationService::class)) {
        $authService = $container->get(AuthorizationService::class);
    }

    /** @var RedisCacheService|null $redisService */
    $redisService = null;
    if ($container->has('redis_cache_service')) {
        $redisService = $container->get('redis_cache_service');
    } elseif ($container->has(RedisCacheService::class)) {
        $redisService = $container->get(RedisCacheService::class);
    }

    // Determine permissions list for role
    $roleNameLower = strtolower($roleEntity->getName());
    if ($roleEntity->getId() === 1000 || $roleEntity->getId() === 500 || $roleNameLower === 'superadmin' || $roleNameLower === 'admin') {
        $permissions = ['*'];
    } else {
        $permissions = array_keys($systemPermissions);
    }

    // A. Write role permissions to Redis via AuthorizationService
    if ($authService !== null) {
        $authService->cacheRolePermissions($roleEntity->getId(), $permissions);
        echo " ✔ Redis RBAC Service cached key 'role_permissions_{$roleEntity->getId()}'.\n";
    }

    // B. Build full user authorization payload
    $authPayload = [
        'user_id'        => $user->getId(),
        'email'          => $user->getEmail(),
        'username'       => $user->getUsername(),
        'uuid'           => $user->getUuid(),
        'uid'            => $user->getUid(),
        'fullname'       => $user->getFullname(),
        'role_id'        => $roleEntity->getId(),
        'role_name'      => $roleEntity->getName(),
        'state_id'       => $user->getState()->getId(),
        'state_name'     => $user->getState()->getState(),
        'email_confirmed' => $user->getEmailConfirmed(),
        'is_profiled'     => $user->getIsProfiled(),
        'permissions'    => $permissions,
        'authorized'     => true,
        'authorized_at'  => (new \DateTime())->format(\DateTime::ATOM),
    ];

    if ($redisService !== null) {
        $redisService->set("user_authorization_{$email}", $authPayload, 86400, 'dyxi_general');
        $redisService->set("user_access_{$email}", $authPayload, 86400, 'dyxi_general');
        $redisService->set("user_permissions_{$email}", $permissions, 86400, 'dyxi_rbac');
        $redisService->set("role_permissions_{$roleEntity->getId()}", $permissions, 86400, 'dyxi_rbac');

        echo " ✔ Redis Service cached keys 'user_authorization_{$email}', 'user_access_{$email}', and 'user_permissions_{$email}'.\n";
    }

    // C. Direct Redis Client writes to ensure raw Redis keys exist
    try {
        $redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
        $redisPort = (int)(getenv('REDIS_PORT') ?: 6379);

        if (class_exists('Redis')) {
            $redis = new \Redis();
            if (@$redis->connect($redisHost, $redisPort)) {
                $payloadJson = json_encode($authPayload);
                $permsJson = json_encode($permissions);

                // Write keys into Redis
                $redis->set("dyxi_rbac:role_permissions_{$roleEntity->getId()}", $permsJson, 86400);
                $redis->set("dyxi_rbac:user_permissions_{$email}", $permsJson, 86400);
                $redis->set("dyxi_general:user_authorization_{$email}", $payloadJson, 86400);
                $redis->set("dyxi_general:user_access_{$email}", $payloadJson, 86400);
                $redis->set("role_permissions_{$roleEntity->getId()}", $permsJson, 86400);
                $redis->set("user_authorization_{$email}", $payloadJson, 86400);

                echo " ✔ Direct Redis keys populated successfully (Server: {$redisHost}:{$redisPort}).\n";
            }
        }
    } catch (\Throwable $e) {
        echo " ⚠️ Direct Redis write warning: " . $e->getMessage() . "\n";
    }

    echo "\n====================================================\n";
    echo "   🎉 ACCESS AUTHORIZATION COMPLETED SUCCESSFULLY!  \n";
    echo "====================================================\n";
    echo " User '$email' is fully authorized in Database & Redis.\n";

} catch (\Throwable $e) {
    echo "❌ Error setting authorization: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
