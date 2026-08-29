<?php

namespace Consultant\Service;

use Doctrine\ORM\EntityManager;
use Consultant\Entity\Consultant;
use Authentication\Entity\User;
use Ramsey\Uuid\Uuid;

class ConsultantService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * ConsultantService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Register a new Consultant profile.
     *
     * @param array $data Input parameters
     * @return Consultant
     * @throws \Exception
     */
    public function register(array $data): Consultant
    {
        if (empty($data['fullname'])) {
            throw new \Exception("Consultant fullname is required.");
        }

        if (empty($data['email'])) {
            throw new \Exception("Consultant email is required.");
        }

        // Validate email structure
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email format.");
        }

        // Check unique email
        $existing = $this->entityManager->getRepository(Consultant::class)->findOneBy(['email' => $data['email']]);
        if ($existing) {
            throw new \Exception("A consultant with this email address already exists.");
        }

        // Resolve user if user_id or user_uuid is provided
        $user = null;
        if (!empty($data['user_id'])) {
            $userRepo = $this->entityManager->getRepository(User::class);
            if (is_numeric($data['user_id'])) {
                $user = $userRepo->find((int) $data['user_id']);
            } else {
                $user = $userRepo->findOneBy(['uuid' => $data['user_id']]);
            }

            if (!$user) {
                throw new \Exception("The associated user account could not be found.");
            }
        }

        $uuid = $data['uuid'] ?? Uuid::uuid4()->toString();
        if (!Uuid::isValid($uuid)) {
            throw new \Exception("Invalid UUID format.");
        }

        $consultant = new Consultant();
        $consultant->setUuid($uuid)
                   ->setFullname($data['fullname'])
                   ->setEmail($data['email'])
                   ->setPhone($data['phone'] ?? null)
                   ->setSpecialization($data['specialization'] ?? null)
                   ->setBio($data['bio'] ?? null)
                   ->setUser($user);

        if (!empty($data['status'])) {
            $consultant->setStatus($data['status']);
        }

        $this->entityManager->persist($consultant);
        $this->entityManager->flush();

        return $consultant;
    }

    /**
     * Retrieve all consultant profiles.
     *
     * @return array
     */
    public function listConsultants(): array
    {
        return $this->entityManager->getRepository(Consultant::class)->findAll();
    }

    /**
     * Retrieve a specific consultant by ID or UUID.
     *
     * @param string|int $idOrUuid
     * @return Consultant
     * @throws \Exception
     */
    public function getConsultantInfo($idOrUuid): Consultant
    {
        $repo = $this->entityManager->getRepository(Consultant::class);
        $consultant = null;

        if (is_numeric($idOrUuid)) {
            $consultant = $repo->find((int) $idOrUuid);
        }
        if (!$consultant) {
            $consultant = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$consultant) {
            $consultant = $repo->findOneBy(['email' => $idOrUuid]);
        }

        if (!$consultant) {
            throw new \Exception("Consultant not found.");
        }

        return $consultant;
    }
}
