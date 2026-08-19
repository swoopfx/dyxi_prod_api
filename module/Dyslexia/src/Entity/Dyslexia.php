<?php

namespace Dyslexia\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ward\Entity\Ward;

/**
 * Dyslexia
 *
 * @ORM\Table(name="dyslexia_assessments")
 * @ORM\Entity
 */
class Dyslexia
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="uuid", type="string", length=36, nullable=false, unique=true)
     */
    private $uuid;

    /**
     * @var string
     * @ORM\Column(name="unique_identifier", type="string", length=255, nullable=false, unique=true)
     */
    private $uniqueIdentifier;

    /**
     * @var Ward
     * @ORM\ManyToOne(targetEntity="Ward\Entity\Ward")
     * @ORM\JoinColumn(name="ward_id", referencedColumnName="id", nullable=false)
     */
    private $ward;

    /**
     * @var \DateTime
     * @ORM\Column(name="assessment_date", type="date", nullable=false)
     */
    private $assessmentDate;

    /**
     * @var int
     * @ORM\Column(name="phonological_awareness_score", type="integer", nullable=false)
     */
    private $phonologicalAwarenessScore;

    /**
     * @var int
     * @ORM\Column(name="rapid_naming_score", type="integer", nullable=false)
     */
    private $rapidNamingScore;

    /**
     * @var int
     * @ORM\Column(name="word_reading_score", type="integer", nullable=false)
     */
    private $wordReadingScore;

    /**
     * @var int
     * @ORM\Column(name="total_score", type="integer", nullable=false)
     */
    private $totalScore;

    /**
     * @var string
     * @ORM\Column(name="subtype", type="string", length=255, nullable=false)
     */
    private $subtype;

    /**
     * @var string|null
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     * @return Dyslexia
     */
    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Get unique identifier
     *
     * @return string
     */
    public function getUniqueIdentifier()
    {
        return $this->uniqueIdentifier;
    }

    /**
     * Set unique identifier
     *
     * @param string $uniqueIdentifier
     * @return Dyslexia
     */
    public function setUniqueIdentifier(string $uniqueIdentifier)
    {
        $this->uniqueIdentifier = $uniqueIdentifier;
        return $this;
    }

    /**
     * Get associated ward
     *
     * @return Ward
     */
    public function getWard()
    {
        return $this->ward;
    }

    /**
     * Set associated ward
     *
     * @param Ward $ward
     * @return Dyslexia
     */
    public function setWard(Ward $ward)
    {
        $this->ward = $ward;
        return $this;
    }

    /**
     * Get assessment date
     *
     * @return \DateTime
     */
    public function getAssessmentDate()
    {
        return $this->assessmentDate;
    }

    /**
     * Set assessment date
     *
     * @param \DateTime $assessmentDate
     * @return Dyslexia
     */
    public function setAssessmentDate(\DateTime $assessmentDate)
    {
        $this->assessmentDate = $assessmentDate;
        return $this;
    }

    /**
     * Get phonological awareness score
     *
     * @return int
     */
    public function getPhonologicalAwarenessScore()
    {
        return $this->phonologicalAwarenessScore;
    }

    /**
     * Set phonological awareness score
     *
     * @param int $phonologicalAwarenessScore
     * @return Dyslexia
     */
    public function setPhonologicalAwarenessScore(int $phonologicalAwarenessScore)
    {
        $this->phonologicalAwarenessScore = $phonologicalAwarenessScore;
        return $this;
    }

    /**
     * Get rapid naming score
     *
     * @return int
     */
    public function getRapidNamingScore()
    {
        return $this->rapidNamingScore;
    }

    /**
     * Set rapid naming score
     *
     * @param int $rapidNamingScore
     * @return Dyslexia
     */
    public function setRapidNamingScore(int $rapidNamingScore)
    {
        $this->rapidNamingScore = $rapidNamingScore;
        return $this;
    }

    /**
     * Get word reading score
     *
     * @return int
     */
    public function getWordReadingScore()
    {
        return $this->wordReadingScore;
    }

    /**
     * Set word reading score
     *
     * @param int $wordReadingScore
     * @return Dyslexia
     */
    public function setWordReadingScore(int $wordReadingScore)
    {
        $this->wordReadingScore = $wordReadingScore;
        return $this;
    }

    /**
     * Get total score
     *
     * @return int
     */
    public function getTotalScore()
    {
        return $this->totalScore;
    }

    /**
     * Set total score
     *
     * @param int $totalScore
     * @return Dyslexia
     */
    public function setTotalScore(int $totalScore)
    {
        $this->totalScore = $totalScore;
        return $this;
    }

    /**
     * Get subtype
     *
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * Set subtype
     *
     * @param string $subtype
     * @return Dyslexia
     */
    public function setSubtype(string $subtype)
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * Get notes
     *
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set notes
     *
     * @param string|null $notes
     * @return Dyslexia
     */
    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
        return $this;
    }
}
