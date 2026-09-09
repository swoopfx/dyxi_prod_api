<?php

declare(strict_types=1);

// Set directory to root
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

use Authentication\Entity\UserState;
use Authentication\Entity\Roles;
use Authentication\Service\AuthenticationService;
use General\Entity\Gender;
use Ward\Entity\WardStatus;
use Game\Entity\TargetTags;
use Consultant\Entity\ConsultantCategory;
use Ramsey\Uuid\Uuid;

echo "Bootstrapping application for preset data hydration...\n";

try {
    $container = require 'config/container.php';
    $generalService = $container->get('general_service');
    $em = $generalService->getEm();
} catch (\Exception $e) {
    echo "Bootstrap error: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    // 1. Seed/Update User States
    $userStatesMap = [
        AuthenticationService::USER_STATE_ENABLED => 'Enabled',
        AuthenticationService::USER_STATE_DISABLED => 'Disabled',
        AuthenticationService::USER_STATE_PENDING => 'Pending',
    ];

    echo "Hydrating User States...\n";
    foreach ($userStatesMap as $stateId => $stateName) {
        $stateObj = $em->find(UserState::class, $stateId);
        if (! $stateObj) {
            $stateObj = new UserState();
            $refProp = new \ReflectionProperty(UserState::class, 'id');
            $refProp->setAccessible(true);
            $refProp->setValue($stateObj, $stateId);
        }
        $stateObj->setState($stateName);
        $em->persist($stateObj);
        echo " - UserState [$stateId]: $stateName\n";
    }

    // 2. Seed/Update Roles
    $rolesMap = [
        AuthenticationService::USER_ROLE_GUEST => 'Guest',
        AuthenticationService::USER_ROLE_GUARDIAN => 'Guardian',
        AuthenticationService::USER_ROLE_CONSULTANT => 'Consultant',
        AuthenticationService::USER_ROLE_ADMIN => 'Admin',
        AuthenticationService::USER_ROLE_SUPER_ADMIN => 'SuperAdmin',
    ];

    echo "Hydrating System Roles...\n";
    foreach ($rolesMap as $rId => $rName) {
        $roleObj = $em->find(Roles::class, $rId);
        if (! $roleObj) {
            $roleObj = $em->getRepository(Roles::class)->findOneBy(['name' => $rName]);
        }
        if (! $roleObj) {
            $roleObj = new Roles();
            $roleObj->setId($rId);
        }
        $roleObj->setName($rName);
        $em->persist($roleObj);
        echo " - Role [{$roleObj->getId()}]: {$roleObj->getName()}\n";
    }

    // 3. Seed/Update Genders
    $gendersMap = [
        1 => 'Male',
        2 => 'Female',
        3 => 'Other',
    ];

    echo "Hydrating Genders...\n";
    foreach ($gendersMap as $gId => $gName) {
        $genderObj = $em->find(Gender::class, $gId);
        if (! $genderObj) {
            $genderObj = new Gender();
            $refProp = new \ReflectionProperty(Gender::class, 'id');
            $refProp->setAccessible(true);
            $refProp->setValue($genderObj, $gId);
        }
        $genderObj->setGender($gName);
        $em->persist($genderObj);
        echo " - Gender [$gId]: $gName\n";
    }

    // 4. Seed/Update Ward Statuses
    $wardStatusesMap = [
        WardStatus::STATUS_ACTIVE_ID => WardStatus::STATUS_ACTIVE,
        WardStatus::STATUS_SUSPENDED_ID => WardStatus::STATUS_SUSPENDED,
        WardStatus::STATUS_PENDING_ID => WardStatus::STATUS_PENDING,
    ];

    echo "Hydrating Ward Statuses...\n";
    foreach ($wardStatusesMap as $wsId => $wsName) {
        $wardStatusObj = $em->find(WardStatus::class, $wsId);
        if (! $wardStatusObj) {
            $wardStatusObj = new WardStatus();
            $refProp = new \ReflectionProperty(WardStatus::class, 'id');
            $refProp->setAccessible(true);
            $refProp->setValue($wardStatusObj, $wsId);
        }
        $wardStatusObj->setStatus($wsName);
        $em->persist($wardStatusObj);
        echo " - WardStatus [$wsId]: $wsName\n";
    }

    // 5. Seed/Update Target Tags
    $targetTagsMap = [
        'ADHD' => 'Attention Deficit Hyperactivity Disorder',
        'Dyslexia' => 'Difficulty reading and processing text',
        'Dyscalculia' => 'Difficulty understanding numbers and math concepts',
        'Autism' => 'Autism Spectrum Disorder',
    ];

    echo "Hydrating Target Tags...\n";
    foreach ($targetTagsMap as $tagName => $tagDesc) {
        $tagObj = $em->getRepository(TargetTags::class)->findOneBy(['name' => $tagName]);
        if (! $tagObj) {
            $tagObj = new TargetTags();
            $tagObj->setUuid(Uuid::uuid4()->toString());
            $tagObj->setName($tagName);
        }
        $tagObj->setDescription($tagDesc);
        $em->persist($tagObj);
        echo " - TargetTag: $tagName\n";
    }

    // 6. Seed/Update Consultant Categories
    $consultantCategoriesMap = [
        'Doctor' => 'Medical doctor specializing in clinical assessment and treatment.',
        'Consultant' => 'Professional consultant providing specialist guidance and advice.',
        'Psychologist' => 'Licensed psychologist specializing in mental health and neurodevelopmental evaluation.',
    ];

    echo "Hydrating Consultant Categories...\n";
    foreach ($consultantCategoriesMap as $catName => $catDesc) {
        $catObj = $em->getRepository(ConsultantCategory::class)->findOneBy(['name' => $catName]);
        if (! $catObj) {
            $catObj = new ConsultantCategory();
            $catObj->setUuid(Uuid::uuid4()->toString());
            $catObj->setName($catName);
        }
        $catObj->setDescription($catDesc);
        $em->persist($catObj);
        echo " - ConsultantCategory: $catName\n";
    }

    $em->flush();
    echo "Preset data hydration completed successfully.\n";
} catch (\Exception $e) {
    echo "Database hydration error: " . $e->getMessage() . "\n";
    exit(1);
}
