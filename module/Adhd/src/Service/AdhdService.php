<?php

namespace Adhd\Service;

use Doctrine\ORM\EntityManager;
use Authentication\Entity\User;
use Ward\Entity\Ward;
use Adhd\Entity\Adhd;
use Ramsey\Uuid\Uuid;

class AdhdService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * AdhdService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Register a new ADHD assessment.
     *
     * @param array $data Input parameters
     * @param array $identity Authenticated user's identity claims
     * @return Adhd
     * @throws \Exception
     */
    public function register(array $data, array $identity): Adhd
    {
        $user = $this->resolveUser($identity);

        if (empty($data['ward_id'])) {
            throw new \Exception("Ward ID (ward_id) is required.");
        }

        $ward = $this->resolveWard($data['ward_id'], $user);

        // Validate required scores
        if (!isset($data['inattention_score'])) {
            throw new \Exception("Inattention score is required.");
        }
        if (!isset($data['hyperactivity_score'])) {
            throw new \Exception("Hyperactivity score is required.");
        }

        $is = (int) $data['inattention_score'];
        $hs = (int) $data['hyperactivity_score'];
        $total = $is + $hs;

        if (empty($data['diagnosis'])) {
            throw new \Exception("ADHD diagnosis is required.");
        }

        if (empty($data['assessment_date'])) {
            throw new \Exception("Assessment date (assessment_date) is required.");
        }

        $dateStr = $data['assessment_date'];
        $assessmentDate = \DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$assessmentDate || $assessmentDate->format('Y-m-d') !== $dateStr) {
            throw new \Exception("Invalid assessment date format. Use YYYY-MM-DD.");
        }

        // Unique Identifier handling
        $uniqueIdentifier = $data['unique_identifier'] ?? null;
        if (empty($uniqueIdentifier)) {
            $uniqueIdentifier = 'ADH-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        }

        $existing = $this->entityManager->getRepository(Adhd::class)->findOneBy([
            'uniqueIdentifier' => $uniqueIdentifier
        ]);
        if ($existing) {
            throw new \Exception("An assessment with this unique identifier already exists.");
        }

        // UUID handling
        $uuid = $data['uuid'] ?? null;
        if (empty($uuid)) {
            $uuid = Uuid::uuid4()->toString();
        } else {
            if (!Uuid::isValid($uuid)) {
                throw new \Exception("Invalid UUID format.");
            }
            $existingUuid = $this->entityManager->getRepository(Adhd::class)->findOneBy([
                'uuid' => $uuid
            ]);
            if ($existingUuid) {
                throw new \Exception("An assessment with this UUID already exists.");
            }
        }

        $adhd = new Adhd();
        $adhd->setUuid($uuid)
             ->setUniqueIdentifier($uniqueIdentifier)
             ->setWard($ward)
             ->setAssessmentDate($assessmentDate)
             ->setInattentionScore($is)
             ->setHyperactivityScore($hs)
             ->setTotalScore($total)
             ->setDiagnosis($data['diagnosis'])
             ->setNotes($data['notes'] ?? null);

        $this->entityManager->persist($adhd);
        $this->entityManager->flush();

        return $adhd;
    }

    /**
     * List all ADHD assessments for the authenticated user's wards.
     *
     * @param array $identity Authenticated user's identity claims
     * @return array
     * @throws \Exception
     */
    public function listAssessments(array $identity): array
    {
        $user = $this->resolveUser($identity);

        $wards = $this->entityManager->getRepository(Ward::class)->findBy([
            'user' => $user
        ]);

        if (empty($wards)) {
            return [];
        }

        return $this->entityManager->getRepository(Adhd::class)->findBy([
            'ward' => $wards
        ]);
    }

    /**
     * Retrieve detailed ADHD assessment if belongs to user's ward.
     *
     * @param string|int $idOrUuid ID, UUID or Unique Identifier
     * @param array $identity Authenticated user's identity claims
     * @return Adhd
     * @throws \Exception
     */
    public function getAssessmentInfo($idOrUuid, array $identity): Adhd
    {
        $user = $this->resolveUser($identity);
        $repo = $this->entityManager->getRepository(Adhd::class);

        $adhd = null;
        if (is_numeric($idOrUuid)) {
            $adhd = $repo->find((int) $idOrUuid);
        }
        if (!$adhd) {
            $adhd = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$adhd) {
            $adhd = $repo->findOneBy(['uniqueIdentifier' => $idOrUuid]);
        }

        if (!$adhd) {
            throw new \Exception("Assessment record not found.");
        }

        if ($adhd->getWard()->getUser()->getId() !== $user->getId()) {
            throw new \Exception("Access denied. You do not have permission to view this record.");
        }

        return $adhd;
    }

    /**
     * Update an existing assessment.
     *
     * @param string|int $idOrUuid
     * @param array $data
     * @param array $identity
     * @return Adhd
     * @throws \Exception
     */
    public function update($idOrUuid, array $data, array $identity): Adhd
    {
        $adhd = $this->getAssessmentInfo($idOrUuid, $identity);

        if (isset($data['inattention_score'])) {
            $adhd->setInattentionScore((int) $data['inattention_score']);
        }
        if (isset($data['hyperactivity_score'])) {
            $adhd->setHyperactivityScore((int) $data['hyperactivity_score']);
        }

        // Recompute total
        $total = $adhd->getInattentionScore() + $adhd->getHyperactivityScore();
        $adhd->setTotalScore($total);

        if (!empty($data['diagnosis'])) {
            $adhd->setDiagnosis($data['diagnosis']);
        }

        if (!empty($data['assessment_date'])) {
            $dateStr = $data['assessment_date'];
            $assessmentDate = \DateTime::createFromFormat('Y-m-d', $dateStr);
            if (!$assessmentDate || $assessmentDate->format('Y-m-d') !== $dateStr) {
                throw new \Exception("Invalid assessment date format. Use YYYY-MM-DD.");
            }
            $adhd->setAssessmentDate($assessmentDate);
        }

        if (array_key_exists('notes', $data)) {
            $adhd->setNotes($data['notes']);
        }

        $this->entityManager->flush();

        return $adhd;
    }

    /**
     * Delete an existing assessment.
     *
     * @param string|int $idOrUuid
     * @param array $identity
     * @return bool
     * @throws \Exception
     */
    public function delete($idOrUuid, array $identity): bool
    {
        $adhd = $this->getAssessmentInfo($idOrUuid, $identity);
        $this->entityManager->remove($adhd);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Resolve authenticated User entity.
     */
    private function resolveUser(array $identity): User
    {
        if (empty($identity['uuid'])) {
            throw new \Exception("Unauthorized: User identity not found.");
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'uuid' => $identity['uuid']
        ]);

        if (!$user) {
            throw new \Exception("User not found.");
        }

        return $user;
    }

    /**
     * Resolve Ward entity and verify ownership.
     */
    private function resolveWard($wardIdOrUuid, User $user): Ward
    {
        $repo = $this->entityManager->getRepository(Ward::class);

        $ward = null;
        if (is_numeric($wardIdOrUuid)) {
            $ward = $repo->find((int) $wardIdOrUuid);
        }
        if (!$ward) {
            $ward = $repo->findOneBy(['uuid' => $wardIdOrUuid]);
        }

        if (!$ward) {
            throw new \Exception("Ward not found.");
        }

        if ($ward->getUser()->getId() !== $user->getId()) {
            throw new \Exception("Access denied. The specified ward does not belong to you.");
        }

        return $ward;
    }
}
