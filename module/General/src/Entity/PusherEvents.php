<?php

namespace General\Entity;

use Doctrine\ORM\Mapping as ORM;
use Authentication\Entity\Roles;

/**
 * @ORM\Entity
 * @ORM\Table(name="pusher_events")
 *
 */
class PusherEvents
{
    /**
     *
     * @var integer @ORM\Column(name="id", type="integer", nullable=false)
     *      @ORM\Id
     *      @ORM\GeneratedValue(strategy="IDENTITY")
     */

    private $id;

    /**
     * name of the event
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $eventName;

    /**
     * Affiliated Role
     * @ORM\ManyToOne(targetEntity="Authentication\Entity\Roles")
     * @var Roles
     */
    private $roleAffiliate;

    /**
     * Undocumented variable
     * @ORM\Column(nullable=true)
     * @var string
     */
    private $description;

    /**
     * Get the value of id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name of the event
     *
     * @return  string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Set name of the event
     *
     * @param  string  $eventName  name of the event
     *
     * @return  self
     */
    public function setEventName(string $eventName)
    {
        $this->eventName = $eventName;

        return $this;
    }

    /**
     * Get affiliated Role
     *
     * @return  Roles
     */
    public function getRoleAffiliate()
    {
        return $this->roleAffiliate;
    }

    /**
     * Set affiliated Role
     *
     * @param  Roles  $roleAffiliate  Affiliated Role
     *
     * @return  self
     */
    public function setRoleAffiliate(Roles $roleAffiliate)
    {
        $this->roleAffiliate = $roleAffiliate;

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
}
