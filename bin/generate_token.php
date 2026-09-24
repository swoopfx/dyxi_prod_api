#!/usr/bin/env php
<?php

declare(strict_types=1);

// Set working directory to project root
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Authentication\Entity\Roles;
use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Authentication\Service\AuthenticationService;
use Subscription\Entity\Invoice;
use Subscription\Entity\SubscriptionType;
use Subscription\Service\SubscriptionService;
use Ramsey\Uuid\Uuid;
use Ward\Entity\Ward;
use Ward\Entity\WardStatus;

// Prevent session header warning output in CLI
if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
    @session_start();
}

echo "====================================================\n";
echo "   🎫 DYXI SUBSCRIPTION SEED & TOKEN GENERATOR      \n";
echo "====================================================\n\n";

// Parse CLI options: --email, --ward, --service, --currency, --help
$options = getopt("e:w:s:c::", ["email:", "ward:", "service:", "currency::", "help"]);

if (isset($options['help'])) {
    echo "Usage: php bin/generate_token.php [options]\n";
    echo "Options:\n";
    echo "  -e, --email      User email address (default: sarah.jenkins@neuropath.org)\n";
    echo "  -w, --ward       Ward / Student full name (default: Leo Jenkins)\n";
    echo "  -s, --service    Subscription Type Code (default: monthly_standard)\n";
    echo "  -c, --currency   Currency NGN or USD (default: USD)\n";
    echo "      --help       Display this help message\n";
    exit(0);
}

$positionalArgs = array_values(array_filter(array_slice($argv, 1), fn ($arg) => ! str_starts_with((string) $arg, '-')));

$email = $options['email'] ?? $options['e'] ?? $positionalArgs[0] ?? 'sarah.jenkins@neuropath.org';
$wardInput = $options['ward'] ?? $options['w'] ?? (isset($positionalArgs[1]) ? $positionalArgs[1] : null);
$serviceCode = $options['service'] ?? $options['s'] ?? (isset($positionalArgs[1]) && !isset($options['ward']) && !isset($options['w']) ? ($positionalArgs[2] ?? 'monthly_standard') : ($positionalArgs[2] ?? 'monthly_standard'));
$currency = strtoupper((string)($options['currency'] ?? $options['c'] ?? $positionalArgs[3] ?? 'NGN'));

if (! in_array($currency, ['USD', 'NGN'], true)) {
    $currency = 'USD';
}

echo "[1/4] Bootstrapping application container...\n";

