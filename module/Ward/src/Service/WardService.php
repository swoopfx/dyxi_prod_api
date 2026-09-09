<?php

namespace Ward\Service;

use Doctrine\ORM\EntityManager;
use Authentication\Entity\User;
use Ward\Entity\Ward;
use Ward\Entity\WardStatus;
use General\Entity\Gender;
use Ramsey\Uuid\Uuid;

class WardService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * WardService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Register a new ward for the authenticated user.
     *
     * @param array $data Input parameters
     * @param array $identity Authenticated user's identity claims
     * @return Ward
     * @throws \Exception
     */
    public function register(array $data, array $identity): Ward
    {
        // 1. Resolve user
        if (empty($identity['uuid'])) {
            throw new \Exception("Unauthorized: User identity not found.");
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'uuid' => $identity['uuid']
        ]);

        if (! $user) {
            throw new \Exception("User not found.");
        }

        // 2. Validate input parameters
        if (empty($data['fullname'])) {
            throw new \Exception("Full name is required.");
        }

        if (empty($data['date_of_birth'])) {
            throw new \Exception("Date of birth is required.");
        }

        $dobStr = $data['date_of_birth'];
        $dob = \DateTime::createFromFormat('Y-m-d', $dobStr);
        if (! $dob || $dob->format('Y-m-d') !== $dobStr) {
            throw new \Exception("Invalid date of birth format. Use YYYY-MM-DD.");
        }

        // Handle uuid
        $uuid = $data['uuid'] ?? null;
        if (empty($uuid)) {
            $uuid = Uuid::uuid4()->toString();
        } else {
            if (! Uuid::isValid($uuid)) {
                throw new \Exception("Invalid UUID format.");
            }
            // Check if uuid is already taken
            $existingUuid = $this->entityManager->getRepository(Ward::class)->findOneBy([
                'uuid' => $uuid
            ]);
            if ($existingUuid) {
                throw new \Exception("A ward with this UUID already exists.");
            }
        }

        // 3. Create Ward entity
        $ward = new Ward();
        $ward->setFullname($data['fullname'])
             ->setDateOfBirth($dob)
             ->setUuid($uuid)
             ->setUser($user);

        // Handle WardStatus
        if (! empty($data['status'])) {
            $statusEntity = null;
            if (is_numeric($data['status'])) {
                $statusEntity = $this->entityManager->getRepository(WardStatus::class)->find((int) $data['status']);
            } else {
                $statusEntity = $this->entityManager->getRepository(WardStatus::class)->findOneBy([
                    'status' => strtolower((string) $data['status'])
                ]);
            }
            if ($statusEntity) {
                $ward->setStatus($statusEntity);
            }
        } else {
            $defaultStatus = $this->entityManager->getRepository(WardStatus::class)->findOneBy([
                'status' => WardStatus::STATUS_ACTIVE
            ]);
            if ($defaultStatus) {
                $ward->setStatus($defaultStatus);
            }
        }

        // Handle expireDate
        $expireInput = $data['expireDate'] ?? $data['expire_date'] ?? null;
        if ($expireInput !== null && $expireInput !== '') {
            if ($expireInput instanceof \DateTime) {
                $ward->setExpireDate($expireInput);
            } elseif (is_numeric($expireInput)) {
                $hours = (int) $expireInput;
                $expireDt = (new \DateTime())->modify("+{$hours} hours");
                $ward->setExpireDate($expireDt);
            } else {
                try {
                    $expireDt = new \DateTime((string) $expireInput);
                    $ward->setExpireDate($expireDt);
                } catch (\Throwable $e) {
                    throw new \Exception("Invalid expireDate format.");
                }
            }
        }

        // Handle Gender (Default to Female)
        $genderEntity = null;
        $genderVal = $data['gender_id'] ?? $data['gender'] ?? null;

        if (! empty($genderVal)) {
            if (is_numeric($genderVal)) {
                $genderEntity = $this->entityManager->getRepository(Gender::class)->find((int) $genderVal);
            } else {
                $genderEntity = $this->entityManager->getRepository(Gender::class)->findOneBy([
                    'gender' => ucfirst(strtolower((string) $genderVal))
                ]);
                if (! $genderEntity) {
                    $genderEntity = $this->entityManager->getRepository(Gender::class)->findOneBy([
                        'gender' => strtolower((string) $genderVal)
                    ]);
                }
            }
        }

        if (! $genderEntity) {
            // Default to Female
            $genderEntity = $this->entityManager->getRepository(Gender::class)->findOneBy([
                'gender' => 'Female'
            ]);
            if (! $genderEntity) {
                $genderEntity = $this->entityManager->getRepository(Gender::class)->find(2);
            }
        }

        if ($genderEntity) {
            $ward->setGender($genderEntity);
        }

        // 4. Persist
        $this->entityManager->persist($ward);
        $this->entityManager->flush();

        return $ward;
    }

    /**
     * List all wards registered under the authenticated user.
     *
     * @param array $identity Authenticated user's identity claims
     * @return array Array of Ward entities
     * @throws \Exception
     */
    public function listWards(array $identity): array
    {
        if (empty($identity['uuid'])) {
            throw new \Exception("Unauthorized: User identity not found.");
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'uuid' => $identity['uuid']
        ]);

        if (! $user) {
            throw new \Exception("User not found.");
        }

        return $this->entityManager->getRepository(Ward::class)->findBy([
            'user' => $user
        ]);
    }

    /**
     * Retrieve a specific ward's details for the authenticated user.
     *
     * @param string|int $wardIdOrUuid Ward ID, UUID or unique identifier
     * @param array $identity Authenticated user's identity claims
     * @return Ward
     * @throws \Exception
     */
    public function getWardInfo($wardIdOrUuid, array $identity): Ward
    {
        if (empty($identity['uuid'])) {
            throw new \Exception("Unauthorized: User identity not found.");
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'uuid' => $identity['uuid']
        ]);

        if (! $user) {
            throw new \Exception("User not found.");
        }

        $repo = $this->entityManager->getRepository(Ward::class);

        $ward = null;
        if (is_numeric($wardIdOrUuid)) {
            $ward = $repo->findOneBy(['id' => (int) $wardIdOrUuid, 'user' => $user]);
        }

        if (! $ward) {
            $ward = $repo->findOneBy(['uuid' => $wardIdOrUuid, 'user' => $user]);
        }

        if (! $ward) {
            throw new \Exception("Ward not found or you do not have permission to view it.");
        }

        return $ward;
    }

    /**
     * Get entity manager.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
}
