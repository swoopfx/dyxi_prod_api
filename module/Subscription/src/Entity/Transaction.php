<?php

namespace Subscription\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;
use Ward\Entity\Ward;

/**
 * Transaction
 *
 * @ORM\Table(name="transactions")
 * @ORM\Entity
 */
class Transaction
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED  = 'failed';

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
     * @ORM\Column(name="transaction_reference", type="string", length=100, nullable=false, unique=true)
     */
    private $transactionReference;

    /**
     * @var Invoice|null
     * @ORM\ManyToOne(targetEntity="Subscription\Entity\Invoice")
     * @ORM\JoinColumn(name="invoice_id", referencedColumnName="id", nullable=true)
     */
    private $invoice;

    /**
     * @var User|null
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $user;

    /**
     * @var Ward|null
     * @ORM\ManyToOne(targetEntity="Ward\Entity\Ward")
     * @ORM\JoinColumn(name="ward_id", referencedColumnName="id", nullable=true)
     */
    private $ward;

    /**
     * @var float
     * @ORM\Column(name="amount", type="decimal", precision=10, scale=2, nullable=false)
     */
    private $amount;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string", length=10, nullable=false)
     */
    private $currency = 'NGN';

    /**
     * @var string
     * @ORM\Column(name="payment_method", type="string", length=50, nullable=false)
     */
    private $paymentMethod = 'paystack';

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=30, nullable=false)
     */
    private $status = self::STATUS_SUCCESS;

    /**
     * @var string|null
     * @ORM\Column(name="gateway_reference", type="string", length=100, nullable=true)
     */
    private $gatewayReference;

    /**
     * @var string|null
     * @ORM\Column(name="payment_details", type="text", nullable=true)
     */
    private $paymentDetails;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_on", type="datetime", nullable=false)
     */
    private $createdOn;

    public function __construct()
    {
        $this->createdOn = new \DateTime();
        $this->status = self::STATUS_SUCCESS;
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

    public function getTransactionReference(): string
    {
        return $this->transactionReference;
    }

    public function setTransactionReference(string $transactionReference): self
    {
        $this->transactionReference = $transactionReference;
        return $this;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;
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

    public function getWard(): ?Ward
    {
        return $this->ward;
    }

    public function setWard(?Ward $ward): self
    {
        $this->ward = $ward;
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

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getGatewayReference(): ?string
    {
        return $this->gatewayReference;
    }

    public function setGatewayReference(?string $gatewayReference): self
    {
        $this->gatewayReference = $gatewayReference;
        return $this;
    }

    public function getPaymentDetails(): ?string
    {
        return $this->paymentDetails;
    }

    public function setPaymentDetails($paymentDetails): self
    {
        if (is_array($paymentDetails)) {
            $this->paymentDetails = json_encode($paymentDetails);
        } else {
            $this->paymentDetails = $paymentDetails;
        }
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

    public function toArray(): array
    {
        return [
            'id'                    => $this->getId(),
            'uuid'                  => $this->getUuid(),
            'transaction_reference' => $this->getTransactionReference(),
            'invoice_id'            => $this->getInvoice() ? $this->getInvoice()->getId() : null,
            'user_id'               => $this->getUser() ? $this->getUser()->getId() : null,
            'ward_id'               => $this->getWard() ? $this->getWard()->getId() : null,
            'amount'                => $this->getAmount(),
            'currency'              => $this->getCurrency(),
            'payment_method'        => $this->getPaymentMethod(),
            'status'                => $this->getStatus(),
            'gateway_reference'     => $this->getGatewayReference(),
            'payment_details'       => $this->getPaymentDetails() ? json_decode($this->getPaymentDetails(), true) : null,
            'created_on'            => $this->getCreatedOn() ? $this->getCreatedOn()->format('Y-m-d H:i:s') : null,
        ];
    }
}
