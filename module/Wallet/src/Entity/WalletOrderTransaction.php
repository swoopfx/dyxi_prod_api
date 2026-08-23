<?php

namespace Wallet\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shop\Entity\CartOrders;

/**
 * @ORM\Entity
 * @ORM\Table(name="wallet_order_transaction")
 * @author
 *
 */
class WalletOrderTransaction
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\Column(name="wallet_order_uid", type="string", nullable=true)
     * @var string
     */
    private $walletOrderUid;

    // /**
    //  * @ORM\ManyToOne(targetEntity="Shop\Entity\CartOrders")
    //  * @var CartOrders
    //  */
    // private $cartOrder;

    /**
     * @ORM\ManyToOne(targetEntity="Wallet")
     * @var Wallet
     */
    private $wallet;

    /**
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $createdOn;

    /**
     * @ORM\Column(name="amount", type="string", nullable=true)
     * @var string
     */
    private $amount;


    public function __construct()
    {

        // TODO - Insert your code here
    }
    /**
     * @return the $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return the $walletOrderUid
     */
    public function getWalletOrderUid()
    {
        return $this->walletOrderUid;
    }

    /**
     * @return the $cartOrder
     */
    public function getCartOrder()
    {
        return $this->cartOrder;
    }

    /**
     * @return the $wallet
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * @return the $createdOn
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * @return the $amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $walletOrderUid
     */
    public function setWalletOrderUid($walletOrderUid)
    {
        $this->walletOrderUid = $walletOrderUid;
        return $this;
    }

    /**
     * @param \Shop\Entity\CartOrders $cartOrder
     */
    public function setCartOrder($cartOrder)
    {
        $this->cartOrder = $cartOrder;
        return $this;
    }

    /**
     * @param \Wallet\Entity\Wallet $wallet
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;
        return $this;
    }

    /**
     * @param DateTime $createdOn
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    /**
     * @param string $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }
}
