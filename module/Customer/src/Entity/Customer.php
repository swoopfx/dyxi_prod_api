<?php

namespace Customer\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="customer")
 */

class Customer
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Undocumented variable
     * @ORM\OneToOne(targetEntity="Authentication\Entity\User", inversedBy="customer")
     * @var User
     */
    private $user;


    /**
     * @ORM\Column(nullable=true)
     *
     * @var string
     */
    private $address;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $addressPlaceId;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $addressLongitude;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $addressLatitude;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $phone;

    /**
     * Customer Uid
     * @ORM\Column(nullable=false)
     * @var string
     */
    private $customerUid;

    /**
     * UUID
     * @ORM\Column(nullable=false)
     * @var string
     */
    private $customerUuid;

    /**
     * If customer has been disabled
     *  @ORM\Column(type="boolean", nullable=false , options={"default": 1})
     * @var bool
     */
    private $isActive;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime")
     * @var Datetime
     */
    private $createdOn;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime")
     * @var \Datetime
     */
    private $updatedOn;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $customLandmark;



    /**
     * Get customer Uid
     *
     * @return  string
     */
    public function getCustomerUid()
    {
        return $this->customerUid;
    }

    /**
     * Set customer Uid
     *
     * @param  string  $customerUid  Customer Uid
     *
     * @return  self
     */
    public function setCustomerUid(string $customerUid)
    {
        $this->customerUid = $customerUid;

        return $this;
    }

    /**
     * Get uUID
     *
     * @return  string
     */
    public function getCustomerUuid()
    {
        return $this->customerUuid;
    }

    /**
     * Set uUID
     *
     * @param  string  $customerUuid  UUID
     *
     * @return  self
     */
    public function setCustomerUuid(string $customerUuid)
    {
        $this->customerUuid = $customerUuid;

        return $this;
    }

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
     * Get undocumented variable
     *
     * @return  string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set undocumented variable
     *
     * 
     *
     * @return  self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of address
     *
     * @return  string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set the value of address
     *
     * @param  string  $address
     *
     * @return  self
     */
    public function setAddress(string $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getAddressPlaceId()
    {
        return $this->addressPlaceId;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $addressPlaceId  Undocumented variable
     *
     * @return  self
     */
    public function setAddressPlaceId(string $addressPlaceId)
    {
        $this->addressPlaceId = $addressPlaceId;

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
     * @param  Datetime  $createdOn  Undocumented variable
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
     * @return  string
     */
    public function getAddressLongitude()
    {
        return $this->addressLongitude;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $addressLongitude  Undocumented variable
     *
     * @return  self
     */
    public function setAddressLongitude(string $addressLongitude)
    {
        $this->addressLongitude = $addressLongitude;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getAddressLatitude()
    {
        return $this->addressLatitude;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $addressLatitude  Undocumented variable
     *
     * @return  self
     */
    public function setAddressLatitude(string $addressLatitude)
    {
        $this->addressLatitude = $addressLatitude;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $phone  Undocumented variable
     *
     * @return  self
     */
    public function setPhone(string $phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get if customer has been disabled
     *
     * @return  bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set if customer has been disabled
     *
     * @param  bool  $isActive  If customer has been disabled
     *
     * @return  self
     */
    public function setIsActive(bool $isActive)
    {
        $this->isActive = $isActive;

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

    /**
     * Get undocumented variable
     *
     * @return  string
     */ 
    public function getCustomLandmark()
    {
        return $this->customLandmark;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $customLandmark  Undocumented variable
     *
     * @return  self
     */ 
    public function setCustomLandmark(string $customLandmark)
    {
        $this->customLandmark = $customLandmark;

        return $this;
    }
}
