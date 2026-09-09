<?php

namespace Consultant\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;
use Consultant\Entity\ConsultantCategory;

/**
 * Consultant
 *
 * @ORM\Table(name="consultants")
 * @ORM\Entity
 */
class Consultant
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
     * @ORM\Column(name="fullname", type="string", length=255, nullable=false)
     */
    private $fullname;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=255, nullable=false, unique=true)
     */
    private $email;

    /**
     * @var string|null
     * @ORM\Column(name="phone", type="string", length=50, nullable=true)
     */
    private $phone;

    /**
     * @var string|null
     * @ORM\Column(name="title", type="string", length=100, nullable=true)
     */
    private $title;

    /**
     * @var string|null
     * @ORM\Column(name="introduction", type="text", nullable=true)
     */
    private $introduction;

    /**
     * @var string|null
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var ConsultantCategory|null
     * @ORM\ManyToOne(targetEntity="Consultant\Entity\ConsultantCategory")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $category;

    /**
     * @var string|null
     * @ORM\Column(name="speciality", type="string", length=255, nullable=true)
     */
    private $speciality;

    /**
     * @var string|null
     * @ORM\Column(name="specialization", type="string", length=255, nullable=true)
     */
    private $specialization;

    /**
     * @var string|null
     * @ORM\Column(name="bio", type="text", nullable=true)
     */
    private $bio;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=50, nullable=false)
     */
    private $status;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $user;

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
        $this->status = 'active';
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

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): self
    {
        $this->introduction = $introduction;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? $this->bio;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->bio = $description;
        return $this;
    }

    public function getCategory(): ?ConsultantCategory
    {
        return $this->category;
    }

    public function setCategory(?ConsultantCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getSpeciality(): ?string
    {
        return $this->speciality ?? $this->specialization;
    }

    public function setSpeciality(?string $speciality): self
    {
        $this->speciality = $speciality;
        $this->specialization = $speciality;
        return $this;
    }

    public function getSpecialization(): ?string
    {
        return $this->specialization ?? $this->speciality;
    }

    public function setSpecialization(?string $specialization): self
    {
        $this->specialization = $specialization;
        $this->speciality = $specialization;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio ?? $this->description;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        $this->description = $bio;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
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
