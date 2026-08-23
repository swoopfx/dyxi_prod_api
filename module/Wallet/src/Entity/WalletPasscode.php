<?php

namespace Wallet\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Entity
 * @ORM\Table(name="wallet_passcode")
 *
 * @author otaba
 *
 */
class WalletPasscode
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
     * @ORM\OneToOne(targetEntity="Wallet", inversedBy="passcode")
     * @var Wallet
     */
    private $wallet;
/**
     *
     * @ORM\Column(name="password", type="string", length=60, nullable=false)
     * @var string
     */
    private $passcode;
// TODO - Insert your code here
    public function __construct()
    {

        // TODO - Insert your code here
    }
    /**
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Wallet\Entity\Wallet
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * @return string
     */
    public function getPasscode()
    {
        return $this->passcode;
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
     * @param \Wallet\Entity\Wallet $wallet
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;
        return $this;
    }

    /**
     * @param string $passcode
     */
    public function setPasscode($passcode)
    {
        $this->passcode = $passcode;
        return $this;
    }
}
