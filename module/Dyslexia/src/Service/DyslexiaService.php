<?php

namespace Dyslexia\Service;

use Doctrine\ORM\EntityManager;
use Authentication\Entity\User;
use Ward\Entity\Ward;
use Dyslexia\Entity\Dyslexia;
use Ramsey\Uuid\Uuid;

class DyslexiaService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * DyslexiaService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Register a new Dyslexia assessment.
     *
     * @param array $data Input parameters
     * @param array $identity Authenticated user's identity claims
     * @return Dyslexia
     * @throws \Exception
     */
    public function register(array $data, array $identity): Dyslexia
    {
        $user = $this->resolveUser($identity);

        if (empty($data['ward_id'])) {
            throw new \Exception("Ward ID (ward_id) is required.");
        }

        $ward = $this->resolveWard($data['ward_id'], $user);

        // Validate required scores
        if (!isset($data['phonological_awareness_score'])) {
            throw new \Exception("Phonological awareness score is required.");
        }
        if (!isset($data['rapid_naming_score'])) {
            throw new \Exception("Rapid naming score is required.");
        }
        if (!isset($data['word_reading_score'])) {
            throw new \Exception("Word reading score is required.");
        }

        $pas = (int) $data['phonological_awareness_score'];
        $rns = (int) $data['rapid_naming_score'];
        $wrs = (int) $data['word_reading_score'];
        $total = $pas + $rns + $wrs;

        if (empty($data['subtype'])) {
            throw new \Exception("Dyslexia subtype is required.");
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
            $uniqueIdentifier = 'DYS-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));
        }

        $existing = $this->entityManager->getRepository(Dyslexia::class)->findOneBy([
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
            $existingUuid = $this->entityManager->getRepository(Dyslexia::class)->findOneBy([
                'uuid' => $uuid
            ]);
            if ($existingUuid) {
                throw new \Exception("An assessment with this UUID already exists.");
            }
        }

        $dyslexia = new Dyslexia();
        $dyslexia->setUuid($uuid)
                 ->setUniqueIdentifier($uniqueIdentifier)
                 ->setWard($ward)
                 ->setAssessmentDate($assessmentDate)
                 ->setPhonologicalAwarenessScore($pas)
                 ->setRapidNamingScore($rns)
                 ->setWordReadingScore($wrs)
                 ->setTotalScore($total)
                 ->setSubtype($data['subtype'])
                 ->setNotes($data['notes'] ?? null);

        $this->entityManager->persist($dyslexia);
        $this->entityManager->flush();

        return $dyslexia;
    }

    /**
     * List all dyslexia assessments for the authenticated user's wards.
     *
     * @param array $identity Authenticated user's identity claims
     * @return array
     * @throws \Exception
     */
    public function listAssessments(array $identity): array
    {
        $user = $this->resolveUser($identity);

        // Find all wards belonging to this user
        $wards = $this->entityManager->getRepository(Ward::class)->findBy([
            'user' => $user
        ]);

        if (empty($wards)) {
            return [];
        }

        return $this->entityManager->getRepository(Dyslexia::class)->findBy([
            'ward' => $wards
        ]);
    }

    /**
     * Retrieve detailed dyslexia assessment if belongs to user's ward.
     *
     * @param string|int $idOrUuid ID, UUID or Unique Identifier
     * @param array $identity Authenticated user's identity claims
     * @return Dyslexia
     * @throws \Exception
     */
    public function getAssessmentInfo($idOrUuid, array $identity): Dyslexia
    {
        $user = $this->resolveUser($identity);
        $repo = $this->entityManager->getRepository(Dyslexia::class);

        $dyslexia = null;
        if (is_numeric($idOrUuid)) {
            $dyslexia = $repo->find((int) $idOrUuid);
        }
        if (!$dyslexia) {
            $dyslexia = $repo->findOneBy(['uuid' => $idOrUuid]);
        }
        if (!$dyslexia) {
            $dyslexia = $repo->findOneBy(['uniqueIdentifier' => $idOrUuid]);
        }

        if (!$dyslexia) {
            throw new \Exception("Assessment record not found.");
        }

        // Verify authorization (ward belongs to this user)
        if ($dyslexia->getWard()->getUser()->getId() !== $user->getId()) {
            throw new \Exception("Access denied. You do not have permission to view this record.");
        }

        return $dyslexia;
    }

    /**
     * Update an existing assessment.
     *
     * @param string|int $idOrUuid
     * @param array $data
     * @param array $identity
     * @return Dyslexia
     * @throws \Exception
     */
    public function update($idOrUuid, array $data, array $identity): Dyslexia
    {
        $dyslexia = $this->getAssessmentInfo($idOrUuid, $identity);

        if (isset($data['phonological_awareness_score'])) {
            $dyslexia->setPhonologicalAwarenessScore((int) $data['phonological_awareness_score']);
        }
        if (isset($data['rapid_naming_score'])) {
            $dyslexia->setRapidNamingScore((int) $data['rapid_naming_score']);
        }
        if (isset($data['word_reading_score'])) {
            $dyslexia->setWordReadingScore((int) $data['word_reading_score']);
        }

        // Recompute total
        $total = $dyslexia->getPhonologicalAwarenessScore() +
                 $dyslexia->getRapidNamingScore() +
                 $dyslexia->getWordReadingScore();
        $dyslexia->setTotalScore($total);

        if (!empty($data['subtype'])) {
            $dyslexia->setSubtype($data['subtype']);
        }

        if (!empty($data['assessment_date'])) {
            $dateStr = $data['assessment_date'];
            $assessmentDate = \DateTime::createFromFormat('Y-m-d', $dateStr);
            if (!$assessmentDate || $assessmentDate->format('Y-m-d') !== $dateStr) {
                throw new \Exception("Invalid assessment date format. Use YYYY-MM-DD.");
            }
            $dyslexia->setAssessmentDate($assessmentDate);
        }

        if (array_key_exists('notes', $data)) {
            $dyslexia->setNotes($data['notes']);
        }

        $this->entityManager->flush();

        return $dyslexia;
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
        $dyslexia = $this->getAssessmentInfo($idOrUuid, $identity);
        $this->entityManager->remove($dyslexia);
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
            $ward = $repo->findOneBy(['uniqueIdentifier' => $wardIdOrUuid]);
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