try {
    $container = require 'config/container.php';
    $subscriptionService = $container->get(SubscriptionService::class);
    $em = $subscriptionService->getEntityManager();
    $tokenService = $subscriptionService->getTokenService();
} catch (\Throwable $e) {
    echo "❌ Bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // 1. Seed & retrieve Subscription Types
    echo "[2/4] Seeding & loading Subscription Types from database...\n";
    $subscriptionService->seedSubscriptionTypes();
    $subTypeRepo = $em->getRepository(SubscriptionType::class);
    $subscriptionType = $subTypeRepo->findOneBy(['code' => $serviceCode]);

    if (! $subscriptionType) {
        $allTypes = $subTypeRepo->findAll();
        $subscriptionType = ! empty($allTypes) ? $allTypes[0] : null;
    }

    if (! $subscriptionType) {
        echo "❌ Error: No subscription type found in database.\n";
        exit(1);
    }

    echo " ✔ Subscription Type loaded: '{$subscriptionType->getName()}' ({$subscriptionType->getCode()})\n";

    // 2. Find or create User
    echo "[3/4] Checking database User & Ward account...\n";
    $userRepo = $em->getRepository(User::class);
    $user = $userRepo->findOneBy(['email' => $email]);

    if (! $user) {
        $stateEnabled = $em->find(UserState::class, AuthenticationService::USER_STATE_ENABLED);
        $roleGuardian = $em->find(Roles::class, AuthenticationService::USER_ROLE_GUARDIAN);

        $user = new User();
        $user->setFullname('Sarah Jenkins')
             ->setUsername($email)
             ->setEmail($email)
             ->setPassword(AuthenticationService::encryptPassword('Password123!'))
             ->setEmailConfirmed(true)
             ->setIsProfiled(true)
             ->setUid(uniqid("resu"))
             ->setUuid(Uuid::uuid4()->toString())
             ->setRegistrationDate(new \DateTime())
             ->setCreatedOn(new \DateTime());

        if ($stateEnabled) {
            $user->setState($stateEnabled);
        }
        if ($roleGuardian) {
            $user->setRole($roleGuardian);
        }

        $em->persist($user);
        $em->flush();
        echo " ✔ Created User: {$email} (ID: {$user->getId()})\n";
    } else {
        echo " ✔ Existing User found: {$user->getEmail()} (ID: {$user->getId()})\n";
    }

    // 3. Find or create Ward & enforce User association
    $wardRepo = $em->getRepository(Ward::class);
    $ward = null;

    if ($wardInput !== null && trim((string)$wardInput) !== '') {
        $wardName = trim((string)$wardInput);
        $ward = $wardRepo->findOneBy(['user' => $user, 'fullname' => $wardName])
            ?: $wardRepo->findOneBy(['fullname' => $wardName]);
    } else {
        // Ward was not provided: search the database for one ward associated to the user
        $ward = $wardRepo->findOneBy(['user' => $user]);
        $wardName = $ward ? $ward->getFullname() : 'Leo Jenkins';
    }

    if (! $ward) {
        $statusActive = $em->find(WardStatus::class, WardStatus::STATUS_ACTIVE_ID)
            ?: $em->getRepository(WardStatus::class)->findOneBy(['status' => WardStatus::STATUS_ACTIVE]);

        $ward = new Ward();
        $ward->setFullname($wardName)
             ->setUser($user)
             ->setUuid(Uuid::uuid4()->toString())
             ->setDateOfBirth(new \DateTime('2015-06-12'))
             ->setCreatedOn(new \DateTime());

        if ($statusActive) {
            $ward->setStatus($statusActive);
        }

        $em->persist($ward);
        $em->flush();
        echo " ✔ Created Ward: {$ward->getFullname()} (ID: {$ward->getId()}) associated with User (ID: {$user->getId()})\n";
    } else {
        if (! $ward->getUser() || $ward->getUser()->getId() !== $user->getId()) {
            $ward->setUser($user);
            $em->flush();
            echo " ✔ Updated Ward ownership: {$ward->getFullname()} (ID: {$ward->getId()}) now associated with User (ID: {$user->getId()})\n";
        } else {
            echo " ✔ Found & Verified Ward association: {$ward->getFullname()} (ID: {$ward->getId()}) associated with User (ID: {$user->getId()})\n";
        }
    }

    // 4. Generate or retrieve Pending Invoice
    $invoiceRepo = $em->getRepository(Invoice::class);
    $pendingInvoice = $invoiceRepo->findOneBy([
        'user'             => $user,
        'ward'             => $ward,
        'subscriptionType' => $subscriptionType,
        'status'           => Invoice::STATUS_PENDING,
    ], ['id' => 'DESC']);

    if (! $pendingInvoice) {
        $pendingInvoice = $subscriptionService->generatePendingInvoice(
            $user->getId(),
            $ward->getId(),
            $subscriptionType->getCode(),
            $currency
        );
        echo " ✔ Generated new Pending Invoice: {$pendingInvoice->getInvoiceNumber()}\n";
    } else {
        echo " ✔ Active Pending Invoice retrieved: {$pendingInvoice->getInvoiceNumber()}\n";
    }

    // 5. Encrypt token with required payload data
    echo "\n[4/4] Encrypting token with required payload data...\n";

    $tokenPayload = [
        'user_id'    => $user->getId(),
        'ward_id'    => $ward->getId(),
        'service'    => $subscriptionType->getCode(),
        'currency'   => $currency,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $encryptedToken = $tokenService->encryptToken($tokenPayload);
    $appUrl = getenv('APP_URL') ?: 'http://localhost:8080';
    $subscribeUrl = rtrim($appUrl, '/') . '/subscribe/' . $encryptedToken;

    echo "\n====================================================\n";
    echo "   🎉 SUBSCRIPTION TOKEN & SEED GENERATED SUCCESS  \n";
    echo "====================================================\n";
    echo " USER NAME    : " . $user->getFullname() . "\n";
    echo " USER EMAIL   : " . $user->getEmail() . " (ID: " . $user->getId() . ")\n";
    echo " WARD NAME    : " . $ward->getFullname() . " (ID: " . $ward->getId() . ")\n";
    echo " PLAN CODE    : " . $subscriptionType->getCode() . " (" . $subscriptionType->getName() . ")\n";
    echo " INVOICE REF  : " . $pendingInvoice->getInvoiceNumber() . " (Status: " . strtoupper($pendingInvoice->getStatus()) . ")\n";
    echo " INVOICE AMT  : " . ($currency === 'USD' ? '$' . number_format($subscriptionType->getAmountUsd(), 2) : '₦' . number_format($subscriptionType->getAmountNgn(), 2)) . "\n";
    echo "----------------------------------------------------\n";
    echo " ENCRYPTED TOKEN:\n";
    echo " " . $encryptedToken . "\n";
    echo "----------------------------------------------------\n";
    echo " READY-TO-USE SUBSCRIBE PORTAL URL:\n";
    echo " " . $subscribeUrl . "\n";
    echo "====================================================\n\n";

} catch (\Throwable $e) {
    echo "❌ Error generating subscription token: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
