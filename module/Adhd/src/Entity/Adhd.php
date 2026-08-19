<?php

namespace Adhd\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ward\Entity\Ward;

/**
 * Adhd
 *
 * @ORM\Table(name="adhd_assessments")
 * @ORM\Entity
 */
class Adhd
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
     * @ORM\Column(name="inattention_score", type="integer", nullable=false)
     */
    private $inattentionScore;

    /**
     * @var int
     * @ORM\Column(name="hyperactivity_score", type="integer", nullable=false)
     */
    private $hyperactivityScore;

    /**
     * @var int
     * @ORM\Column(name="total_score", type="integer", nullable=false)
     */
    private $totalScore;

    /**
     * @var string
     * @ORM\Column(name="diagnosis", type="string", length=255, nullable=false)
     */
    private $diagnosis;

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
     * @return Adhd
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
     * @return Adhd
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
     * @return Adhd
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
     * @return Adhd
     */
    public function setAssessmentDate(\DateTime $assessmentDate)
    {
        $this->assessmentDate = $assessmentDate;
        return $this;
    }

    /**
     * Get inattention score
     *
     * @return int
     */
    public function getInattentionScore()
    {
        return $this->inattentionScore;
    }

    /**
     * Set inattention score
     *
     * @param int $inattentionScore
     * @return Adhd
     */
    public function setInattentionScore(int $inattentionScore)
    {
        $this->inattentionScore = $inattentionScore;
        return $this;
    }

    /**
     * Get hyperactivity score
     *
     * @return int
     */
    public function getHyperactivityScore()
    {
        return $this->hyperactivityScore;
    }

    /**
     * Set hyperactivity score
     *
     * @param int $hyperactivityScore
     * @return Adhd
     */
    public function setHyperactivityScore(int $hyperactivityScore)
    {
        $this->hyperactivityScore = $hyperactivityScore;
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
     * @return Adhd
     */
    public function setTotalScore(int $totalScore)
    {
        $this->totalScore = $totalScore;
        return $this;
    }

    /**
     * Get diagnosis
     *
     * @return string
     */
    public function getDiagnosis()
    {
        return $this->diagnosis;
    }

    /**
     * Set diagnosis
     *
     * @param string $diagnosis
     * @return Adhd
     */
    public function setDiagnosis(string $diagnosis)
    {
        $this->diagnosis = $diagnosis;
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
     * @return Adhd
     */
    public function setNotes(?string $notes)
    {
        $this->notes = $notes;
        return $this;
    }
}
