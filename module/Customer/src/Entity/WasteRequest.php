<?php

namespace Customer\Entity;

use General\Entity\WasteRequestType;
use General\Entity\WasteType;
use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\User;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use General\Entity\WasteRequestState;
use General\Entity\EstimatedWeight;
use Customer\Entity\WasteRequestActivity;
use General\Entity\WasteCollectionType;

/**
 * @ORM\Entity
 * @ORM\Table(name="waste_request", indexes={@ORM\Index(name="id", columns={ "requestUuid", "requestId"})})
 */

class WasteRequest
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * User that made the request
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var \Authentication\Entity\User
     */
    private $user;

    /**
     * Undocumented variable
     * @ORM\ManyToone(targetEntity="General\Entity\EstimatedWeight")
     * @var EstimatedWeight
     */
    private $estimatedWeight;

    /**
     * Paper, Metal, Plastics , Compost
     * @ORM\ManyToOne(targetEntity="General\Entity\WasteType")
     * @var WasteType
     */
    private $wasteType;

    /**
     * Pickup or DroppOff
     * @ORM\ManyToOne(targetEntity="General\Entity\WasteRequestType")
     * @var WasteRequestType
     */
    private $requestType;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $pickupAddress;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $pickupPlaceId;

    /**
     * Longitude of Pick Up
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $longitude;

    /**
     * Date the Pick up or drop off would take place
     * @ORM\Column(type="datetime", nullable=true)
     * @var \Datetime
     */
    private $requestDatetime;

    /**
     * The date pickup by trash buster will occur
     * @ORM\Column(type="datetime", nullable=true)
     * @var \Datetime
     */
    private $confirmedPickupDate;

    /**
     * Undocumented variable
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $note;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $latitude;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=false)
     * @var string
     */
    private $requestId;

    // private $requestUid;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=false)
     * @var string
     */
    private $requestUuid;

    /**
     * Code used for handshake between host and customer Used to generate Qr code
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $handshakeCode;


    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     *
     * @var bool
     */
    private $isConfirmedHandshake;

    /**
     * Status of the waste request
     * @ORM\ManyToOne(targetEntity="General\Entity\WasteRequestState")
     * @var WasteRequestState
     */
    private $wasteRequestState;

    /**
     * @ORM\Column(type="boolean", options={"default" : 1})
     *
     * @var bool
     */
    private $isActive;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime", nullable=false)
     * @var \Datetime
     */
    private $createdOn;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime", nullable=false)
     * @var \Datetime
     */
    private $updatedOn;

    /**
     * Host Assigned for drop Off
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var User
     */
    private $dropOffhost;

    /**
     * The actual weight of the waste in kg
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $wasteWeigth;

    /**
     * Undocumented variable
     * @ORM\OneToMany(targetEntity="Customer\Entity\WasteRequestActivity", mappedBy="wasteRequest")
     * @var Collection
     */
    private $wasteRequestActivity;

    /**
     * Undocumented variable
     * @ORM\ManyToOne(targetEntity="General\Entity\WasteCollectionType")
     * @var WasteCollectionType
     */
    private $wasteCollectionType;

   


    // private

    public function __construct()
    {
        $this->wasteRequestActivity = new ArrayCollection();
    }

    /**
     * Get the value of isActive
     *
     * @return  bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set the value of isActive
     *
     * @param  bool  $isActive
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
     * Set @ORM\Column(name="id", type="integer")
     *
     * @param  integer  $id  @ORM\Column(name="id", type="integer")
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get user Involved
     *
     * @return  \Authentication\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user Involved
     *
     * @param  \Authentication\Entity\User  $user  User Involved
     *
     * @return  self
     */
    public function setUser(\Authentication\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }



    /**
     * Get longitude of Pick Up
     *
     * @return  string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set longitude of Pick Up
     *
     * @param  string  $longitude  Longitude of Pick Up
     *
     * @return  self
     */
    public function setLongitude(string $longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get pickup or DroppOff
     *
     * @return  WasteRequestType
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Set pickup or DroppOff
     *
     * @param  WasteRequestType  $requestType  Pickup or DroppOff
     *
     * @return  self
     */
    public function setRequestType(WasteRequestType $requestType)
    {
        $this->requestType = $requestType;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $note  Undocumented variable
     *
     * @return  self
     */
    public function setNote(string $note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get paper, Metal, Plastics , Compost
     *
     * @return  WasteType
     */
    public function getWasteType()
    {
        return $this->wasteType;
    }

    /**
     * Set paper, Metal, Plastics , Compost
     *
     * @param  WasteType
     *
     * @return  self
     */
    public function setWasteType(WasteType $wasteType)
    {
        $this->wasteType = $wasteType;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPickupAddress()
    {
        return $this->pickupAddress;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pickupAddress  Undocumented variable
     *
     * @return  self
     */
    public function setPickupAddress(string $pickupAddress)
    {
        $this->pickupAddress = $pickupAddress;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getPickupPlaceId()
    {
        return $this->pickupPlaceId;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $pickupPlaceId  Undocumented variable
     *
     * @return  self
     */
    public function setPickupPlaceId(string $pickupPlaceId)
    {
        $this->pickupPlaceId = $pickupPlaceId;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $requestId  Undocumented variable
     *
     * @return  self
     */
    public function setRequestId(string $requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $latitude  Undocumented variable
     *
     * @return  self
     */
    public function setLatitude(string $latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getRequestUuid()
    {
        return $this->requestUuid;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $requestUuid  Undocumented variable
     *
     * @return  self
     */
    public function setRequestUuid(string $requestUuid)
    {
        $this->requestUuid = $requestUuid;

        return $this;
    }

    /**
     * Get the weight of the waste in kg
     *
     * @return  string
     */
    public function getWasteWeigth()
    {
        return $this->wasteWeigth;
    }

    /**
     * Set the weight of the waste in kg
     *
     * @param  string  $wasteWeigth  The weight of the waste in kg
     *
     * @return  self
     */
    public function setWasteWeigth(string $wasteWeigth)
    {
        $this->wasteWeigth = $wasteWeigth;

        return $this;
    }

    /**
     * Get code used for handshake between host and customer Used to generate Qr code
     *
     * @return  string
     */
    public function getHandshakeCode()
    {
        return $this->handshakeCode;
    }

    /**
     * Set code used for handshake between host and customer Used to generate Qr code
     *
     * @param  string  $handshakeCode  Code used for handshake between host and customer Used to generate Qr code
     *
     * @return  self
     */
    public function setHandshakeCode(string $handshakeCode)
    {
        $this->handshakeCode = $handshakeCode;

        return $this;
    }

    /**
     * Get status of the waste request
     *
     * @return  WasteRequestState
     */
    public function getWasteRequestState()
    {
        return $this->wasteRequestState;
    }

    /**
     * Set status of the waste request
     *
     * @param  WasteRequestState  $wasteRequestState  Status of the waste request
     *
     * @return  self
     */
    public function setWasteRequestState(WasteRequestState $wasteRequestState)
    {
        $this->wasteRequestState = $wasteRequestState;

        return $this;
    }

    /**
     * Get host Assigned for drop Off
     *
     * @return  User
     */
    public function getDropOffhost()
    {
        return $this->dropOffhost;
    }

    /**
     * Set host Assigned for drop Off
     *
     * @param  User  $dropOffhost  Host Assigned for drop Off
     *
     * @return  self
     */
    public function setDropOffhost(User $dropOffhost)
    {
        $this->dropOffhost = $dropOffhost;

        return $this;
    }

    /**
     * Get date the Pick up or drop off would take place
     *
     * @return  \Datetime
     */
    public function getRequestDatetime()
    {
        return $this->requestDatetime;
    }

    /**
     * Set date the Pick up or drop off would take place
     *
     * @param  \Datetime  $requestDatetime  Date the Pick up or drop off would take place
     *
     * @return  self
     */
    public function setRequestDatetime(\Datetime $requestDatetime)
    {
        $this->requestDatetime = $requestDatetime;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  EstimatedWeight
     */
    public function getEstimatedWeight()
    {
        return $this->estimatedWeight;
    }

    /**
     * Set undocumented variable
     *
     * @param  EstimatedWeight  $estimatedWeight  Undocumented variable
     *
     * @return  self
     */
    public function setEstimatedWeight(EstimatedWeight $estimatedWeight)
    {
        $this->estimatedWeight = $estimatedWeight;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  \Datetime
     */
    public function getConfirmedPickupDate()
    {
        return $this->confirmedPickupDate;
    }

    /**
     * Set undocumented variable
     *
     * @param  \Datetime  $confirmedPickupDate  Undocumented variable
     *
     * @return  self
     */
    public function setConfirmedPickupDate(\Datetime $confirmedPickupDate)
    {
        $this->confirmedPickupDate = $confirmedPickupDate;

        return $this;
    }

    /**
     * Get the value of isConfirmedHandshake
     *
     * @return  bool
     */
    public function getIsConfirmedHandshake()
    {
        return $this->isConfirmedHandshake;
    }

    /**
     * Set the value of isConfirmedHandshake
     *
     * @param  bool  $isConfirmedHandshake
     *
     * @return  self
     */
    public function setIsConfirmedHandshake(bool $isConfirmedHandshake)
    {
        $this->isConfirmedHandshake = $isConfirmedHandshake;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  Collection
     */
    public function getWasteRequestActivity()
    {
        return $this->wasteRequestActivity;
    }

    /**
     * Get undocumented variable
     *
     * @return  WasteCollectionType
     */
    public function getWasteCollectionType()
    {
        return $this->wasteCollectionType;
    }

    /**
     * Set undocumented variable
     *
     * @param  WasteCollectionType  $wasteCollectionType  Undocumented variable
     *
     * @return  self
     */
    public function setWasteCollectionType(WasteCollectionType $wasteCollectionType)
    {
        $this->wasteCollectionType = $wasteCollectionType;

        return $this;
    }
}
