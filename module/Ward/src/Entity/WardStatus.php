<?php

namespace Ward\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WardStatus
 *
 * @ORM\Table(name="ward_status")
 * @ORM\Entity
 */
class WardStatus
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE_ID = 1;
    public const STATUS_SUSPENDED_ID = 2;
    public const STATUS_PENDING_ID = 3;

    public const PRESET_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_PENDING,
    ];

    /**
     * @var int
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=45, nullable=false)
     */
    private $status;

    /**
     * Get id
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return WardStatus
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return WardStatus
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }
}
