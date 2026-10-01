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

        // 1b. Check subscription max_child limit from database
        $existingWards = $this->entityManager->getRepository(Ward::class)->findBy([
            'user' => $user
        ]);
        $currentWardCount = is_array($existingWards) || $existingWards instanceof \Countable ? count($existingWards) : 0;

        $maxChild = 1;
        $invoiceRepo = $this->entityManager->getRepository(\Subscription\Entity\Invoice::class);
        if ($invoiceRepo) {
            $paidInvoices = $invoiceRepo->findBy(
                ['user' => $user, 'status' => \Subscription\Entity\Invoice::STATUS_PAID],
                ['id' => 'DESC']
            );
            if (! empty($paidInvoices) && isset($paidInvoices[0]) && $paidInvoices[0]->getSubscriptionType()) {
                $maxChild = $paidInvoices[0]->getSubscriptionType()->getMaxChild();
            }
        }

        if ($maxChild === 1) {
            $subTypeRepo = $this->entityManager->getRepository(\Subscription\Entity\SubscriptionType::class);
            if ($subTypeRepo) {
                $standardType = $subTypeRepo->findOneBy(['code' => 'monthly_standard']);
                if ($standardType) {
                    $maxChild = $standardType->getMaxChild();
                }
            }
        }

        if ($currentWardCount >= $maxChild) {
            throw new \Exception("Maximum child limit reached for your subscription plan. Limit is {$maxChild} child(ren).");
        }

        // 2. Validate input parameters (fullname, date_of_birth)
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

        // Auto-generate a unique string UUID in the database
        do {
            $uuid = Uuid::uuid4()->toString();
            $existingUuid = $this->entityManager->getRepository(Ward::class)->findOneBy([
                'uuid' => $uuid
            ]);
        } while ($existingUuid !== null);

        // 3. Create Ward entity
        $ward = new Ward();
        $ward->setFullname($data['fullname'])
             ->setDateOfBirth($dob)
             ->setUuid($uuid)
             ->setUser($user);

        // Set default status (active)
        $defaultStatus = $this->entityManager->getRepository(WardStatus::class)->findOneBy([
            'status' => WardStatus::STATUS_ACTIVE
        ]);
        if ($defaultStatus) {
            $ward->setStatus($defaultStatus);
        }

        // Set expireDate to a day before present date
        $expireDt = (new \DateTime())->modify('-1 day');
        $ward->setExpireDate($expireDt);

        // Handle Gender from request body (or default to Female)
        $genderEntity = null;
        $genderVal = $data['gender'] ?? $data['gender_id'] ?? null;

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
     * Edit / update ward info for the authenticated user.
     *
     * @param array $data Input parameters
     * @param array $identity Authenticated user's identity claims
     * @return Ward
     * @throws \Exception
     */
    public function editWard(array $data, array $identity): Ward
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

        $wardIdOrUuid = $data['id'] ?? $data['ward_id'] ?? $data['uuid'] ?? null;
        if (empty($wardIdOrUuid)) {
            throw new \Exception("Ward identifier (id or uuid) is required.");
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
            throw new \Exception("Ward not found or you do not have permission to edit it.");
        }

        // 1. Update fullname if provided
        if (array_key_exists('fullname', $data)) {
            if (empty(trim((string) $data['fullname']))) {
                throw new \Exception("Full name cannot be empty.");
            }
            $ward->setFullname(trim((string) $data['fullname']));
        }

        // 2. Update date_of_birth if provided
        if (array_key_exists('date_of_birth', $data)) {
            if (empty($data['date_of_birth'])) {
                throw new \Exception("Date of birth cannot be empty.");
            }
            $dobStr = (string) $data['date_of_birth'];
            $dob = \DateTime::createFromFormat('Y-m-d', $dobStr);
            if (! $dob || $dob->format('Y-m-d') !== $dobStr) {
                throw new \Exception("Invalid date of birth format. Use YYYY-MM-DD.");
            }
            $ward->setDateOfBirth($dob);
        }

        // 3. Update gender if provided
        if (array_key_exists('gender', $data) || array_key_exists('gender_id', $data)) {
            $genderVal = $data['gender'] ?? $data['gender_id'] ?? null;
            if (! empty($genderVal)) {
                $genderEntity = null;
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

                if (! $genderEntity) {
                    throw new \Exception("Gender not found.");
                }
                $ward->setGender($genderEntity);
            }
        }

        // 4. Update status if provided
        if (array_key_exists('status', $data) || array_key_exists('status_id', $data)) {
            $statusVal = $data['status'] ?? $data['status_id'] ?? null;
            if (! empty($statusVal)) {
                $statusEntity = null;
                if (is_numeric($statusVal)) {
                    $statusEntity = $this->entityManager->getRepository(WardStatus::class)->find((int) $statusVal);
                } else {
                    $statusEntity = $this->entityManager->getRepository(WardStatus::class)->findOneBy([
                        'status' => strtolower((string) $statusVal)
                    ]);
                }

                if (! $statusEntity) {
                    throw new \Exception("Ward status not found.");
                }
                $ward->setStatus($statusEntity);
            }
        }

        $ward->setUpdatedOn(new \DateTime());

        $this->entityManager->flush();

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
