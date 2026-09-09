<?php

namespace Consultant\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;
use Consultant\Entity\Consultant;

/**
 * ConsultantBooking
 *
 * @ORM\Table(name="consultant_bookings")
 * @ORM\Entity
 */
class ConsultantBooking
{
    const STATUS_INITIATED = 'initiated';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_EXECUTED = 'executed';
    const STATUS_CANCELLED = 'cancelled';

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
     * @var Consultant
     * @ORM\ManyToOne(targetEntity="Consultant\Entity\Consultant")
     * @ORM\JoinColumn(name="consultant_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $consultant;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @var \DateTime
     * @ORM\Column(name="appointment_date", type="datetime", nullable=false)
     */
    private $appointmentDate;

    /**
     * @var string
     * @ORM\Column(name="preferred_time", type="string", length=50, nullable=false)
     */
    private $preferredTime;

    /**
     * @var string|null
     * @ORM\Column(name="reason_for_visit", type="text", nullable=true)
     */
    private $reasonForVisit;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    private $status;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_on", type="datetime", nullable=false)
     */
    private $createdOn;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_on", type="datetime", nullable=false)
     */
    private $updatedOn;

    public function __construct()
    {
        $this->status = self::STATUS_INITIATED;
        $this->createdOn = new \DateTime();
        $this->updatedOn = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getConsultant(): ?Consultant
    {
        return $this->consultant;
    }

    public function setConsultant(Consultant $consultant): self
    {
        $this->consultant = $consultant;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getAppointmentDate(): ?\DateTime
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(\DateTime $appointmentDate): self
    {
        $this->appointmentDate = $appointmentDate;
        return $this;
    }

    public function getPreferredTime(): ?string
    {
        return $this->preferredTime;
    }

    public function setPreferredTime(string $preferredTime): self
    {
        $this->preferredTime = $preferredTime;
        return $this;
    }

    public function getReasonForVisit(): ?string
    {
        return $this->reasonForVisit;
    }

    public function setReasonForVisit(?string $reasonForVisit): self
    {
        $this->reasonForVisit = $reasonForVisit;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedOn(): ?\DateTime
    {
        return $this->createdOn;
    }

    public function setCreatedOn(\DateTime $createdOn): self
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    public function getUpdatedOn(): ?\DateTime
    {
        return $this->updatedOn;
    }

    public function setUpdatedOn(\DateTime $updatedOn): self
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }
}
