<?php
namespace Game\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="toddler_game_list")
 * @ORM\Entity
 */
class ToddlerGamesList
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
     * @ORM\ManyToOne(targetEntity="Game")
     * @ORM\JoinColumn(name="game_id", referencedColumnName="id")
     */
    private $gameId;

    /**
     * @ORM\Column(name="custom_config", type="json", nullable=true)
     */
    private $customeConfig;

    /**
     * @var bool
     * @ORM\Column(name="is_active", type="boolean", nullable=false, options={"default": true})
     */
    private $isActive = true;

    /**
     * @ORM\Column(name="created_on", type="datetime", nullable=false)
     */
    private $createdOn;

    /**
     * @ORM\Column(name="updated_on", type="datetime", nullable=false)
     */
    private $updatedOn;

    public function __construct()
    {
        $this->uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->isActive = true;
        $this->createdOn = new \DateTime();
        $this->updatedOn = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return mixed
     */
    public function getGameId()
    {
        return $this->gameId;
    }

    /**
     * @param mixed $gameId
     */
    public function setGameId($gameId)
    {
        $this->gameId = $gameId;
    }

    /**
     * @return mixed
     */
    public function getCustomeConfig()
    {
        return $this->customeConfig;
    }

    /**
     * @param mixed $customeConfig
     */
    public function setCustomeConfig($customeConfig)
    {
        $this->customeConfig = $customeConfig;
    }

    /**
     * Alias for getCustomeConfig
     */
    public function getCustomConfig()
    {
        return $this->getCustomeConfig();
    }

    /**
     * Alias for setCustomeConfig
     */
    public function setCustomConfig($customConfig)
    {
        $this->setCustomeConfig($customConfig);
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return (bool) $this->isActive;
    }

    /**
     * Alias for getIsActive
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getIsActive();
    }

    /**
     * @param bool $isActive
     * @return self
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = (bool) $isActive;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * @param \DateTime $createdOn
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * @param \DateTime $updatedOn
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;
    }
}
