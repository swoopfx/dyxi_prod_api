<?php

namespace Subscription\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubscriptionType
 *
 * @ORM\Table(name="subscription_types")
 * @ORM\Entity
 */
class SubscriptionType
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=100, nullable=false, unique=true)
     */
    private $code;

    /**
     * @var float
     * @ORM\Column(name="amount_ngn", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $amountNgn;

    /**
     * @var float
     * @ORM\Column(name="amount_usd", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $amountUsd;

    /**
     * @var int
     * @ORM\Column(name="interval_months", type="integer", nullable=false)
     */
    private $intervalMonths = 1;

    /**
     * @var string|null
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private $description;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     */
    private $createdOn;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function getAmountNgn(): float
    {
        return (float) $this->amountNgn;
    }

    public function setAmountNgn(float $amountNgn): self
    {
        $this->amountNgn = $amountNgn;
        return $this;
    }

    public function getAmountUsd(): float
    {
        return (float) $this->amountUsd;
    }

    public function setAmountUsd(float $amountUsd): self
    {
        $this->amountUsd = $amountUsd;
        return $this;
    }

    public function getIntervalMonths(): int
    {
        return $this->intervalMonths;
    }

    public function setIntervalMonths(int $intervalMonths): self
    {
        $this->intervalMonths = $intervalMonths;
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

    public function getCreatedOn(): ?\DateTime
    {
        return $this->createdOn;
    }

    public function setCreatedOn(?\DateTime $createdOn): self
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->getId(),
            'name'            => $this->getName(),
            'code'            => $this->getCode(),
            'amount_ngn'      => $this->getAmountNgn(),
            'amount_usd'      => $this->getAmountUsd(),
            'interval_months' => $this->getIntervalMonths(),
            'description'     => $this->getDescription(),
        ];
    }
}
