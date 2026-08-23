<?php

namespace Customer\Entity;

use Doctrine\ORM\Mapping as ORM;
use General\Entity\WasteRequestActivityConst;
use Authentication\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="waste_request_activity")
 *
 */

class WasteRequestActivity
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer")
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * Undocumented variable
     * @ORM\ManyToOne(targetEntity="WasteRequest")
     * @var WasteRequest
     */
    private $wasteRequest;

    /**
     * Undocumented variable
     * @ORM\ManyToOne(targetEntity="General\Entity\WasteRequestActivityConst", inversedBy="wasteRequestActivity")
     * @var WasteRequestActivityConst
     */
    private $activity;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $description;

    /**
     * Undocumented variable
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\User")
     * @var User
     */
    private $inititor;

    /**
     * Undocumented variable
     * @ORM\Column(type="datetime")
     * @var \Datetime
     */
    private $createdOn;

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
     * @return  WasteRequest
     */
    public function getWasteRequest()
    {
        return $this->wasteRequest;
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
     * @return  WasteRequestActivityConst
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set undocumented variable
     *
     * @param  WasteRequestActivityConst  $activity  Undocumented variable
     *
     * @return  self
     */
    public function setActivity(WasteRequestActivityConst $activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $description  Undocumented variable
     *
     * @return  self
     */
    public function setDescription(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  User
     */
    public function getInititor()
    {
        return $this->inititor;
    }

    /**
     * Set undocumented variable
     *
     * @param  User  $inititor  Undocumented variable
     *
     * @return  self
     */
    public function setInititor(User $inititor)
    {
        $this->inititor = $inititor;

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

        return $this;
    }
}
