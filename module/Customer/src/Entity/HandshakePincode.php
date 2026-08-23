<?php

namespace Customer\Entity;

use Authentication\Entity\User;
use Customer\Entity\WasteRequest;
use Doctrine\ORM\Mapping as ORM;
use General\Service\GeneralService;

use function PHPUnit\Framework\isNull;

/**
 * @ORM\Entity
 * @ORM\Table(name="handshake_pincode")
 */
class HandshakePincode
{

    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     *  @ORM\Column(type="string", nullable=false)
     *
     * @var string
     */
    private string $pinCode;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    private \DateTime $createdOn;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private \DateTime $usedOn;

    /**
     * Undocumented variable
     * @ORM\Column(type="boolean", nullable=false)
     * @var bool
     */
    private bool $isUsed;

    /**
     * The Customer that uses the 
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var User
     */
    private User $generatedFor;

    /**
     * The Dorihost or Trahsbuster that initiated the request
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var User
     */
    private User $generatedBy;

    /**
     * Undocumented variable
     * @ORM\ManyToOne(targetEntity="Customer\Entity\WasteRequest")
     * @var WasteRequest
     */
    private WasteRequest $wasteRequest;


    /**
     * Get @ORM\Column(name="id", type="integer", nullable=false)
     *
     * @return  integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of pinCode
     *
     * @return  string
     */
    public function getPinCode()
    {
        return $this->pinCode;
    }

    /**
     * Set the value of pinCode
     *
     * @param  string  $pinCode
     *
     * @return  self
     */
    public function setPinCode(string $pinCode)
    {
        if (isNull($pinCode)) {
            $this->pinCode = GeneralService::generatePincode();
        } else {
            $this->pinCode = $pinCode;
        }


        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  \DateTime
     */ 
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  \DateTime  $createdOn  Undocumented variable
     *
     * @return  self
     */ 
    public function setCreatedOn(\DateTime $createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  \DateTime
     */ 
    public function getUsedOn()
    {
        return $this->usedOn;
    }

    /**
     * Set undocumented variable
     *
     * @param  \DateTime  $usedOn  Undocumented variable
     *
     * @return  self
     */ 
    public function setUsedOn(\DateTime $usedOn)
    {
        $this->usedOn = $usedOn;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  boolean
     */ 
    public function getIsUsed()
    {
        return $this->isUsed;
    }

    /**
     * Set undocumented variable
     *
     * @param  boolean  $isUsed  Undocumented variable
     *
     * @return  self
     */ 
    public function setIsUsed(bool $isUsed)
    {
        $this->isUsed = $isUsed;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  User
     */ 
    public function getGeneratedFor()
    {
        return $this->generatedFor;
    }

    /**
     * Set undocumented variable
     *
     * @param  User  $generatedFor  Undocumented variable
     *
     * @return  self
     */ 
    public function setGeneratedFor(User $generatedFor)
    {
        $this->generatedFor = $generatedFor;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  User
     */ 
    public function getGeneratedBy()
    {
        return $this->generatedBy;
    }

    /**
     * Set undocumented variable
     *
     * @param  User  $generatedBy  Undocumented variable
     *
     * @return  self
     */ 
    public function setGeneratedBy(User $generatedBy)
    {
        $this->generatedBy = $generatedBy;

        return $this;
    }

   

    /**
     * Set undocumented variable
     *
     * @param  WasteRequest  $wasteRequest  Undocumented variable
     *
     * @return  self
     */ 
    public function setWasteRequest(WasteRequest $wasteRequest)
    {
        $this->wasteRequest = $wasteRequest;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  WasteRequest
     */ 
    public function getWasteRequest()
    {
        return $this->wasteRequest;
    }
}
