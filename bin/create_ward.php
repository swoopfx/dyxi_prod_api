<?php

declare(strict_types=1);

// Set directory to root
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Authentication\Entity\Roles;
use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Authentication\Service\AuthenticationService;
use Ramsey\Uuid\Uuid;
use Ward\Entity\Ward;
use Ward\Entity\WardStatus;

echo "Bootstrapping application to create Ward for user swoopfx@gmail.com...\n";

try {
    $container = require 'config/container.php';
    $generalService = $container->get('general_service');
    $em = $generalService->getEm();
} catch (\Exception $e) {
    echo "Bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

// Get positional CLI arguments (ignoring flags starting with -)
$positionalArgs = array_values(array_filter(array_slice($argv, 1), fn ($arg) => ! str_starts_with((string) $arg, '-')));

$email = $positionalArgs[0] ?? 'swoopfx@gmail.com';
$fullname = $positionalArgs[1] ?? 'Ward Swoopfx';
$dobString = $positionalArgs[2] ?? '2015-08-15';
$statusName = $positionalArgs[3] ?? WardStatus::STATUS_ACTIVE;

try {
    // 1. Find target user
    $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

    if (! $user) {
        echo "User with email '$email' not found in database.\n";
        echo "Creating user account for '$email'...\n";

        $stateEnabled = $em->find(UserState::class, AuthenticationService::USER_STATE_ENABLED);
        $roleEntity = $em->find(Roles::class, AuthenticationService::USER_ROLE_GUARDIAN);

        $user = new User();
        $user->setUsername($email)
             ->setEmail($email)
             ->setPassword(AuthenticationService::encryptPassword('Oluwaseun1'))
             ->setFullname('Swoopfx User')
             ->setState($stateEnabled)
             ->setEmailConfirmed(true)
             ->setIsProfiled(true)
             ->setUid(uniqid("resu"))
             ->setUuid(Uuid::uuid4()->toString())
             ->setRegistrationDate(new \DateTime())
             ->setCreatedOn(new \DateTime());

        if ($roleEntity) {
            $user->setRole($roleEntity);
        }

        $em->persist($user);
        $em->flush();
        echo "Created user account: $email\n";
    }

    // 2. Validate Date of Birth
    $dob = \DateTime::createFromFormat('Y-m-d', $dobString);
    if (! $dob || $dob->format('Y-m-d') !== $dobString) {
        echo "Error: Invalid date of birth format '$dobString'. Use YYYY-MM-DD.\n";
        exit(1);
    }

    // 3. Resolve Ward Status
    $statusEntity = $em->getRepository(WardStatus::class)->findOneBy(['status' => strtolower($statusName)]);
    if (! $statusEntity) {
        $statusEntity = $em->find(WardStatus::class, WardStatus::STATUS_ACTIVE_ID);
    }

    // 4. Create Ward
    $ward = new Ward();
    $uuid = Uuid::uuid4()->toString();

    $ward->setFullname($fullname)
         ->setDateOfBirth($dob)
         ->setUuid($uuid)
         ->setUser($user);

    if ($statusEntity) {
        $ward->setStatus($statusEntity);
    }

    $em->persist($ward);
    $em->flush();

    echo "\nWard created successfully!\n";
    echo " - Ward ID: " . $ward->getId() . "\n";
    echo " - Fullname: " . $ward->getFullname() . "\n";
    echo " - Date of Birth: " . $ward->getDateOfBirth()->format('Y-m-d') . "\n";
    echo " - UUID: " . $ward->getUuid() . "\n";
    echo " - Status: " . ($ward->getStatus() ? $ward->getStatus()->getStatus() : 'N/A') . "\n";
    echo " - Attached to User: " . $user->getEmail() . " (User ID: " . $user->getId() . ")\n";
} catch (\Exception $e) {
    echo "Error creating ward: " . $e->getMessage() . "\n";
    exit(1);
}
