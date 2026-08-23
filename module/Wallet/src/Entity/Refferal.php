<?php

namespace Wallet\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="referal_program", indexes={@ORM\index(name="id", columns={"referal_uid", "referal_code"})})
 *
 * @author otaba
 *
 */
class Refferal
{
    // TODO - Insert your code here

    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
/**
     * This is for internal use only, Auto generated code
     * @ORM\Column(name="referal_uid", type="string", nullable=true)
     *
     * @var string
     */
    private $referalUid;
/**
     * The user who made the refeeral
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     *
     * @var User
     */
    private $user;
/**
     * Referal code mainly for external code
     * @ORM\Column(name="referal_code", type="string", nullable=true)
     *
     * @var string
     */
    private $referalCode;
/**
     * @ORM\Column(name="referal_email", type="string", nullable=true)
     *
     * @var string
     */
    private $referalEmail;
/**
     * @ORM\Column(name="referal_phone", type="string", nullable=true)
     *
     * @var phone
     */
    private $referalPhone;
/**
     * Identfies if the refered has registerd
     * This is an indication that
     * @ORM\Column(name="is_registered", type="boolean", nullable=true)
     *
     * @var boolean
     */
    private $isRegistered;
/**
     * The account of the refered ( if registered)
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     *
     * @var User
     */
    private $referedAccount;
/**
     * @ORM\Column(name="registered_on", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $registeredOn;
/**
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $createdOn;
/**
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $updatedOn;
/**
     */
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
     * @return the $referalUid
     */
    public function getReferalUid()
    {
        return $this->referalUid;
    }

    /**
     * @return the $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return the $referalCode
     */
    public function getReferalCode()
    {
        return $this->referalCode;
    }

    /**
     * @return the $referalEmail
     */
    public function getReferalEmail()
    {
        return $this->referalEmail;
    }

    /**
     * @return the $referalPhone
     */
    public function getReferalPhone()
    {
        return $this->referalPhone;
    }

    /**
     * @return the $isRegistered
     */
    public function getIsRegistered()
    {
        return $this->isRegistered;
    }

    /**
     * @return the $referedAccount
     */
    public function getReferedAccount()
    {
        return $this->referedAccount;
    }

    /**
     * @return the $registeredOn
     */
    public function getRegisteredOn()
    {
        return $this->registeredOn;
    }

    /**
     * @return the $createdOn
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * @return the $updatedOn
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
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
     * @param string $referalUid
     */
    public function setReferalUid($referalUid)
    {
        $this->referalUid = $referalUid;
        return $this;
    }

    /**
     * @param \CsnUser\Entity\User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $referalCode
     */
    public function setReferalCode($referalCode)
    {
        $this->referalCode = $referalCode;
        return $this;
    }

    /**
     * @param string $referalEmail
     */
    public function setReferalEmail($referalEmail)
    {
        $this->referalEmail = $referalEmail;
        return $this;
    }

    /**
     * @param \Wallet\Entity\phone $referalPhone
     */
    public function setReferalPhone($referalPhone)
    {
        $this->referalPhone = $referalPhone;
        return $this;
    }

    /**
     * @param boolean $isRegistered
     */
    public function setIsRegistered($isRegistered)
    {
        $this->isRegistered = $isRegistered;
        return $this;
    }

    /**
     * @param \CsnUser\Entity\User $referedAccount
     */
    public function setReferedAccount($referedAccount)
    {
        $this->referedAccount = $referedAccount;
        return $this;
    }

    /**
     * @param DateTime $registeredOn
     */
    public function setRegisteredOn($registeredOn)
    {
        $this->registeredOn = $registeredOn;
        return $this;
    }

    /**
     * @param DateTime $createdOn
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        $this->updatedOn = $createdOn;
        return $this;
    }

    /**
     * @param DateTime $updatedOn
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }
}
