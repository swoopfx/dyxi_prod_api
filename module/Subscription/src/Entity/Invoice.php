<?php

namespace Subscription\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;
use Ward\Entity\Ward;

/**
 * Invoice
 *
 * @ORM\Table(name="invoices")
 * @ORM\Entity
 */
class Invoice
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID    = 'paid';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

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
     * @ORM\Column(name="invoice_number", type="string", length=50, nullable=false, unique=true)
     */
    private $invoiceNumber;

    /**
     * @var string|null
     * @ORM\Column(name="reference_code", type="string", length=100, nullable=true, unique=true)
     */
    private $referenceCode;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var Ward
     * @ORM\ManyToOne(targetEntity="Ward\Entity\Ward")
     * @ORM\JoinColumn(name="ward_id", referencedColumnName="id", nullable=false)
     */
    private $ward;

    /**
     * @var SubscriptionType
     * @ORM\ManyToOne(targetEntity="Subscription\Entity\SubscriptionType")
     * @ORM\JoinColumn(name="subscription_type_id", referencedColumnName="id", nullable=false)
     */
    private $subscriptionType;

    /**
     * @var float
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $amount;

    /**
     * @var float
     * @ORM\Column(name="vat_rate", type="decimal", precision=5, scale=2, nullable=false)
     */
    private $vatRate = 7.50;

    /**
     * @var float
     * @ORM\Column(name="vat_amount", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $vatAmount = 0.00;

    /**
     * @var float
     * @ORM\Column(name="subtotal", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $subtotal = 0.00;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string", length=10, nullable=false)
     */
    private $currency = 'NGN';

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=30, nullable=false)
     */
    private $status = self::STATUS_PENDING;

    /**
     * @var string|null
     * @ORM\Column(name="paystack_reference", type="string", length=100, nullable=true)
     */
    private $paystackReference;

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

    /**
     * @var \DateTime|null
     * @ORM\Column(name="paid_on", type="datetime", nullable=true)
     */
    private $paidOn;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
        $this->updatedOn = new \DateTime();
        $this->status = self::STATUS_PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getInvoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getWard(): Ward
    {
        return $this->ward;
    }

    public function setWard(Ward $ward): self
    {
        $this->ward = $ward;
        return $this;
    }

    public function getSubscriptionType(): SubscriptionType
    {
        return $this->subscriptionType;
    }

    public function setSubscriptionType(SubscriptionType $subscriptionType): self
    {
        $this->subscriptionType = $subscriptionType;
        return $this;
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getVatRate(): float
    {
        return (float) $this->vatRate;
    }

    public function setVatRate(float $vatRate): self
    {
        $this->vatRate = $vatRate;
        return $this;
    }

    public function getVatAmount(): float
    {
        return (float) $this->vatAmount;
    }

    public function setVatAmount(float $vatAmount): self
    {
        $this->vatAmount = $vatAmount;
        return $this;
    }

    public function getSubtotal(): float
    {
        return (float) $this->subtotal;
    }

    public function setSubtotal(float $subtotal): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedOn = new \DateTime();
        return $this;
    }

    public function getPaystackReference(): ?string
    {
        return $this->paystackReference;
    }

    public function setPaystackReference(?string $paystackReference): self
    {
        $this->paystackReference = $paystackReference;
        return $this;
    }

    public function getCreatedOn(): \DateTime
    {
        return $this->createdOn;
    }

    public function setCreatedOn(\DateTime $createdOn): self
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    public function getUpdatedOn(): \DateTime
    {
        return $this->updatedOn;
    }

    public function setUpdatedOn(\DateTime $updatedOn): self
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }

    public function getPaidOn(): ?\DateTime
    {
        return $this->paidOn;
    }

    public function setPaidOn(?\DateTime $paidOn): self
    {
        $this->paidOn = $paidOn;
        return $this;
    }

    public function getReferenceCode(): ?string
    {
        return $this->referenceCode;
    }

    public function setReferenceCode(?string $referenceCode): self
    {
        $this->referenceCode = $referenceCode;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->getId(),
            'uuid'               => $this->getUuid(),
            'invoice_number'     => $this->getInvoiceNumber(),
            'reference_code'     => $this->getReferenceCode(),
            'user_id'            => $this->getUser() ? $this->getUser()->getId() : null,
            'user_email'         => $this->getUser() ? $this->getUser()->getEmail() : null,
            'ward_id'            => $this->getWard() ? $this->getWard()->getId() : null,
            'ward_name'          => $this->getWard() ? $this->getWard()->getFullname() : null,
            'subscription_type'  => $this->getSubscriptionType() ? $this->getSubscriptionType()->toArray() : null,
            'amount'             => $this->getAmount(),
            'subtotal'           => $this->getSubtotal(),
            'vat_rate'           => $this->getVatRate(),
            'vat_amount'         => $this->getVatAmount(),
            'vat_inclusive'      => true,
            'currency'           => $this->getCurrency(),
            'status'             => $this->getStatus(),
            'paystack_reference' => $this->getPaystackReference(),
            'created_on'         => $this->getCreatedOn() ? $this->getCreatedOn()->format('Y-m-d H:i:s') : null,
            'paid_on'            => $this->getPaidOn() ? $this->getPaidOn()->format('Y-m-d H:i:s') : null,
        ];
    }
}
