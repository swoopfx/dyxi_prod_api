<?php

namespace Dyscalculia\Service;

use Doctrine\ORM\EntityManager;
use Authentication\Entity\User;
use Ward\Entity\Ward;
use Dyscalculia\Entity\Dyscalculia;
use Ramsey\Uuid\Uuid;

class DyscalculiaService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * DyscalculiaService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Register a new Dyscalculia assessment.
     *
     * @param array $data Input parameters
     * @param array $identity Authenticated user's identity claims
     * @return Dyscalculia
     * @throws \Exception
     */
    public function register(array $data, array $identity): Dyscalculia
    {
        $user = $this->resolveUser($identity);

        if (empty($data['ward_id'])) {
            throw new \Exception("Ward ID (ward_id) is required.");
        }

        $ward = $this->resolveWard($data['ward_id'], $user);

        // Validate required scores
        if (!isset($data['arithmetic_score'])) {
            throw new \Exception("Arithmetic score is required.");
        }
        if (!isset($data['number_sense_score'])) {
            throw new \Exception("Number sense score is required.");
        }
        if (!isset($data['spatial_reasoning_score'])) {
            throw new \Exception("Spatial reasoning score is required.");
        }

        $as = (int) $data['arithmetic_score'];
        $ns = (int) $data['number_sense_score'];
        $ss = (int) $data['spatial_reasoning_score'];
        $total = $as + $ns + $ss;

        if (empty($data['severity_level'])) {
            throw new \Exception("Dyscalculia severity level is required.");
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
            $uniqueIdentifier = 'CAL-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        }

        $existing = $this->entityManager->getRepository(Dyscalculia::class)->findOneBy([
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
            $existingUuid = $this->entityManager->getRepository(Dyscalculia::class)->findOneBy([
                'uuid' => $uuid
            ]);
            if ($existingUuid) {
                throw new \Exception("An assessment with this UUID already exists.");
            }
        }

        $dyscalculia = new Dyscalculia();
        $dyscalculia->setUuid($uuid)
                    ->setUniqueIdentifier($uniqueIdentifier)
                    ->setWard($ward)
                    ->setAssessmentDate($assessmentDate)
                    ->setArithmeticScore($as)
                    ->setNumberSenseScore($ns)
                    ->setSpatialReasoningScore($ss)
                    ->setTotalScore($total)
                    ->setSeverityLevel($data['severity_level'])
                    ->setNotes($data['notes'] ?? null);

        $this->entityManager->persist($dyscalculia);
        $this->entityManager->flush();

        return $dyscalculia;
    }

    /**
     * List all Dyscalculia assessments for the authenticated user's wards.
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

        return $this->entityManager->getRepository(Dyscalculia::class)->findBy([
            'ward' => $wards
        ]);
    }

    /**
     * Retrieve detailed Dyscalculia assessment if belongs to user's ward.
     *
     * @param string|int $idOrUuid ID, UUID or Unique Identifier
     * @param array $identity Authenticated user's identity claims
     * @return Dyscalculia
     * @throws \Exception
     */
    public function getAssessmentInfo($idOrUuid, array $identity): Dyscalculia
    {
        $user = $this->resolveUser($identity);
        $repo = $this->entityManager->getRepository(Dyscalculia::class);

        $dyscalculia = null;
        if (is_numeric($idOrUuid)) {
            $dyscalculia = $repo->find((int) $idOrUuid);
        }
        if (!$dyscalculia) {
            $dyscalculia = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$dyscalculia) {
            $dyscalculia = $repo->findOneBy(['uniqueIdentifier' => $idOrUuid]);
        }

        if (!$dyscalculia) {
            throw new \Exception("Assessment record not found.");
        }

        if ($dyscalculia->getWard()->getUser()->getId() !== $user->getId()) {
            throw new \Exception("Access denied. You do not have permission to view this record.");
        }

        return $dyscalculia;
    }

    /**
     * Update an existing assessment.
     *
     * @param string|int $idOrUuid
     * @param array $data
     * @param array $identity
     * @return Dyscalculia
     * @throws \Exception
     */
    public function update($idOrUuid, array $data, array $identity): Dyscalculia
    {
        $dyscalculia = $this->getAssessmentInfo($idOrUuid, $identity);

        if (isset($data['arithmetic_score'])) {
            $dyscalculia->setArithmeticScore((int) $data['arithmetic_score']);
        }
        if (isset($data['number_sense_score'])) {
            $dyscalculia->setNumberSenseScore((int) $data['number_sense_score']);
        }
        if (isset($data['spatial_reasoning_score'])) {
            $dyscalculia->setSpatialReasoningScore((int) $data['spatial_reasoning_score']);
        }

        // Recompute total
        $total = $dyscalculia->getArithmeticScore() +
                 $dyscalculia->getNumberSenseScore() +
                 $dyscalculia->getSpatialReasoningScore();
        $dyscalculia->setTotalScore($total);

        if (!empty($data['severity_level'])) {
            $dyscalculia->setSeverityLevel($data['severity_level']);
        }

        if (!empty($data['assessment_date'])) {
            $dateStr = $data['assessment_date'];
            $assessmentDate = \DateTime::createFromFormat('Y-m-d', $dateStr);
            if (!$assessmentDate || $assessmentDate->format('Y-m-d') !== $dateStr) {
                throw new \Exception("Invalid assessment date format. Use YYYY-MM-DD.");
            }
            $dyscalculia->setAssessmentDate($assessmentDate);
        }

        if (array_key_exists('notes', $data)) {
            $dyscalculia->setNotes($data['notes']);
        }

        $this->entityManager->flush();

        return $dyscalculia;
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
        $dyscalculia = $this->getAssessmentInfo($idOrUuid, $identity);
        $this->entityManager->remove($dyscalculia);
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
