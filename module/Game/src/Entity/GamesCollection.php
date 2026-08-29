<?php

namespace Game\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * GamesCollection
 *
 * @ORM\Table(name="games_collections")
 * @ORM\Entity
 */
class GamesCollection
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false, unique=true)
     */
    private $name;

    /**
     * @var string|null
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="Game\Entity\Game")
     * @ORM\JoinTable(name="games_collection_games",
     *      joinColumns={@ORM\JoinColumn(name="collection_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="game_id", referencedColumnName="id", onDelete="CASCADE")}
     *      )
     */
    private $games;

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
        $this->games = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): self
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
        }
        return $this;
    }

    public function removeGame(Game $game): self
    {
        $this->games->removeElement($game);
        return $this;
    }

    public function setGames(Collection $games): self
    {
        $this->games = $games;
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
