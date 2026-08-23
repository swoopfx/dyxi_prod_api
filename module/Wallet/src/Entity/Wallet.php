<?php

namespace Wallet\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Authentication\Entity\User;

/**
 * This is is an instance of the wallet at any given time
 * @ORM\Entity(repositoryClass="Wallet\Entity\Repository\WalletRepository")
 * @ORM\Table(name="wallet")
 * @author otaba
 *
 */
class Wallet
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
     * @ORM\Column(name="wallet_uid", type="string", nullable=true)
     * @var string
     */
    private $walletUid;


    /**
     * Undocumented variable
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $walletUuid;

    /**
     * This is available balance
     * This is all credit and payment that successfully went through
     * This is the balance withdrawal could be initiated from
     * @ORM\Column(name="balance", type="string", nullable=true, options={"default"="0"})
     * @var string
     */
    private $balance;

    //     /**
    //      * This is the balance that is meant to be
    //      * Which includes unfinalized and unsettled , and pending payment
    //      *
    //      * @ORM\Column(name="book_balance", type="string", nullable=true)
    //      * @var string Balance
    //      */
    //     private $bookBalance;

    /**
     *
     * @ORM\OneToOne(targetEntity="Authentication\Entity\User", inversedBy="wallet")
     * @var User
     */
    private $user;

    /**
     *
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $createdOn;

    /**
     *
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $updatedOn;

    // /**
    //  *
    //  * @ORM\OneToOne(targetEntity="WalletPasscode", mappedBy="wallet")
    //  * @var WalletPasscode
    //  */
    // private $passcode;

    /**
     * @ORM\OneToMany(targetEntity="WalletActivity", mappedBy="wallet")
     * @var Collection
     */
    private $walletActivity;

    // TODO - Insert your code here
    public function __construct()
    {

        // TODO - Insert your code here
    }

    /**
     *
     * @return number
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     *
     * @return string
     */
    public function getWalletUid()
    {
        return $this->walletUid;
    }

    /**
     *
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     *
     * @return \CsnUser\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     *
     * @return \DateTime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     *
     * @param number $id
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     *
     * @param string $walletUid
     */
    public function setWalletUid($walletUid)
    {
        $this->walletUid = $walletUid;
        return $this;
    }

    /**
     *
     * @param string $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     *
     * @param \CsnUser\Entity\User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     *
     * @param \DateTime $createdOn
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    /**
     *
     * @param \DateTime $updatedOn
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }




    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getWalletUuid()
    {
        return $this->walletUuid;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $walletUuid  Undocumented variable
     *
     * @return  self
     */
    public function setWalletUuid(string $walletUuid)
    {
        $this->walletUuid = $walletUuid;

        return $this;
    }
}
