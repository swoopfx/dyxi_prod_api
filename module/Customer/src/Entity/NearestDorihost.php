<?php

namespace Customer\Entity;

use Authentication\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="nearest_dorihost")
 */

class NearestDorihost
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Identity of the customer making the serach
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var User
     */
    private $customer;

    /**
     *identity of the nearest host
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var User
     */
    private $dorihost;

    /**
     * Distance away from the nearest host
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $distanceAway;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true, type="datetime")
     * @var \Datetime
     */
    private $createdOn;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true, type="datetime")
     * @var \Datetime
     */
    private $updatedOn;

    /**
     * Get @ORM\Column(name="id", type="integer")
     *
     * @return  integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get identity of the customer making the serach
     *
     * @return  User
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set identity of the customer making the serach
     *
     * @param  User  $customer  Identity of the customer making the serach
     *
     * @return  self
     */
    public function setCustomer(User $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get *identity of the nearest host
     *
     * @return  User
     */
    public function getDorihost()
    {
        return $this->dorihost;
    }

    /**
     * Set *identity of the nearest host
     *
     * @param  User  $dorihost  *identity of the nearest host
     *
     * @return  self
     */
    public function setDorihost(User $dorihost)
    {
        $this->dorihost = $dorihost;

        return $this;
    }

    /**
     * Get distance away from the nearest host
     *
     * @return  string
     */
    public function getDistanceAway()
    {
        return $this->distanceAway;
    }

    /**
     * Set distance away from the nearest host
     *
     * @param  string  $distanceAway  Distance away from the nearest host
     *
     * @return  self
     */
    public function setDistanceAway(string $distanceAway)
    {
        $this->distanceAway = $distanceAway;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  \Datetime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  \Datetime  $createdOn  Undocumented variable
     *
     * @return  self
     */
    public function setCreatedOn(\Datetime $createdOn)
    {
        $this->createdOn = $createdOn;
        $this->updatedOn = $createdOn;
        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  \Datetime
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  \Datetime  $updatedOn  Undocumented variable
     *
     * @return  self
     */
    public function setUpdatedOn(\Datetime $updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }
}
