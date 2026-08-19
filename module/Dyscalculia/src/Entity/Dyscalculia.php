<?php

namespace Dyscalculia\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ward\Entity\Ward;

/**
 * Dyscalculia
 *
 * @ORM\Table(name="dyscalculia_assessments")
 * @ORM\Entity
 */
class Dyscalculia
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
     * @ORM\Column(name="arithmetic_score", type="integer", nullable=false)
     */
    private $arithmeticScore;

    /**
     * @var int
     * @ORM\Column(name="number_sense_score", type="integer", nullable=false)
     */
    private $numberSenseScore;

    /**
     * @var int
     * @ORM\Column(name="spatial_reasoning_score", type="integer", nullable=false)
     */
    private $spatialReasoningScore;

    /**
     * @var int
     * @ORM\Column(name="total_score", type="integer", nullable=false)
     */
    private $totalScore;

    /**
     * @var string
     * @ORM\Column(name="severity_level", type="string", length=255, nullable=false)
     */
    private $severityLevel;

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
     * @return Dyscalculia
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
     * @return Dyscalculia
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
     * @return Dyscalculia
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
     * @return Dyscalculia
     */
    public function setAssessmentDate(\DateTime $assessmentDate)
    {
        $this->assessmentDate = $assessmentDate;
        return $this;
    }

    /**
     * Get arithmetic score
     *
     * @return int
     */
    public function getArithmeticScore()
    {
        return $this->arithmeticScore;
    }

    /**
     * Set arithmetic score
     *
     * @param int $arithmeticScore
     * @return Dyscalculia
     */
    public function setArithmeticScore(int $arithmeticScore)
    {
        $this->arithmeticScore = $arithmeticScore;
        return $this;
    }

    /**
     * Get number sense score
     *
     * @return int
     */
    public function getNumberSenseScore()
    {
        return $this->numberSenseScore;
    }

    /**
     * Set number sense score
     *
     * @param int $numberSenseScore
     * @return Dyscalculia
     */
    public function setNumberSenseScore(int $numberSenseScore)
    {
        $this->numberSenseScore = $numberSenseScore;
        return $this;
    }

    /**
     * Get spatial reasoning score
     *
     * @return int
     */
    public function getSpatialReasoningScore()
    {
        return $this->spatialReasoningScore;
    }

    /**
     * Set spatial reasoning score
     *
     * @param int $spatialReasoningScore
     * @return Dyscalculia
     */
    public function setSpatialReasoningScore(int $spatialReasoningScore)
    {
        $this->spatialReasoningScore = $spatialReasoningScore;
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
     * @return Dyscalculia
     */
    public function setTotalScore(int $totalScore)
    {
        $this->totalScore = $totalScore;
        return $this;
    }

    /**
     * Get severity level
     *
     * @return string
     */
    public function getSeverityLevel()
    {
        return $this->severityLevel;
    }

    /**
     * Set severity level
     *
     * @param string $severityLevel
     * @return Dyscalculia
     */
    public function setSeverityLevel(string $severityLevel)
    {
        $this->severityLevel = $severityLevel;
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
     * @return Dyscalculia
     */
    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
        return $this;
    }
}
