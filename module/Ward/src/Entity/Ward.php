<?php

namespace Ward\Entity;

use Authentication\Entity\User;
use General\Entity\Gender;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ward
 *
 * @ORM\Table(name="wards")
 * @ORM\Entity
 */
class Ward
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
     * @ORM\Column(name="fullname", type="string", length=255, nullable=false)
     */
    private $fullname;

    /**
     * @var \DateTime
     * @ORM\Column(name="date_of_birth", type="date", nullable=false)
     */
    private $dateOfBirth;

    /**
     * @var string
     * @ORM\Column(name="uuid", type="string", length=36, nullable=false, unique=true)
     */
    private $uuid;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var WardStatus|null
     * @ORM\ManyToOne(targetEntity="Ward\Entity\WardStatus")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id", nullable=true)
     */
    private $status;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="expire_date", type="datetime", nullable=true)
     */
    private $expireDate;

    /**
     * @var Gender|null
     * @ORM\ManyToOne(targetEntity="General\Entity\Gender")
     * @ORM\JoinColumn(name="gender_id", referencedColumnName="id", nullable=true)
     */
    private $gender;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     */
    private $createdOn;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     */
    private $updatedOn;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
        $this->updatedOn = new \DateTime();
    }

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
     * Get fullname
     *
     * @return string
     */
    public function getFullname()
    {
        return $this->fullname;
    }

    /**
     * Set fullname
     *
     * @param string $fullname
     * @return Ward
     */
    public function setFullname(string $fullname)
    {
        $this->fullname = $fullname;
        return $this;
    }

    /**
     * Get date of birth
     *
     * @return \DateTime
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * Set date of birth
     *
     * @param \DateTime $dateOfBirth
     * @return Ward
     */
    public function setDateOfBirth(\DateTime $dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
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
     * @return Ward
     */
    public function setUuid(string $uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * Get associated user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set associated user
     *
     * @param User $user
     * @return Ward
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get status
     *
     * @return WardStatus|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param WardStatus|null $status
     * @return Ward
     */
    public function setStatus(?WardStatus $status)
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    public function setCreatedOn(\DateTime $createdOn)
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    public function setUpdatedOn(\DateTime $updatedOn)
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }

    /**
     * Get expire date
     *
     * @return \DateTime|null
     */
    public function getExpireDate(): ?\DateTime
    {
        return $this->expireDate;
    }

    /**
     * Set expire date
     *
     * @param \DateTime|null $expireDate
     * @return Ward
     */
    public function setExpireDate(?\DateTime $expireDate): self
    {
        $this->expireDate = $expireDate;
        return $this;
    }

    /**
     * Get calculated age from date of birth
     *
     * @return int
     */
    public function getAge(): int
    {
        if (! $this->dateOfBirth) {
            return 0;
        }
        return $this->dateOfBirth->diff(new \DateTime())->y;
    }

    /**
     * Get remaining hours until expiry date
     *
     * @return int|null
     */
    public function getExpireHours(): ?int
    {
        if (! $this->expireDate) {
            return null;
        }
        $now = new \DateTime();
        $diff = $now->diff($this->expireDate);
        $hours = ($diff->days * 24) + $diff->h;
        return $diff->invert ? -$hours : $hours;
    }

    /**
     * Get gender
     *
     * @return Gender|null
     */
    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    /**
     * Set gender
     *
     * @param Gender|null $gender
     * @return Ward
     */
    public function setGender(?Gender $gender): self
    {
        $this->gender = $gender;
        return $this;
    }
}
