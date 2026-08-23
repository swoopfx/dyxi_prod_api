<?php

namespace Wallet\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * THese are subsequent activities taken place on the wallet
 * which is a whole activity
 * including
 * book Balnce activity,
 * balance activity
 * Credit
 * Debit
 * Password Creation
 * Password Edit
 *
 * @ORM\Entity
 * @ORM\Table(name="wallet_activity")
 * @author otaba
 *
 */
class WalletActivity
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
/**
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     * @var string
     */
    private $name;
/**
     *
     * @ORM\ManyToOne(targetEntity="WalletActivityType")
     * @var WalletActivityType
     */
    private $type;
/**
     *
     * @ORM\Column(name="descriptions", type="text", nullable=true)
     * @var string
     */
    private $desc;
/**
     *
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $createdOn;
/**
     * @ORM\ManyToOne(targetEntity="Wallet", inversedBy="walletActivity")
     * @var Wallet
     */
    private $wallet;
// TODO - Insert your code here
    public function __construct()
    {

        // TODO - Insert your code here
    }

    private function getId()
    {
        return $this->id;
    }

    // private function activ

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @return mixed
     */
    public function getDesc()
    {
        return $this->desc;
    }

    /**
     *
     * @return mixed
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     *
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     *
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     *
     * @param mixed $desc
     */
    public function setDesc($desc)
    {
        $this->desc = $desc;
        return $this;
    }

    /**
     *
     * @param mixed $createdOn
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        return $this;
    }
    /**
     * @return \Wallet\Entity\Wallet
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * @param \Wallet\Entity\Wallet $wallet
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;
        return $this;
    }
}
