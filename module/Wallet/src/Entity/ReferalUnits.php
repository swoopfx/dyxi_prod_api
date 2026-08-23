<?php

namespace Wallet\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="referal_unit")
 *
 * @author otaba
 *
 */
class ReferalUnits
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
/**
     * @ORM\OneToOne(targetEntity="Authentication\Entity\User", inversedBy="referalUnit")
     *
     * @var User
     */
    private $user;
/**
     * @ORM\Column(name="runits", type="string", nullable=true)
     *
     * @var string
     */
    private $runits;
/**
     * @ORM\Column(name="created_on", type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $createdOn;
/**
     * @ORM\Column(name="updated_on", type="datetime", nullable=true)
     *
     * @var unknown
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
     * @return the $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return the $runits
     */
    public function getRunits()
    {
        return $this->runits;
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
     * @param \CsnUser\Entity\User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $runits
     */
    public function setRunits($runits)
    {
        $this->runits = $runits;
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
     * @param \Wallet\Entity\unknown $updatedOn
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;
        return $this;
    }
}
