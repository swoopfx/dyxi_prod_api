<?php
declare(strict_types=1);

// Set directory to root
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

// First, hydrate preset database data (UserStates, Roles, Genders)
echo "Ensuring database preset data is hydrated...\n";
require __DIR__ . '/hydrate_preset_data.php';

use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Authentication\Entity\Roles;
use Authentication\Service\AuthenticationService;
use Ramsey\Uuid\Uuid;

echo "\nSetting up user accounts...\n";

try {
    $container = require 'config/container.php';
    $generalService = $container->get('general_service');
    $em = $generalService->getEm();
} catch (Exception $e) {
    echo "Bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

$usersData = [
    [
        'email' => 'swoopfx@gmail.com',
        'username' => 'swoopfx@gmail.com',
    ],
    [
        'email' => 'otabayomi@gmail.com',
        'username' => 'otabayomi@gmail.com',
    ]
];

try {
    $stateEnabled = $em->find(UserState::class, AuthenticationService::USER_STATE_ENABLED);
    if (!$stateEnabled) {
        echo "Error: UserState with ID " . AuthenticationService::USER_STATE_ENABLED . " (Enabled) not found in database.\n";
        exit(1);
    }

    $roleEntity = $em->find(Roles::class, AuthenticationService::USER_ROLE_GUARDIAN);
    if (!$roleEntity) {
        $roleEntity = $em->getRepository(Roles::class)->findOneBy([]);
    }

    foreach ($usersData as $data) {
        $email = $data['email'];
        $username = $data['username'];

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        $isNew = false;
        if (!$user) {
            $user = new User();
            $isNew = true;
        }

        $user->setUsername($username)
             ->setEmail($email)
             ->setPassword(AuthenticationService::encryptPassword('Oluwaseun1'))
             ->setFullname('Kiel')
             ->setState($stateEnabled)
             ->setEmailConfirmed(true)
             ->setIsProfiled(true);

        if ($isNew) {
            $user->setUid(uniqid("resu"))
                 ->setUuid(Uuid::uuid4()->toString())
                 ->setRegistrationDate(new \DateTime())
                 ->setCreatedOn(new \DateTime());
        } else {
            $user->setUpdatedOn(new \DateTime());
        }

        $user->setRole($roleEntity);

        $em->persist($user);
        echo ($isNew ? "Created" : "Updated") . " user: $email\n";
    }

    $em->flush();
    echo "User setup completed successfully.\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
}

