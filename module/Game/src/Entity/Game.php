<?php

namespace Game\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Game
 *
 * @ORM\Table(name="games")
 * @ORM\Entity
 */
class Game
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
     * @var string
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    private $title;

    /**
     * @var string|null
     * @ORM\Column(name="summary", type="text", nullable=true)
     */
    private $summary;

    /**
     * @var string|null
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var GameType
     * @ORM\ManyToOne(targetEntity="Game\Entity\GameType")
     * @ORM\JoinColumn(name="game_type_id", referencedColumnName="id", nullable=false)
     */
    private $gameType;

    /**
     * @var Curriculum|null
     * @ORM\ManyToOne(targetEntity="Game\Entity\Curriculum")
     * @ORM\JoinColumn(name="curriculum_id", referencedColumnName="id", nullable=true)
     */
    private $curriculum;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="Game\Entity\TargetTags")
     * @ORM\JoinTable(name="game_target_tags",
     *      joinColumns={@ORM\JoinColumn(name="game_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     */
    private $targetTags;

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
        $this->targetTags = new ArrayCollection();
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

    public function getUniqueIdentifier(): ?string
    {
        return $this->uniqueIdentifier;
    }

    public function setUniqueIdentifier(string $uniqueIdentifier): self
    {
        $this->uniqueIdentifier = $uniqueIdentifier;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): self
    {
        $this->summary = $summary;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getGameType(): ?GameType
    {
        return $this->gameType;
    }

    public function setGameType(GameType $gameType): self
    {
        $this->gameType = $gameType;
        return $this;
    }

    public function getCurriculum(): ?Curriculum
    {
        return $this->curriculum;
    }

    public function setCurriculum(?Curriculum $curriculum): self
    {
        $this->curriculum = $curriculum;
        return $this;
    }

    public function getTargetTags(): Collection
    {
        return $this->targetTags;
    }

    public function addTargetTag(TargetTags $targetTag): self
    {
        if (!$this->targetTags->contains($targetTag)) {
            $this->targetTags->add($targetTag);
        }
        return $this;
    }

    public function removeTargetTag(TargetTags $targetTag): self
    {
        $this->targetTags->removeElement($targetTag);
        return $this;
    }

    public function setTargetTags(Collection $targetTags): self
    {
        $this->targetTags = $targetTags;
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
